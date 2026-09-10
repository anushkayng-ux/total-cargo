<?php

namespace App\Jobs;

/**
 * Background job: send an invoice email. Use instead of calling
 * InvoicesController::shareEmail() inline when bulk-sending so the user
 * isn't held while the SMTP roundtrip runs.
 *
 *   Jobs::push(SendInvoiceEmailJob::class, ['invoice_id' => 42]);
 */
class SendInvoiceEmailJob
{
    public function handle(array $payload): void
    {
        $id = (int) ($payload['invoice_id'] ?? 0);
        if (!$id) throw new \RuntimeException('SendInvoiceEmailJob: invoice_id required');

        // The controller already knows how to do this — we just dispatch
        // through it as a background runner. Construct enough state so
        // it can run without a request scope.
        $svc = new \App\Libraries\EmailService();
        $row = (new \App\Models\InvoiceModel())->withJoins()->where('invoices.id', $id)->first();
        if (!$row) throw new \RuntimeException("SendInvoiceEmailJob: invoice $id not found");

        $client = !empty($row['client_id']) ? (new \App\Models\ClientModel())->find((int) $row['client_id']) : null;
        $to     = (string) ($client['email'] ?? '');
        if (!$to) throw new \RuntimeException("SendInvoiceEmailJob: client has no email");

        $vars = [
            'company'        => $client['company_name'] ?? '',
            'invoice_no'     => $row['invoice_no'] ?? '',
            'amount'         => number_format((float) ($row['total_amount'] ?? 0), 2),
            'balance'        => number_format((float) ($row['balance_due']  ?? 0), 2),
            'due_date'       => $row['due_date'] ?? '',
        ];
        $svc->sendTemplate('invoice_share', $to, $vars, [
            'recipient_name' => $client['contact_name'] ?? null,
            'related_module' => 'invoice',
            'related_table'  => 'invoices',
            'related_id'     => $id,
        ]);
    }
}
