<?php

namespace App\Models;

class BookingModel extends BaseModel
{
    protected $table         = 'bookings';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'booking_no', 'lead_id', 'client_id', 'vendor_id',
        'final_buy_rate', 'final_sell_rate', 'margin_amount',
        'lr_no',
        'route_text', 'pickup_city', 'drop_city', 'vehicle_type', 'vehicle_count', 'vehicle_number', 'load_details', 'loading_date',
        'billing_party',
        'consignee_name', 'consignee_mobile', 'consignee_address', 'consignee_gstin', 'freight_mode',
        'consignor_client_id', 'consignee_client_id',
        'consignor_name', 'consignor_mobile', 'consignor_address', 'consignor_gstin', 'consignor_state',
        // Docket / LR fields
        'packages_count', 'packing_method', 'actual_weight_kg', 'charge_weight_kg',
        'dim_length_cm', 'dim_width_cm', 'dim_height_cm',
        'additional_charges', 'other_charges', 'gst_amount', 'service_tax_amount',
        'invoice_number', 'shipper_invoices_json', 'bill_of_entry', 'bl_number', 'container_number', 'seal_number',
        'person_liable_gst', 'cargo_value_inr',
        'particulars_text', 'driver_mobile', 'ewb_no',
        'instructions', 'booking_status',
        'approved_by', 'approved_at',
        'created_by', 'updated_by', 'assigned_to', 'assigned_by',
    ];

    public const STATUSES = ['Pending', 'Approved', 'Handed Over', 'Completed', 'Cancelled'];

    public function withJoins()
    {
        return $this->select('bookings.*, clients.company_name AS client_company, vendors.company_name AS vendor_company, leads.lead_no, u.name AS created_by_name')
            ->join('clients', 'clients.id = bookings.client_id', 'left')
            ->join('vendors', 'vendors.id = bookings.vendor_id', 'left')
            ->join('leads',   'leads.id   = bookings.lead_id',   'left')
            ->join('users u', 'u.id       = bookings.created_by','left');
    }
}
