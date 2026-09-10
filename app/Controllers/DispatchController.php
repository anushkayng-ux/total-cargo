<?php

namespace App\Controllers;

use App\Models\TripModel;
use App\Models\BookingModel;
use App\Models\ClientModel;
use App\Models\VendorModel;
use App\Models\SettingModel;
use App\Libraries\NumberGenerator;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generates the "Dispatch Pack" — LR, Trip Sheet, Loading Advice, POD-blank, Gate Pass.
 * Each doc is available as viewable HTML or downloadable PDF; "pack" combines all five
 * into a single PDF for easy printing at dispatch.
 */
class DispatchController extends BaseController
{
    private const TYPES = [
        'lr'             => ['view' => 'dispatch/lr',             'label' => 'Lorry Receipt (LR)'],
        'trip-sheet'     => ['view' => 'dispatch/trip_sheet',     'label' => 'Trip Sheet'],
        'loading-advice' => ['view' => 'dispatch/loading_advice', 'label' => 'Loading Advice'],
        'pod-blank'      => ['view' => 'dispatch/pod_blank',      'label' => 'POD (to be signed at delivery)'],
        'gate-pass'      => ['view' => 'dispatch/gate_pass',      'label' => 'Gate Pass'],
    ];

    /** Hub page — shows all 5 documents rendered inline, with Print / Download buttons. */
    public function hub(int $tripId)
    {
        [$trip, $booking, $client, $vendor, $company] = $this->context($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $blocks = [];
        foreach (self::TYPES as $key => $def) {
            $blocks[$key] = [
                'label' => $def['label'],
                'html'  => view($def['view'], compact('trip', 'booking', 'client', 'vendor', 'company')),
            ];
        }

        return view('dispatch/hub', [
            'pageTitle' => 'Dispatch Pack · ' . $trip['trip_no'],
            'trip'      => $trip,
            'booking'   => $booking,
            'blocks'    => $blocks,
        ]);
    }

    /** Render a single doc as standalone print-friendly HTML (new tab / print). */
    public function single(int $tripId, string $type)
    {
        if (!isset(self::TYPES[$type])) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        [$trip, $booking, $client, $vendor, $company] = $this->context($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $copy = $this->resolveCopyLabel((string) $this->request->getGet('copy'));
        $body = view(self::TYPES[$type]['view'], compact('trip', 'booking', 'client', 'vendor', 'company', 'copy'));
        return $this->printableShell(self::TYPES[$type]['label'] . ' · ' . $trip['trip_no'], $body, $tripId);
    }

    /** Send a single doc as a PDF download (inline). */
    public function singlePdf(int $tripId, string $type)
    {
        if (!isset(self::TYPES[$type])) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        [$trip, $booking, $client, $vendor, $company] = $this->context($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $copy = $this->resolveCopyLabel((string) $this->request->getGet('copy'));
        $html = $this->printableShell(
            self::TYPES[$type]['label'] . ' · ' . $trip['trip_no'],
            view(self::TYPES[$type]['view'], compact('trip', 'booking', 'client', 'vendor', 'company', 'copy')),
            $tripId,
            /*forPdf=*/ true
        );
        $copySuffix = $copy !== '' ? '_' . str_replace(' ', '_', strtoupper($copy)) : '';
        // Use the docket number (lr_no) as the filename identifier when it exists —
        // that's what accounting / clients look up by. Fall back to trip_no otherwise.
        $ident   = !empty($trip['lr_no']) ? preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $trip['lr_no']) : $trip['trip_no'];
        $prefix  = strtoupper(str_replace('-', '_', $type));
        $filename = ($type === 'lr' ? '' : $prefix . '_') . $ident . $copySuffix . '.pdf';
        return $this->streamPdf($html, $filename);
    }

    /** Map ?copy=... query value to the printed label. Empty = no stamp. */
    private function resolveCopyLabel(string $copy): string
    {
        $copy = strtolower(trim($copy));
        return match ($copy) {
            'consignee' => 'Consignee Copy',
            'consignor' => 'Consignor Copy',
            'driver'    => 'Driver Copy',
            'record'    => 'Record Copy',
            default     => '',
        };
    }

    /**
     * Combined pack PDF. Pass `?types=lr,pod-blank` to restrict the set.
     * Unknown types are ignored; order follows the canonical order declared in self::TYPES.
     */
    public function packPdf(int $tripId)
    {
        [$trip, $booking, $client, $vendor, $company] = $this->context($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Trip not found.');

        $raw    = (string) $this->request->getGet('types');
        $wanted = array_keys(self::TYPES);
        if ($raw !== '') {
            $requested = array_map('trim', explode(',', strtolower($raw)));
            $valid     = array_values(array_filter($requested, fn($t) => isset(self::TYPES[$t])));
            // Follow canonical order, drop duplicates
            $wanted    = array_values(array_filter(array_keys(self::TYPES), fn($t) => in_array($t, $valid, true)));
        }
        if (empty($wanted)) {
            return redirect()->to(site_url('trips/' . $tripId . '/dispatch'))
                ->with('error', 'Select at least one document to include in the pack.');
        }

        $stack = '';
        foreach ($wanted as $type) {
            $stack .= view(self::TYPES[$type]['view'], compact('trip', 'booking', 'client', 'vendor', 'company'));
        }

        $suffix = (count($wanted) === count(self::TYPES))
            ? ''
            : '_' . implode('-', array_map(fn($t) => str_replace('-', '_', strtoupper($t)), $wanted));
        $html = $this->printableShell('Dispatch Pack · ' . $trip['trip_no'], $stack, $tripId, /*forPdf=*/ true);
        return $this->streamPdf($html, 'DISPATCH_PACK_' . $trip['trip_no'] . $suffix . '.pdf');
    }

    /**
     * Save (or update) the LR number for a trip — MANUAL entry.
     * The operator types their next-in-series number (e.g. 5013) so we don't
     * clobber their existing docket series. Uniqueness enforced across all
     * trips (excluding this one). Editable any time after initial save.
     */
    public function generateLr(int $tripId)
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return redirect()->to(site_url('trips'))->with('error', 'Not found.');

        $lrNo = trim((string) $this->request->getPost('lr_no'));
        if ($lrNo === '') {
            // Empty means "clear the LR" — allow that so operator can undo.
            (new TripModel())->update($tripId, [
                'lr_no'           => null,
                'lr_generated_at' => null,
                'updated_by'      => $this->auth->id(),
            ]);
            return redirect()->to(site_url('trips/' . $tripId . '/dispatch'))
                ->with('success', 'LR number cleared for this trip.');
        }

        // If unchanged, skip write.
        if (isset($trip['lr_no']) && (string) $trip['lr_no'] === $lrNo) {
            return redirect()->to(site_url('trips/' . $tripId . '/dispatch'))
                ->with('success', 'LR ' . $lrNo . ' already saved — no change.');
        }

        // Uniqueness: same LR can't live on a different trip OR a different booking.
        $dup = (new TripModel())->where('lr_no', $lrNo)->where('id !=', $tripId)->first();
        if ($dup) {
            return redirect()->to(site_url('trips/' . $tripId . '/dispatch'))
                ->with('error', 'LR ' . $lrNo . ' is already used by Trip ' . ($dup['trip_no'] ?? ('#' . $dup['id'])) . '. Please use a different LR number.');
        }
        // Also block if a booking (this or other) already has this LR queued.
        $bkDup = (new \App\Models\BookingModel())
            ->where('lr_no', $lrNo)
            ->where('id !=', (int) ($trip['booking_id'] ?? 0))
            ->first();
        if ($bkDup) {
            return redirect()->to(site_url('trips/' . $tripId . '/dispatch'))
                ->with('error', 'LR ' . $lrNo . ' is already reserved by Booking ' . ($bkDup['booking_no'] ?? ('#' . $bkDup['id'])) . '. Please use a different LR number.');
        }

        $wasEmpty = empty($trip['lr_no']);
        (new TripModel())->update($tripId, [
            'lr_no'           => $lrNo,
            // Preserve original issue time on subsequent edits so audit trail is honest.
            'lr_generated_at' => $wasEmpty ? date('Y-m-d H:i:s') : ($trip['lr_generated_at'] ?? date('Y-m-d H:i:s')),
            'updated_by'      => $this->auth->id(),
        ]);
        $msg = $wasEmpty ? 'LR ' . $lrNo . ' saved.' : 'LR updated to ' . $lrNo . '.';
        return redirect()->to(site_url('trips/' . $tripId . '/dispatch'))->with('success', $msg);
    }

    // -----------------------------------------------------------------

    /**
     * @return array{0: ?array, 1: ?array, 2: ?array, 3: ?array, 4: array}
     */
    private function context(int $tripId): array
    {
        $trip = (new TripModel())->find($tripId);
        if (!$trip) return [null, null, null, null, []];

        // LR number is now MANUAL — the operator types their in-series number
        // via /trips/:id/dispatch/lr/generate. Don't auto-assign here; docket
        // views will show a "not yet issued" state until the operator saves one.

        $booking = !empty($trip['booking_id']) ? (new BookingModel())->find((int) $trip['booking_id']) : [];
        $client  = !empty($booking['client_id']) ? (new ClientModel())->find((int) $booking['client_id']) : [];
        $vendor  = !empty($booking['vendor_id']) ? (new VendorModel())->find((int) $booking['vendor_id']) : [];
        $company = (new SettingModel())->getAllGrouped()['company'] ?? [];

        return [$trip, $booking ?: [], $client ?: [], $vendor ?: [], $company];
    }

    private function printableShell(string $title, string $body, int $tripId, bool $forPdf = false): string
    {
        $styles = view('dispatch/_styles');

        $toolbar = '';
        if (!$forPdf) {
            $toolbar = '
            <div class="no-print" style="max-width:210mm;margin:10mm auto -2mm;padding:0 4mm;display:flex;gap:8px;align-items:center;">
              <a href="' . site_url('trips/' . $tripId) . '" style="font-family:Poppins,sans-serif;font-size:.85rem;text-decoration:none;padding:6px 12px;border-radius:8px;background:#eee;color:#111;">← Back to trip</a>
              <a href="' . site_url('trips/' . $tripId . '/dispatch') . '" style="font-family:Poppins,sans-serif;font-size:.85rem;text-decoration:none;padding:6px 12px;border-radius:8px;background:#eee;color:#111;">Dispatch pack hub</a>
              <div style="flex:1"></div>
              <button type="button" onclick="window.print()" style="font-family:Poppins,sans-serif;font-size:.85rem;padding:6px 14px;border-radius:8px;border:0;background:#111;color:#fff;cursor:pointer;">🖨️ Print</button>
            </div>';
        }

        return "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>" . esc($title) . "</title>" . $styles . "</head><body>" . $toolbar . $body . "</body></html>";
    }

    private function streamPdf(string $html, string $filename)
    {
        $opts = new Options();
        $opts->set('isRemoteEnabled', false);
        $opts->set('defaultFont', 'helvetica');

        $pdf = new Dompdf($opts);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        // ?download=1 flips the browser from "show inline" to "save file"
        // so the download button on the Dispatch Pack does what it says
        // (previously users had to open the PDF, then click download again).
        $disposition = $this->request->getGet('download') ? 'attachment' : 'inline';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"')
            ->setBody($pdf->output());
    }
}
