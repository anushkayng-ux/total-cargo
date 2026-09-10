<?php

namespace App\Libraries;

use App\Models\EinvoiceLogModel;
use App\Models\InvoiceItemModel;
use App\Models\InvoiceModel;
use App\Models\ClientModel;
use App\Models\SettingModel;

/**
 * ClearTax e-Invoice adapter.
 *
 * When API creds are missing we record the intended request in einvoice_logs
 * with irn_status = "Queued" and return without calling out. Wire live by
 * filling cleartax.* in .env. The payload shape follows the standard IRP
 * schema that ClearTax accepts on behalf of NIC.
 */
class ClearTaxService
{
    private string $baseUrl;
    private string $apiKey;
    private string $sellerGstin;

    public function __construct()
    {
        $this->baseUrl     = (string) env('cleartax.baseUrl', '');
        $this->apiKey      = (string) env('cleartax.apiKey', '');
        $this->sellerGstin = (string) env('cleartax.gstin', '');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '' && $this->sellerGstin !== '';
    }

    public function generateIrn(int $invoiceId): array
    {
        $inv = (new InvoiceModel())->withJoins()->where('invoices.id', $invoiceId)->first();
        if (!$inv) return ['ok' => false, 'error' => 'Invoice not found'];

        $items   = (new InvoiceItemModel())->forInvoice($invoiceId);
        $client  = (new ClientModel())->find((int) $inv['client_id']);
        $company = [
            'name'    => (new SettingModel())->get('company_name', 'TPT Logistics'),
            'gstin'   => $this->sellerGstin ?: (string) (new SettingModel())->get('company_gstin', ''),
            'state'   => (string) (new SettingModel())->get('company_state', ''),
            'address' => (string) (new SettingModel())->get('company_address', ''),
        ];

        $payload = $this->buildPayload($inv, $items, $client, $company);

        $logModel = new EinvoiceLogModel();
        $logId = $logModel->insert([
            'invoice_id'      => $invoiceId,
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'irn_status'      => 'Queued',
        ]);

        if (!$this->isConfigured()) {
            return ['ok' => false, 'queued' => true, 'log_id' => $logId, 'note' => 'ClearTax credentials not configured — payload queued.'];
        }

        $endpoint = rtrim($this->baseUrl, '/') . '/einv/v2/generate';
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'gstin: ' . $this->sellerGstin,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        $decoded = is_string($response) ? json_decode($response, true) : null;
        $irn     = $decoded['data']['Irn']       ?? $decoded['Irn']       ?? null;
        $ackNo   = $decoded['data']['AckNo']     ?? $decoded['AckNo']     ?? null;
        $ackDt   = $decoded['data']['AckDt']     ?? $decoded['AckDt']     ?? null;
        $ok      = $status >= 200 && $status < 300 && $irn;

        $logModel->update($logId, [
            'response_payload' => is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : (string) $response,
            'irn_status'       => $ok ? 'Success' : 'Failed',
        ]);

        if ($ok) {
            (new InvoiceModel())->update($invoiceId, [
                'irn_no'   => $irn,
                'ack_no'   => $ackNo,
                'ack_date' => $ackDt ? date('Y-m-d H:i:s', strtotime((string) $ackDt)) : date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'ok'       => (bool) $ok,
            'log_id'   => $logId,
            'irn'      => $irn,
            'ack_no'   => $ackNo,
            'response' => $decoded,
            'error'    => $ok ? null : ($decoded['message'] ?? $err ?? ('HTTP ' . $status)),
        ];
    }

    private function buildPayload(array $inv, array $items, ?array $client, array $company): array
    {
        $interstate = !empty($client['state']) && $this->slug((string) $client['state']) !== $this->slug((string) $company['state']);

        $itemList = [];
        foreach ($items as $i => $it) {
            $rate    = (float) $it['rate'];
            $qty     = (float) $it['qty'];
            $taxable = (float) $it['taxable_amount'];
            $gstPc   = (float) $it['gst_percent'];
            $gstAmt  = (float) $it['gst_amount'];

            $itemList[] = [
                'SlNo'       => (string) ($i + 1),
                'PrdDesc'    => (string) ($it['description'] ?? ''),
                'IsServc'    => 'Y',
                'HsnCd'      => (string) ($it['hsn_sac'] ?? '996791'),
                'Qty'        => round($qty, 2),
                'Unit'       => 'OTH',
                'UnitPrice'  => round($rate, 2),
                'TotAmt'     => round($rate * $qty, 2),
                'Discount'   => 0,
                'AssAmt'     => round($taxable, 2),
                'GstRt'      => round($gstPc, 2),
                'IgstAmt'    => $interstate ? round($gstAmt, 2) : 0,
                'CgstAmt'    => $interstate ? 0 : round($gstAmt / 2, 2),
                'SgstAmt'    => $interstate ? 0 : round($gstAmt / 2, 2),
                'TotItemVal' => round($taxable + $gstAmt, 2),
            ];
        }

        return [
            'Version'  => '1.1',
            'TranDtls' => [
                'TaxSch'  => 'GST',
                'SupTyp'  => 'B2B',
                'RegRev'  => 'N',
                'IgstOnIntra' => 'N',
            ],
            'DocDtls'  => [
                'Typ' => 'INV',
                'No'  => (string) $inv['invoice_no'],
                'Dt'  => date('d/m/Y', strtotime((string) $inv['invoice_date'])),
            ],
            'SellerDtls' => [
                'Gstin'  => (string) $company['gstin'],
                'LglNm'  => (string) $company['name'],
                'Addr1'  => (string) $company['address'] ?: '-',
                'Loc'    => (string) $company['state']   ?: '-',
                'Pin'    => 0,
                'Stcd'   => '',
            ],
            'BuyerDtls' => [
                'Gstin' => (string) ($client['gst_no'] ?? 'URP'),
                'LglNm' => (string) ($client['company_name'] ?? 'Unregistered'),
                'Pos'   => (string) ($client['state'] ?? ''),
                'Addr1' => (string) ($client['address'] ?? '-'),
                'Loc'   => (string) ($client['city'] ?? '-'),
                'Pin'   => 0,
                'Stcd'  => '',
            ],
            'ItemList' => $itemList,
            'ValDtls'  => [
                'AssVal'   => round((float) $inv['taxable_amount'], 2),
                'IgstVal'  => round((float) $inv['igst_amount'], 2),
                'CgstVal'  => round((float) $inv['cgst_amount'], 2),
                'SgstVal'  => round((float) $inv['sgst_amount'], 2),
                'RndOffAmt'=> round((float) $inv['round_off'], 2),
                'TotInvVal'=> round((float) $inv['total_amount'], 2),
            ],
        ];
    }

    private function slug(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $s));
    }
}
