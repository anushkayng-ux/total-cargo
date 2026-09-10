<?php

namespace App\Controllers;

use App\Models\VendorModel;
use App\Models\VendorContactModel;

class VendorsController extends BaseController
{
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));
        $model  = new VendorModel();
        $query  = $model->select('vendors.*,
                  (SELECT contact_name FROM vendor_contacts c WHERE c.vendor_id = vendors.id ORDER BY c.is_primary DESC, c.id ASC LIMIT 1) AS primary_contact_name,
                  (SELECT mobile       FROM vendor_contacts c WHERE c.vendor_id = vendors.id ORDER BY c.is_primary DESC, c.id ASC LIMIT 1) AS primary_contact_mobile,
                  (SELECT designation  FROM vendor_contacts c WHERE c.vendor_id = vendors.id ORDER BY c.is_primary DESC, c.id ASC LIMIT 1) AS primary_contact_role,
                  (SELECT COUNT(*)     FROM vendor_contacts c WHERE c.vendor_id = vendors.id) AS contact_count', false)
            ->orderBy('vendors.id', 'DESC');
        if ($search !== '') {
            $query->groupStart()
                ->like('company_name', $search)
                ->orLike('owner_name', $search)
                ->orLike('mobile', $search)
                ->orLike('whatsapp_no', $search)
                ->orLike('vendor_code', $search)
                ->groupEnd();
        }
        return $this->render('vendors/index', [
            'pageTitle' => 'Vendors',
            'rows'      => $query->paginate(20),
            'pager'     => $model->pager,
            'search'    => $search,
        ]);
    }

    public function create()
    {
        return $this->render('vendors/form', [
            'pageTitle' => 'Add Vendor',
            'row'       => null,
            'contacts'  => [],
        ]);
    }

    public function store()
    {
        $model = new VendorModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // Validate contacts BEFORE inserting the vendor
        $contactErr = $this->validateContactsPayload();
        if ($contactErr) return redirect()->back()->withInput()->with('error', $contactErr);

        $data = $this->request->getPost();
        $data['vendor_code']    = !empty($data['vendor_code']) ? $data['vendor_code'] : $model->nextCode();
        $data['created_by']     = $this->auth->id();
        $data['status']         = !empty($data['status']) ? 1 : 0;
        $data['is_preferred']   = !empty($data['is_preferred']) ? 1 : 0;
        $data['is_blacklisted'] = !empty($data['is_blacklisted']) ? 1 : 0;
        $id = $model->insert($data);

        $this->saveContacts((int) $id);

        // Notify everyone who manages vendors (Purchase) + admin oversight.
        \App\Libraries\Notify::toPermission('vendors', 'can_view',
            'New vendor — ' . ($data['company_name'] ?? ('#' . $id)),
            trim((string) ($data['vendor_type'] ?? '')) ?: null,
            site_url('vendors/' . (int) $id),
            ['type' => 'vendor_new', 'icon' => 'truck']);

        return redirect()->to(site_url('vendors'))->with('success', 'Vendor added.');
    }

    public function edit(int $id)
    {
        $row = (new VendorModel())->find($id);
        if (!$row) return redirect()->to(site_url('vendors'))->with('error', 'Not found.');
        $db = \Config\Database::connect();
        $routes = $db->table('vendor_routes')
            ->where('vendor_id', $id)->where('status', 1)
            ->orderBy('id', 'ASC')->get()->getResultArray();
        // Split into the two simple chip sets the UI exposes:
        //  - drop cities the vendor serves (pickup IS NULL)
        //  - pickup cities the vendor operates from (drop IS NULL)
        //  - explicit pairs (both set) kept as a third "advanced" group
        $dropCities = []; $pickupCities = []; $pairs = [];
        foreach ($routes as $r) {
            if (empty($r['pickup_city']) && !empty($r['drop_city']))      $dropCities[]   = $r['drop_city'];
            elseif (!empty($r['pickup_city']) && empty($r['drop_city']))  $pickupCities[] = $r['pickup_city'];
            elseif (!empty($r['pickup_city']) && !empty($r['drop_city']))$pairs[] = $r;
        }
        return $this->render('vendors/form', [
            'pageTitle'    => 'Edit Vendor',
            'row'          => $row,
            'contacts'     => (new VendorContactModel())->forVendor($id),
            'dropCities'   => array_values(array_unique($dropCities)),
            'pickupCities' => array_values(array_unique($pickupCities)),
            'pairs'        => $pairs,
        ]);
    }

    public function update(int $id)
    {
        $model = new VendorModel();
        if (!$this->validate($model->getValidationRules())) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $contactErr = $this->validateContactsPayload();
        if ($contactErr) return redirect()->back()->withInput()->with('error', $contactErr);

        $data = $this->request->getPost();
        $data['updated_by']     = $this->auth->id();
        $data['status']         = !empty($data['status']) ? 1 : 0;
        $data['is_preferred']   = !empty($data['is_preferred']) ? 1 : 0;
        $data['is_blacklisted'] = !empty($data['is_blacklisted']) ? 1 : 0;
        $model->update($id, $data);

        $this->saveContacts($id);
        $this->saveRoutes($id);
        return redirect()->to(site_url('vendors'))->with('success', 'Vendor updated.');
    }

    /**
     * Replace the vendor's route coverage from POST. Accepts three field shapes:
     *   route_drop_cities   = comma-separated drops the vendor serves from any origin
     *   route_pickup_cities = comma-separated origins from which the vendor goes anywhere
     *   route_pairs[]       = explicit "pickup→drop" pairs (advanced)
     */
    private function saveRoutes(int $vendorId): void
    {
        $db = \Config\Database::connect();
        $db->table('vendor_routes')->where('vendor_id', $vendorId)->delete();

        $rows = [];
        $splitChips = function ($raw): array {
            $raw = (string) $raw;
            if ($raw === '') return [];
            $parts = preg_split('/[,\n]+/', $raw) ?: [];
            $out = [];
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') $out[strtolower($p)] = mb_substr($p, 0, 80);
            }
            return array_values($out);
        };
        $cityModel = new \App\Models\CityModel();
        $resolveId = function (?string $name) use ($cityModel): ?int {
            if (!$name) return null;
            $hit = $cityModel->resolve($name);
            return $hit ? (int) $hit['id'] : null;
        };

        foreach ($splitChips($this->request->getPost('route_drop_cities')) as $city) {
            $rows[] = ['vendor_id' => $vendorId, 'pickup_city' => null, 'pickup_city_id' => null, 'drop_city' => $city, 'drop_city_id' => $resolveId($city), 'status' => 1];
        }
        foreach ($splitChips($this->request->getPost('route_pickup_cities')) as $city) {
            $rows[] = ['vendor_id' => $vendorId, 'pickup_city' => $city, 'pickup_city_id' => $resolveId($city), 'drop_city' => null, 'drop_city_id' => null, 'status' => 1];
        }
        $pairs = (array) ($this->request->getPost('route_pairs') ?? []);
        foreach ($pairs as $p) {
            $pk = trim((string) ($p['pickup_city'] ?? ''));
            $dr = trim((string) ($p['drop_city'] ?? ''));
            if ($pk === '' && $dr === '') continue;
            $rows[] = ['vendor_id' => $vendorId,
                       'pickup_city'    => $pk !== '' ? mb_substr($pk, 0, 80) : null,
                       'pickup_city_id' => $pk !== '' ? $resolveId($pk) : null,
                       'drop_city'      => $dr !== '' ? mb_substr($dr, 0, 80) : null,
                       'drop_city_id'   => $dr !== '' ? $resolveId($dr) : null,
                       'status' => 1];
        }
        if ($rows) $db->table('vendor_routes')->insertBatch($rows);
    }

    public function delete(int $id)
    {
        (new VendorModel())->delete($id);
        return redirect()->to(site_url('vendors'))->with('success', 'Vendor removed.');
    }

    // ── Contacts handling ─────────────────────────────────────────────

    /**
     * Validates the posted contacts array. Returns null on success, or an
     * error string. Rules:
     *  - Each row must have a name AND mobile.
     *  - Mobile is unique within this vendor's contacts (case-insensitive, digits only).
     *  - Email, if provided, must be valid.
     */
    private function validateContactsPayload(): ?string
    {
        $names = (array) $this->request->getPost('contact_name');
        $mobs  = (array) $this->request->getPost('contact_mobile');
        $emails= (array) $this->request->getPost('contact_email');

        $seen = [];
        $rowsWithData = 0;
        for ($i = 0, $n = count($names); $i < $n; $i++) {
            $name = trim((string) $names[$i]);
            $mob  = preg_replace('/\D+/', '', (string) ($mobs[$i] ?? ''));
            $em   = trim((string) ($emails[$i] ?? ''));
            if ($name === '' && $mob === '' && $em === '') continue; // empty row, skip

            $rowsWithData++;
            if ($name === '') return 'Contact #' . ($i + 1) . ': name is required.';
            if ($mob === '')  return 'Contact #' . ($i + 1) . ': mobile is required.';
            if (strlen($mob) < 7 || strlen($mob) > 15) return 'Contact #' . ($i + 1) . ': mobile must be 7–15 digits.';
            if ($em !== '' && !filter_var($em, FILTER_VALIDATE_EMAIL)) {
                return 'Contact #' . ($i + 1) . ": email '$em' is not valid.";
            }
            if (isset($seen[$mob])) {
                return 'Duplicate mobile ' . $mob . ' on rows ' . $seen[$mob] . ' and ' . ($i + 1) . '.';
            }
            $seen[$mob] = $i + 1;
        }
        return null;
    }

    /** Bulk-replace contacts on the given vendor from the posted arrays. */
    private function saveContacts(int $vendorId): void
    {
        $names   = (array) $this->request->getPost('contact_name');
        $roles   = (array) $this->request->getPost('contact_designation');
        $mobs    = (array) $this->request->getPost('contact_mobile');
        $emails  = (array) $this->request->getPost('contact_email');
        $primary = (string) $this->request->getPost('contact_primary');  // index of the primary row

        $cm = new VendorContactModel();
        $cm->where('vendor_id', $vendorId)->delete();

        $kept = 0;
        for ($i = 0, $n = count($names); $i < $n; $i++) {
            $name = trim((string) $names[$i]);
            $mob  = preg_replace('/\D+/', '', (string) ($mobs[$i] ?? ''));
            if ($name === '' || $mob === '') continue;
            $cm->insert([
                'vendor_id'    => $vendorId,
                'contact_name' => $name,
                'designation'  => trim((string) ($roles[$i] ?? '')) ?: null,
                'mobile'       => $mob,
                'email'        => trim((string) ($emails[$i] ?? '')) ?: null,
                'is_primary'   => ($primary !== '' && (int) $primary === $i) ? 1 : 0,
                'notes'        => null,
            ]);
            $kept++;
        }

        // If no row was marked primary but at least one contact exists, set the first as primary
        if ($kept > 0) {
            $hasPrimary = $cm->where('vendor_id', $vendorId)->where('is_primary', 1)->countAllResults();
            if ($hasPrimary === 0) {
                $first = $cm->where('vendor_id', $vendorId)->orderBy('id', 'ASC')->first();
                if ($first) $cm->update($first['id'], ['is_primary' => 1]);
            }
        }
    }
}
