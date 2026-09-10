<?php

namespace App\Controllers;

/**
 * Universal search across the most-queried entities. Returns top hits per
 * module with a uniform shape so the Ctrl+K palette can render them.
 *
 *   GET /_search?q=mumbai      → JSON
 *
 * Each hit:
 *   { type, label, sub, url, badge? }
 */
class GlobalSearchController extends BaseController
{
    private const MAX_PER_TYPE = 5;

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '' || mb_strlen($q) < 2) {
            return $this->response->setJSON(['ok' => true, 'q' => $q, 'hits' => [], 'total' => 0]);
        }

        $db = \Config\Database::connect();
        $hits = [];

        // Clients
        if ($this->auth->can('clients', 'can_view')) {
            $rows = $db->table('clients')
                ->select('id, company_name, contact_name, mobile, city')
                ->groupStart()
                    ->like('company_name', $q)
                    ->orLike('contact_name', $q)
                    ->orLike('mobile', $q)
                    ->orLike('gst_no', $q)
                    ->orLike('client_code', $q)
                ->groupEnd()
                ->where('deleted_at', null)
                ->orderBy('company_name', 'ASC')->limit(self::MAX_PER_TYPE)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $hits[] = ['type' => 'client', 'icon' => 'people',
                    'label' => $r['company_name'],
                    'sub'   => trim(($r['contact_name'] ?? '') . ' · ' . ($r['mobile'] ?? '') . ' · ' . ($r['city'] ?? ''), ' ·'),
                    'url'   => site_url('clients/' . (int) $r['id'] . '/edit')];
            }
        }

        // Vendors
        if ($this->auth->can('vendors', 'can_view')) {
            $rows = $db->table('vendors')
                ->select('id, company_name, owner_name, mobile, city, vendor_code')
                ->groupStart()
                    ->like('company_name', $q)
                    ->orLike('owner_name', $q)
                    ->orLike('mobile', $q)
                    ->orLike('gst_no', $q)
                    ->orLike('vendor_code', $q)
                ->groupEnd()
                ->where('deleted_at', null)
                ->orderBy('company_name', 'ASC')->limit(self::MAX_PER_TYPE)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $hits[] = ['type' => 'vendor', 'icon' => 'truck',
                    'label' => $r['company_name'],
                    'sub'   => trim(($r['owner_name'] ?? '') . ' · ' . ($r['mobile'] ?? '') . ' · ' . ($r['city'] ?? ''), ' ·'),
                    'url'   => site_url('vendors/' . (int) $r['id'] . '/edit')];
            }
        }

        // Trips
        if ($this->auth->can('trips', 'can_view')) {
            $rows = $db->table('trips t')
                ->select('t.id, t.trip_no, t.lr_no, t.current_status, t.vehicle_number, t.driver_name, b.route_text')
                ->join('bookings b', 'b.id = t.booking_id', 'left')
                ->groupStart()
                    ->like('t.trip_no', $q)
                    ->orLike('t.lr_no', $q)
                    ->orLike('t.vehicle_number', $q)
                    ->orLike('t.driver_name', $q)
                ->groupEnd()
                ->where('t.deleted_at', null)
                ->orderBy('t.id', 'DESC')->limit(self::MAX_PER_TYPE)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $hits[] = ['type' => 'trip', 'icon' => 'truck-flatbed',
                    'label' => $r['trip_no'] . ($r['lr_no'] ? ' · LR ' . $r['lr_no'] : ''),
                    'sub'   => ($r['route_text'] ?? '') . ' · ' . $r['current_status'],
                    'badge' => $r['current_status'],
                    'url'   => site_url('trips/' . (int) $r['id'])];
            }
        }

        // Bookings
        if ($this->auth->can('bookings', 'can_view')) {
            $rows = $db->table('bookings b')
                ->select('b.id, b.booking_no, b.route_text, b.booking_status, c.company_name AS client_company')
                ->join('clients c', 'c.id = b.client_id', 'left')
                ->groupStart()
                    ->like('b.booking_no', $q)
                    ->orLike('b.route_text', $q)
                    ->orLike('c.company_name', $q)
                ->groupEnd()
                ->where('b.deleted_at', null)
                ->orderBy('b.id', 'DESC')->limit(self::MAX_PER_TYPE)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $hits[] = ['type' => 'booking', 'icon' => 'card-list',
                    'label' => $r['booking_no'],
                    'sub'   => trim(($r['client_company'] ?? '') . ' · ' . ($r['route_text'] ?? ''), ' ·'),
                    'badge' => $r['booking_status'],
                    'url'   => site_url('bookings/' . (int) $r['id'])];
            }
        }

        // Invoices
        if ($this->auth->can('invoices', 'can_view')) {
            $rows = $db->table('invoices i')
                ->select('i.id, i.invoice_no, i.total_amount, i.balance_due, i.invoice_status, c.company_name AS client_company')
                ->join('clients c', 'c.id = i.client_id', 'left')
                ->groupStart()
                    ->like('i.invoice_no', $q)
                    ->orLike('i.irn_no', $q)
                    ->orLike('c.company_name', $q)
                ->groupEnd()
                ->where('i.deleted_at', null)
                ->orderBy('i.id', 'DESC')->limit(self::MAX_PER_TYPE)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $hits[] = ['type' => 'invoice', 'icon' => 'receipt',
                    'label' => $r['invoice_no'] . ' · ₹' . number_format((float) $r['total_amount'], 0),
                    'sub'   => ($r['client_company'] ?? '') . ' · balance ₹' . number_format((float) $r['balance_due'], 0),
                    'badge' => $r['invoice_status'],
                    'url'   => site_url('invoices/' . (int) $r['id'])];
            }
        }

        // Leads
        if ($this->auth->can('leads', 'can_view')) {
            $rows = $db->table('leads')
                ->select('id, lead_no, company_name, client_name, mobile, pickup_city, drop_city, current_status')
                ->groupStart()
                    ->like('lead_no', $q)
                    ->orLike('company_name', $q)
                    ->orLike('client_name', $q)
                    ->orLike('mobile', $q)
                ->groupEnd()
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')->limit(self::MAX_PER_TYPE)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $hits[] = ['type' => 'lead', 'icon' => 'lightning-charge',
                    'label' => $r['lead_no'] . ' · ' . ($r['company_name'] ?: $r['client_name']),
                    'sub'   => trim(($r['pickup_city'] ?? '') . ' → ' . ($r['drop_city'] ?? '') . ' · ' . $r['current_status'], ' →·'),
                    'badge' => $r['current_status'],
                    'url'   => site_url('leads/' . (int) $r['id'])];
            }
        }

        // RFQs
        if ($this->auth->can('rfq', 'can_view')) {
            $rows = $db->table('rfq_master')
                ->select('id, rfq_no, masked_reference, pickup_city, drop_city, status')
                ->groupStart()
                    ->like('rfq_no', $q)
                    ->orLike('masked_reference', $q)
                    ->orLike('pickup_city', $q)
                    ->orLike('drop_city', $q)
                ->groupEnd()
                ->orderBy('id', 'DESC')->limit(self::MAX_PER_TYPE)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $hits[] = ['type' => 'rfq', 'icon' => 'file-text',
                    'label' => $r['rfq_no'],
                    'sub'   => trim(($r['pickup_city'] ?? '') . ' → ' . ($r['drop_city'] ?? ''), ' →') . ' · ' . $r['status'],
                    'badge' => $r['status'],
                    'url'   => site_url('rfq/' . (int) $r['id'])];
            }
        }

        return $this->response->setJSON(['ok' => true, 'q' => $q, 'hits' => $hits, 'total' => count($hits)]);
    }
}
