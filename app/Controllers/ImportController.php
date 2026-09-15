<?php

namespace App\Controllers;

use App\Libraries\SafeUpload;
use App\Libraries\SpreadsheetReader;
use App\Models\ClientModel;
use App\Models\VendorModel;
use App\Models\VendorContactModel;

/**
 * Excel / CSV importer for Clients and Vendors.
 *
 * Flow per entity:
 *   1. GET  /import/{type}            — upload form + last summary
 *   2. POST /import/{type}/parse      — read sheet, map columns, hold rows in session
 *   3. GET  /import/{type}/review     — preview each row + dedupe flags
 *   4. POST /import/{type}/confirm    — insert the ticked rows
 *
 * The column mapper is fuzzy — header text is lower-cased, whitespace and
 * punctuation removed, then compared against a per-field alias list. So
 * "CUSTMORE NAME", "Customer Name", "Company / Firm" all map to company_name.
 */
class ImportController extends BaseController
{
    private const TYPES = ['clients', 'vendors'];

    // ── Field maps ───────────────────────────────────────────────────
    // Key = DB column, value = list of header aliases (case- & punctuation-insensitive).
    private const CLIENT_FIELDS = [
        'company_name'  => ['companyname', 'custmorename', 'customername', 'clientname', 'partyname', 'firmname', 'name', 'company'],
        'gst_no'        => ['gstno', 'gstnumber', 'gstin', 'gst'],
        'pan_no'        => ['panno', 'pannumber', 'pan'],
        'contact_name'  => ['contactname', 'contactperson', 'personname', 'spoc'],
        'mobile'        => ['mobile', 'mobileno', 'mobilenumber', 'phone', 'phoneno', 'phonenumber', 'contactno', 'contactnumber'],
        'alt_mobile'    => ['altmobile', 'alternatemobile', 'altphone', 'secondarymobile'],
        'email'         => ['email', 'emailid', 'emailaddress', 'mailid'],
        'address'       => ['address', 'fulladdress', 'officeaddress', 'billingaddress'],
        'city'          => ['city', 'town'],
        'state'         => ['state', 'province'],
        'pincode'       => ['pincode', 'pin', 'zip', 'zipcode', 'postalcode'],
        'credit_limit'  => ['creditlimit'],
        'credit_days'   => ['creditdays', 'paymentterms', 'paymentdays'],
    ];

    private const VENDOR_FIELDS = [
        // VENDOR'S NAME — apostrophe is stripped by normaliseKey() → "vendorsname"
        'company_name'  => ['companyname', 'vendorname', 'vendorsname', 'firmname', 'transportername', 'partyname', 'name', 'company'],
        // CONTACT PERSON in their file = the named owner/contact at the vendor
        'owner_name'    => ['ownername', 'proprietorname', 'proprietor', 'contactperson', 'personname'],
        'gst_no'        => ['gstno', 'gstnumber', 'gstin', 'gst'],
        'pan_no'        => ['panno', 'pannumber', 'pan'],
        'contact_name'  => ['contactname', 'spoc'],
        // CONTACT NUMBER & EMAIL ID is one merged column — match the whole thing too
        'mobile'        => ['mobile', 'mobileno', 'mobilenumber', 'phone', 'phoneno', 'phonenumber', 'contactno', 'contactnumber', 'contactnumberemailid', 'contactnumberandemailid'],
        'alt_mobile'    => ['altmobile', 'alternatemobile', 'altphone', 'secondarymobile'],
        'whatsapp_no'   => ['whatsappno', 'whatsapp', 'wano', 'wanumber'],
        'email'         => ['email', 'emailid', 'emailaddress', 'mailid'],
        // VENDOR ADDRESS → address
        'address'       => ['address', 'fulladdress', 'officeaddress', 'vendoraddress'],
        'city'          => ['city', 'town', 'location'],
        'state'         => ['state', 'province'],
        'pincode'       => ['pincode', 'pin', 'zip', 'zipcode', 'postalcode'],
        'bank_name'     => ['bankname', 'bank'],
        'account_no'    => ['accountno', 'accountnumber', 'acno', 'acnumber', 'bankaccount'],
        'ifsc_code'     => ['ifsc', 'ifsccode'],
    ];

    // Sheet-only columns we recognise but don't store on vendors (they feed the
    // related tables: vendor_vehicle_types, vendor_routes). Listed here so the
    // header-mapper finds them and we can build sidecar inserts.
    private const VENDOR_SIDECAR_FIELDS = [
        // Vehicle type columns (OPEN / CLOSED BODY). Keep aliases specific so
        // generic 2-letter tokens don't bleed into other headers.
        'vehicle_open'        => ['open', 'openbody', 'opentruck'],
        'vehicle_closed'      => ['closedbody', 'closebody'],
        'vehicle_type_any'    => ['vehicletype', 'vehicetype'],
        // Route columns. "from"/"to" are too generic (they substring-match into
        // many headers) — match only specific labels.
        'route_origin'        => ['origin', 'loadingpoint', 'originloadingpoint', 'pickupcity'],
        'route_destination'   => ['destination', 'offloadingpoint', 'destinationoffloadingpoint', 'dropcity'],
        'broker_or_fleet'     => ['brokerfleetowner', 'brokerorfleetowner', 'fleetowner', 'vendortype', 'vendorcategory'],
    ];

    // ── Routes ───────────────────────────────────────────────────────

    /** Unified landing page: pick Clients vs Vendors from a dropdown, then upload. */
    public function chooser()
    {
        return $this->render('import/chooser', [
            'pageTitle'        => 'Import Data [Administration]',
            'preselected'      => (string) $this->request->getGet('type') ?: 'clients',
            'lastClientImport' => $this->session->get('import_last_summary_clients'),
            'lastVendorImport' => $this->session->get('import_last_summary_vendors'),
        ], retroFixedShell: true);
    }

    /**
     * Single-screen flow: user picks the type from a dropdown and uploads the
     * file in the same form. We dispatch to parse() based on the chosen type.
     */
    public function chooserSubmit()
    {
        $type = (string) $this->request->getPost('type');
        $type = in_array($type, self::TYPES, true) ? $type : 'clients';
        return $this->parse($type);
    }

    public function form(string $type)
    {
        $type = $this->validType($type);
        return $this->render('import/form', [
            'pageTitle'    => 'Import Data [Administration] — ' . ucfirst($type),
            'type'         => $type,
            'lastSummary'  => $this->session->get("import_last_summary_$type"),
        ], retroFixedShell: true);
    }

    public function parse(string $type)
    {
        $type = $this->validType($type);
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Upload a .xlsx or .csv file.');
        }
        try {
            $rel = SafeUpload::move($file, 'imports', 'spreadsheet', 8 * 1024 * 1024);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $full = WRITEPATH . 'uploads/' . $rel;
        try {
            $raw = SpreadsheetReader::read($full);
        } catch (\Throwable $e) {
            @unlink($full);
            return redirect()->back()->with('error', $e->getMessage());
        }
        @unlink($full);

        if (count($raw) < 2) {
            return redirect()->back()->with('error', 'File must have a header row plus at least one data row.');
        }

        [$header, $rows] = $this->findHeaderRow($raw);
        if (!$header) {
            return redirect()->back()->with('error', 'Could not find a header row. Make sure column names like "Company Name", "GST Number" etc. appear in the first non-empty row.');
        }

        $fieldMap = $type === 'clients' ? self::CLIENT_FIELDS : self::VENDOR_FIELDS;
        $mapping  = $this->mapHeaders($header, $fieldMap);

        // Vendor sidecar columns (vehicle types + routes) — captured separately
        // and applied on insert into vendor_vehicle_types / vendor_routes.
        $sidecar = $type === 'vendors' ? $this->mapHeaders($header, self::VENDOR_SIDECAR_FIELDS) : [];

        if (!isset($mapping['company_name'])) {
            return redirect()->back()->with('error', 'Could not find a column for the company / customer name. Required headers: at least one of "Company Name", "Customer Name", "Vendor Name".');
        }

        try {
            $parsed = $this->extractRows($rows, $mapping, $sidecar);
            $parsed = $this->flagDuplicates($parsed, $type);
        } catch (\Throwable $e) {
            log_message('error', 'Import parse failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Could not parse the file: ' . $e->getMessage());
        }

        $this->session->set("import_proposed_$type", $parsed);
        $this->session->set("import_mapping_$type", $mapping);
        $this->session->set("import_header_$type",  $header);

        return redirect()->to(site_url("import/$type/review"));
    }

    public function review(string $type)
    {
        $type = $this->validType($type);
        return $this->render('import/review', [
            'pageTitle' => 'Import Data [Administration] — ' . ucfirst($type),
            'type'      => $type,
            'rows'      => $this->session->get("import_proposed_$type") ?? [],
            'mapping'   => $this->session->get("import_mapping_$type") ?? [],
            'header'    => $this->session->get("import_header_$type")  ?? [],
        ], retroFixedShell: true);
    }

    public function confirm(string $type)
    {
        $type     = $this->validType($type);
        $proposed = $this->session->get("import_proposed_$type") ?? [];
        if (empty($proposed)) {
            return redirect()->to(site_url("import/$type"))->with('error', 'Nothing to import — upload a file first.');
        }

        $picked = array_map('intval', (array) $this->request->getPost('idx'));
        $created = 0; $updated = 0; $skipped = 0;

        try {
            if ($type === 'clients') {
                [$created, $updated, $skipped] = $this->insertClients($proposed, $picked);
            } else {
                [$created, $updated, $skipped] = $this->insertVendors($proposed, $picked);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Import confirm failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $this->session->remove("import_proposed_$type");
        $this->session->remove("import_mapping_$type");
        $this->session->remove("import_header_$type");
        $this->session->set("import_last_summary_$type", [
            'when'    => date('Y-m-d H:i'),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        $base = $type === 'clients' ? site_url('clients') : site_url('vendors');
        return redirect()->to($base)
            ->with('success', "$created new $type added · $updated updated · $skipped skipped");
    }

    /**
     * Downloadable CSV template. Headers mirror the user's real files exactly:
     *  - Clients: S. No., Customer Name, GST Number
     *  - Vendors: DOMESTIC VENDOR DATA SHEET layout (10 columns)
     */
    public function template(string $type)
    {
        $type = $this->validType($type);

        if ($type === 'clients') {
            $headers = ['S. No.', 'Customer Name', 'GST Number'];
            $samples = [
                ['1', 'N Ranga Rao & Sons Pvt. Ltd.',         '29AAECN8103G2ZG'],
                ['2', 'Tempesta Luxury Products Pvt. Ltd.',   '08AADCT7791B1Z6'],
            ];
        } else {
            $headers = [
                'S.NO', "Vendor's Name", 'Broker/Fleet Owner', 'Vendor Address',
                'Contact Person', 'Contact Number & Email ID',
                'Open', 'Closed Body',
                'Origin (Loading Point)', 'Destination (Offloading Point)',
            ];
            $samples = [
                ['1', 'Anil Roadways',           'Broker/Fleet Owner', 'CW-48 SGT Nagar Delhi', '',             '9953723164',            '14FT,17FT,19FT',  '32FT, XL, XXL', 'Delhi, NCR',   'All India'],
                ['2', 'Ashok Road Carriers',    'Broker',             'Near Fruit Mandi Azadpur', 'Yogesh Kumar', '9911065019 9911665930', '14FT,17FT,19FT,22FT', '',          'Delhi, NCR',   'All UP & Nepal'],
            ];
        }

        $body = $this->toCsvLine($headers);
        foreach ($samples as $row) $body .= $this->toCsvLine($row);

        $filename = "{$type}-import-template.csv";
        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', "attachment; filename=\"$filename\"")
            ->setBody($body);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function validType(string $type): string
    {
        if (!in_array($type, self::TYPES, true)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return $type;
    }

    /**
     * Find the header row. Some sheets (DOMESTIC VENDOR DATA SHEET style) have
     * a merged two-row header where row 1 spans groups ("VEHICLE TYPE",
     * "SERVICES") and row 2 has the sub-headers ("OPEN", "CLOSED BODY",
     * "ORIGIN", "DESTINATION"). When we spot this pattern we MERGE the two
     * rows — cell value = sub-header if present, else parent header — so the
     * mapper can find every column.
     */
    private function findHeaderRow(array $rows): array
    {
        $isHeaderish = function (array $r): bool {
            $joined = self::normaliseKey(implode(' ', $r));
            return (bool) preg_match('/(name|company|gst|mobile|phone|email|address|pan|customer|vendor|origin|destination|contact|broker|loadingpoint|offloadingpoint)/i', $joined);
        };
        // A "data" row almost always has a numeric S.NO or company name without
        // header keywords. We stop the merge as soon as we hit one of those.
        $looksLikeData = function (array $r): bool {
            $vals = array_filter(array_map('trim', $r), fn($v) => $v !== '');
            if (empty($vals)) return false;
            // Any cell that's pure digits/decimal AND no header keywords elsewhere
            $first = (string) array_values($vals)[0];
            return ctype_digit($first);
        };

        $limit = min(10, count($rows));
        for ($i = 0; $i < $limit; $i++) {
            if (!$isHeaderish($rows[$i])) continue;

            // Merge ALL consecutive header-looking rows. Some sheets have 2,
            // some have a title row + 2 sub-headers. Keep going while the next
            // row is still header-ish AND doesn't look like a data row.
            $endIdx = $i;
            while (isset($rows[$endIdx + 1])
                && $isHeaderish($rows[$endIdx + 1])
                && !$looksLikeData($rows[$endIdx + 1])) {
                $endIdx++;
            }

            $width = 0;
            for ($k = $i; $k <= $endIdx; $k++) $width = max($width, count($rows[$k]));

            // Merge cell-by-cell: most recent non-empty value wins (so sub-header
            // overrides the parent header label).
            $merged = array_fill(0, $width, '');
            for ($k = $i; $k <= $endIdx; $k++) {
                foreach ($rows[$k] as $c => $v) {
                    $v = trim((string) $v);
                    if ($v !== '') $merged[$c] = $v;
                }
            }
            return [$merged, array_slice($rows, $endIdx + 1)];
        }
        return [array_map('strval', $rows[0]), array_slice($rows, 1)];
    }

    /**
     * Returns ['db_field' => column_index, …] for every alias hit. Column
     * names are normalised — case folded, alphanumerics only.
     */
    private function mapHeaders(array $header, array $fieldMap): array
    {
        $normalised = [];
        foreach ($header as $idx => $h) {
            $normalised[$idx] = self::normaliseKey((string) $h);
        }
        $out  = [];
        $used = [];  // column indexes already claimed — prevents one column being mapped to two fields
                     // (e.g. "CONTACT NUMBER & EMAIL ID" matching both `mobile` and `email`).

        // Pass 1 — exact match. A column whose normalised name equals an alias
        // wins over a column that merely contains the alias.
        foreach ($fieldMap as $db => $aliases) {
            foreach ($normalised as $idx => $key) {
                if ($key === '' || isset($used[$idx])) continue;
                if (in_array($key, $aliases, true)) {
                    $out[$db]    = $idx;
                    $used[$idx]  = true;
                    break;
                }
            }
        }
        // Pass 2 — substring (fuzzy) match for fields still unmapped.
        foreach ($fieldMap as $db => $aliases) {
            if (isset($out[$db])) continue;
            foreach ($normalised as $idx => $key) {
                if ($key === '' || isset($used[$idx])) continue;
                foreach ($aliases as $a) {
                    if (str_contains($key, $a)) {
                        $out[$db]   = $idx;
                        $used[$idx] = true;
                        break 2;
                    }
                }
            }
        }
        return $out;
    }

    private static function normaliseKey(string $s): string
    {
        $s = strtolower($s);
        return preg_replace('/[^a-z0-9]+/', '', $s);
    }

    /**
     * Build the typed payload per data row from the column index mapping.
     * For vendors, $sidecar maps optional fields (vehicle_open, vehicle_closed,
     * route_origin, route_destination, broker_or_fleet) — those are stashed
     * on the row and applied to vendor_vehicle_types / vendor_routes on insert.
     */
    private function extractRows(array $rows, array $mapping, array $sidecar = []): array
    {
        $out = [];
        foreach ($rows as $r) {
            $payload = [];
            foreach ($mapping as $field => $col) {
                $val = trim((string) ($r[$col] ?? ''));
                if ($val !== '') $payload[$field] = $val;
            }
            // Skip the "reverse route" rows that have no vendor name — these are
            // legitimately part of the DOMESTIC VENDOR DATA SHEET where the
            // origin/destination flips below a vendor row.
            if (empty($payload['company_name'])) continue;

            // Belt-and-braces: if the row's company_name happens to look like a
            // header label that leaked through (e.g. "VENDOR'S NAME") skip it —
            // headers contain "'s name" / "number" / "person" etc.
            $cn = strtolower($payload['company_name']);
            if (preg_match("/(^s\.?\s*no\.?$|vendor'?s name|company name|customer name|contact (number|person|name)|gst (number|no)|pan (number|no))/i", $cn)) continue;

            // CONTACT NUMBER & EMAIL ID column may hold a phone + an email mixed:
            //   "9911065019 9911665930"  or  "9876500000 ops@vendor.in"
            // Extract email if present, then take the first 10-15 digit run as mobile.
            if (!empty($payload['mobile'])) {
                $raw = $payload['mobile'];
                if (empty($payload['email']) && preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $raw, $m)) {
                    $payload['email'] = $m[0];
                }
                $payload['mobile'] = self::firstPhone($raw);
                // If a second number is present, keep it as alt_mobile.
                $rest = preg_replace('/^\D*' . preg_quote($payload['mobile'], '/') . '/', '', self::digitsOnly($raw));
                if (empty($payload['alt_mobile']) && strlen($rest) >= 7) {
                    $payload['alt_mobile'] = substr($rest, 0, 15);
                }
            }
            if (!empty($payload['alt_mobile']))  $payload['alt_mobile']  = self::firstPhone($payload['alt_mobile']);
            if (!empty($payload['whatsapp_no'])) $payload['whatsapp_no'] = self::firstPhone($payload['whatsapp_no']);
            if (!empty($payload['pincode']))     $payload['pincode']     = self::digitsOnly($payload['pincode']);
            if (!empty($payload['gst_no']))      $payload['gst_no']      = strtoupper(preg_replace('/\s+/', '', $payload['gst_no']));
            if (!empty($payload['pan_no']))      $payload['pan_no']      = strtoupper(preg_replace('/\s+/', '', $payload['pan_no']));
            if (!empty($payload['credit_limit'])) $payload['credit_limit'] = (float) preg_replace('/[^0-9.]/', '', $payload['credit_limit']);
            if (!empty($payload['credit_days']))  $payload['credit_days']  = (int) preg_replace('/[^0-9]/', '', $payload['credit_days']);

            // Derive state from GST prefix if state is missing — GST prefix is the
            // 2-digit state code (e.g. 29 = Karnataka, 06 = Haryana).
            if (empty($payload['state']) && !empty($payload['gst_no']) && strlen($payload['gst_no']) >= 2) {
                $derived = self::stateFromGstPrefix(substr($payload['gst_no'], 0, 2));
                if ($derived) $payload['state'] = $derived;
            }

            // Pull vendor sidecar values onto the row payload — used on insert.
            foreach ($sidecar as $field => $col) {
                $val = trim((string) ($r[$col] ?? ''));
                if ($val !== '') $payload["_$field"] = $val;
            }
            // Promote the BROKER/FLEET OWNER sidecar to vendor_type so it
            // appears in the review preview (not just at insert time).
            if (!empty($payload['_broker_or_fleet'])) {
                $payload['vendor_type'] = mb_substr($payload['_broker_or_fleet'], 0, 60);
            }

            // Defensive: if email somehow got a non-email value, drop it rather
            // than letting validation flag the row.
            if (!empty($payload['email']) && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                unset($payload['email']);
            }

            $payload['_errors']     = $this->validateRow($payload);
            $payload['_duplicate']  = null; // filled in flagDuplicates()

            $out[] = $payload;
        }
        return $out;
    }

    private function validateRow(array $p): array
    {
        $errs = [];
        if (empty($p['company_name'])) $errs[] = 'Company name missing';
        if (!empty($p['gst_no']) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[0-9A-Z]{1}Z[0-9A-Z]{1}$/', $p['gst_no'])) {
            $errs[] = 'GST format invalid';
        }
        if (!empty($p['pan_no']) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $p['pan_no'])) {
            $errs[] = 'PAN format invalid';
        }
        if (!empty($p['email']) && !filter_var($p['email'], FILTER_VALIDATE_EMAIL)) {
            $errs[] = 'Email format invalid';
        }
        if (!empty($p['mobile']) && (strlen($p['mobile']) < 7 || strlen($p['mobile']) > 15)) {
            $errs[] = 'Mobile length suspect';
        }
        return $errs;
    }

    /** Mark rows whose GST or (failing that) company_name already exists. */
    private function flagDuplicates(array $rows, string $type): array
    {
        $table = $type === 'clients' ? 'clients' : 'vendors';
        $db = \Config\Database::connect();

        $gsts  = array_values(array_filter(array_column($rows, 'gst_no')));
        $names = array_values(array_filter(array_column($rows, 'company_name')));

        $existingByGst  = [];
        $existingByName = [];

        // Use prepared-statement binds — bullet-proof against apostrophes
        // and unusual chars in vendor/client names ("VENDOR'S NAME", "M/s …").
        if ($gsts) {
            try {
                $ph = implode(',', array_fill(0, count($gsts), '?'));
                $found = $db->query("SELECT id, gst_no FROM `$table` WHERE gst_no IN ($ph)", $gsts)->getResultArray();
                foreach ($found as $f) $existingByGst[strtoupper($f['gst_no'])] = (int) $f['id'];
            } catch (\Throwable $e) {
                log_message('warning', 'flagDuplicates(gst) failed: ' . $e->getMessage());
            }
        }
        if ($names) {
            try {
                $ph = implode(',', array_fill(0, count($names), '?'));
                $found = $db->query("SELECT id, company_name FROM `$table` WHERE company_name IN ($ph)", $names)->getResultArray();
                foreach ($found as $f) $existingByName[strtolower($f['company_name'])] = (int) $f['id'];
            } catch (\Throwable $e) {
                log_message('warning', 'flagDuplicates(name) failed: ' . $e->getMessage());
            }
        }

        foreach ($rows as &$r) {
            if (!empty($r['gst_no']) && isset($existingByGst[strtoupper($r['gst_no'])])) {
                $r['_duplicate'] = ['by' => 'gst', 'id' => $existingByGst[strtoupper($r['gst_no'])]];
            } elseif (!empty($r['company_name']) && isset($existingByName[strtolower($r['company_name'])])) {
                $r['_duplicate'] = ['by' => 'name', 'id' => $existingByName[strtolower($r['company_name'])]];
            }
        }
        return $rows;
    }

    /**
     * Read the model's allowedFields array. CI 4.4+ has getAllowedFields(),
     * older versions don't — fall back to reflection on the protected property.
     */
    private function modelAllowedFields($model): array
    {
        if (method_exists($model, 'getAllowedFields')) {
            return (array) $model->getAllowedFields();
        }
        try {
            $ref = new \ReflectionClass($model);
            if ($ref->hasProperty('allowedFields')) {
                $prop = $ref->getProperty('allowedFields');
                $prop->setAccessible(true);
                return (array) $prop->getValue($model);
            }
        } catch (\Throwable $e) { /* fall through */ }
        return [];
    }

    private function insertClients(array $rows, array $picked): array
    {
        $model = new ClientModel();
        $allowed = $this->modelAllowedFields($model);
        $created = $updated = $skipped = 0;

        $action = (string) $this->request->getPost('on_duplicate'); // 'skip' or 'update'

        foreach ($picked as $i) {
            if (!isset($rows[$i])) { $skipped++; continue; }
            $r = $rows[$i];
            if (!empty($r['_errors']))      { $skipped++; continue; }
            if (empty($r['company_name'])) { $skipped++; continue; }

            $data = array_intersect_key($r, array_flip($allowed));

            if (!empty($r['_duplicate']['id'])) {
                if ($action === 'update') {
                    $data['updated_by'] = $this->auth->id();
                    $model->update($r['_duplicate']['id'], $data);
                    $updated++;
                } else {
                    $skipped++;
                }
                continue;
            }
            $data['client_code'] = $model->nextCode();
            $data['created_by']  = $this->auth->id();
            $data['status']      = 1;
            $model->insert($data);
            $created++;
        }
        return [$created, $updated, $skipped];
    }

    private function insertVendors(array $rows, array $picked): array
    {
        $model = new VendorModel();
        $cm    = new VendorContactModel();
        $allowed = $this->modelAllowedFields($model);
        $created = $updated = $skipped = 0;
        $action = (string) $this->request->getPost('on_duplicate');

        // Detect whether the new vendor_type column has actually been migrated
        // on this server. If not, silently drop it from the payload so the
        // INSERT doesn't blow up with "Unknown column 'vendor_type'".
        $db = \Config\Database::connect();
        $hasVendorType = false;
        try {
            $cols = $db->getFieldNames('vendors');
            $hasVendorType = in_array('vendor_type', $cols, true);
        } catch (\Throwable $e) { /* tolerate driver quirks — assume column absent */ }

        foreach ($picked as $i) {
            if (!isset($rows[$i])) { $skipped++; continue; }
            $r = $rows[$i];
            if (!empty($r['_errors']))     { $skipped++; continue; }
            if (empty($r['company_name'])) { $skipped++; continue; }

            $data = array_intersect_key($r, array_flip($allowed));
            // Strip vendor_type if the column hasn't been migrated yet.
            if (!$hasVendorType) unset($data['vendor_type']);

            // BROKER/FLEET OWNER column → vendors.vendor_type. We capture it as
            // sidecar in extractRows() under _broker_or_fleet; promote it here
            // so the value is preserved on insert/update.
            if ($hasVendorType && !empty($r['_broker_or_fleet']) && in_array('vendor_type', $allowed, true)) {
                $data['vendor_type'] = mb_substr(trim((string) $r['_broker_or_fleet']), 0, 60);
            }

            try {
                if (!empty($r['_duplicate']['id'])) {
                    if ($action === 'update') {
                        $data['updated_by'] = $this->auth->id();
                        $model->update($r['_duplicate']['id'], $data);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                    continue;
                }
                $data['vendor_code'] = $model->nextCode();
                $data['created_by']  = $this->auth->id();
                $data['status']      = 1;
                $id = $model->insert($data);

                if ($id) {
                    // Primary contact from CONTACT PERSON column if present
                    if (!empty($r['contact_name']) && !empty($r['mobile'])) {
                        try {
                            $cm->insert([
                                'vendor_id'    => (int) $id,
                                'contact_name' => $r['contact_name'],
                                'mobile'       => $r['mobile'],
                                'email'        => $r['email'] ?? null,
                                'is_primary'   => 1,
                            ]);
                        } catch (\Throwable $e) {
                            log_message('warning', 'vendor_contacts insert skipped: ' . $e->getMessage());
                        }
                    }
                    $this->insertVendorSidecars((int) $id, $r);
                }
                $created++;
            } catch (\Throwable $e) {
                log_message('error', 'Vendor row insert failed (' . ($r['company_name'] ?? '?') . '): ' . $e->getMessage());
                $skipped++;
            }
        }
        return [$created, $updated, $skipped];
    }

    /**
     * Persist the OPEN / CLOSED BODY / ORIGIN / DESTINATION sheet columns into
     * the related tables. Tolerates either table being absent (returns silently).
     */
    private function insertVendorSidecars(int $vendorId, array $row): void
    {
        $db = \Config\Database::connect();

        // Vehicle types — combine OPEN body + CLOSED body lists, dedupe.
        $vehicleSrc = trim(($row['_vehicle_open'] ?? '') . ',' . ($row['_vehicle_closed'] ?? '') . ',' . ($row['_vehicle_type_any'] ?? ''));
        if (trim($vehicleSrc, ', ') !== '' && $db->tableExists('vendor_vehicle_types')) {
            $seen = [];
            foreach (preg_split('/[,;\n\/]+/', $vehicleSrc) ?: [] as $vt) {
                $vt = trim($vt);
                if ($vt === '') continue;
                $key = strtoupper(preg_replace('/\s+/', '', $vt));
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                try {
                    $db->table('vendor_vehicle_types')->insert([
                        'vendor_id'    => $vendorId,
                        'vehicle_type' => mb_substr($vt, 0, 40),
                        'status'       => 1,
                    ]);
                } catch (\Throwable $e) { /* tolerate column shape differences */ }
            }
        }

        // Routes — origin + destination from the SERVICES columns
        $origin = trim((string) ($row['_route_origin'] ?? ''));
        $dest   = trim((string) ($row['_route_destination'] ?? ''));
        if (($origin !== '' || $dest !== '') && $db->tableExists('vendor_routes')) {
            try {
                $db->table('vendor_routes')->insert([
                    'vendor_id'   => $vendorId,
                    'pickup_city' => $origin !== '' ? mb_substr($origin, 0, 80) : null,
                    'drop_city'   => $dest   !== '' ? mb_substr($dest,   0, 80) : null,
                    'status'      => 1,
                ]);
            } catch (\Throwable $e) { /* tolerate column shape differences */ }
        }
    }

    // ── Tiny utilities ───────────────────────────────────────────────

    private static function digitsOnly(string $s): string
    {
        return (string) preg_replace('/\D+/', '', $s);
    }

    /**
     * Extract the first 7-15 digit phone number from a free-form string.
     * Splits on whitespace first so "9911065019 9911665930" yields "9911065019"
     * (not the concatenated 20-digit blob). "+91 99110 65019" → "919911065019".
     */
    private static function firstPhone(string $s): string
    {
        // Split on whitespace/commas/semicolons; take the first chunk with 7-15 digits.
        $parts = preg_split('/[\s,;\/]+/', trim($s)) ?: [];
        // First try: a token that's plainly a phone (digits + optional + - prefix)
        foreach ($parts as $p) {
            if (preg_match('/^\+?\d[\d\-]{6,19}$/', $p)) {
                return substr(preg_replace('/\D+/', '', $p), 0, 15);
            }
        }
        // Fallback: longest digit run in the original string, capped at 15
        if (preg_match_all('/\+?\d[\d\-]{6,19}/', $s, $m) && !empty($m[0])) {
            usort($m[0], fn($a, $b) => strlen($b) <=> strlen($a));
            return substr(preg_replace('/\D+/', '', $m[0][0]), 0, 15);
        }
        return substr(self::digitsOnly($s), 0, 15);
    }

    private function toCsvLine(array $cells): string
    {
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $cells);
        rewind($fh);
        $line = stream_get_contents($fh);
        fclose($fh);
        return (string) $line;
    }

    /** GST state code → state name. Subset; falls back to '' if unknown. */
    private static function stateFromGstPrefix(string $code): string
    {
        static $map = [
            '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab',
            '04' => 'Chandigarh', '05' => 'Uttarakhand', '06' => 'Haryana',
            '07' => 'Delhi', '08' => 'Rajasthan', '09' => 'Uttar Pradesh',
            '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
            '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram',
            '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam',
            '19' => 'West Bengal', '20' => 'Jharkhand', '21' => 'Odisha',
            '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
            '26' => 'Dadra and Nagar Haveli and Daman and Diu', '27' => 'Maharashtra',
            '28' => 'Andhra Pradesh (Old)', '29' => 'Karnataka', '30' => 'Goa',
            '31' => 'Lakshadweep', '32' => 'Kerala', '33' => 'Tamil Nadu',
            '34' => 'Puducherry', '35' => 'Andaman and Nicobar Islands',
            '36' => 'Telangana', '37' => 'Andhra Pradesh', '38' => 'Ladakh',
        ];
        return $map[$code] ?? '';
    }
}
