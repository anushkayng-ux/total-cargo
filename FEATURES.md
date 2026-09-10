# TPT Aggregator — Feature Specification (v1.1)

> **Transport Aggregator CRM · Procurement · Operations · Billing · Tracking · Analytics Platform**
>
> A WhatsApp-native, GST-ready, mobile-first operations platform for Indian road-freight aggregators. Built on CodeIgniter 4 + MySQL/MariaDB with Poppins / Bootstrap 5. Single-app architecture, 37 tables, 3 queue-safe integrations (Meta WhatsApp Cloud API, LocoNav GPS, ClearTax GSP for E-Invoice + E-Way Bill).

**Default access**: [http://localhost/tpt/public/](http://localhost/tpt/public/) · `admin@tpt.local` / `admin@123`

---

## Table of Contents

1. [Platform Foundation](#1-platform-foundation)
2. [Authentication, Roles & Permissions](#2-authentication-roles--permissions)
3. [Masters](#3-masters)
4. [CRM — Leads](#4-crm--leads)
5. [Procurement — RFQ & Quotations](#5-procurement--rfq--quotations)
6. [WhatsApp Automation](#6-whatsapp-automation-meta-cloud-api)
7. [Operations — Bookings & Trips](#7-operations--bookings--trips)
8. [Dispatch Pack](#8-dispatch-pack-5-auto-generated-documents)
9. [E-Way Bill Integration](#9-e-way-bill-integration)
10. [Trip Expenses](#10-trip-expenses)
11. [GPS & Fleet Tracking](#11-gps--fleet-tracking-loconav)
12. [Billing — Invoices, Receipts, E-Invoice](#12-billing--invoices-receipts-e-invoice)
13. [Vendor Bills & Payments](#13-vendor-bills--payments)
14. [Documents Module](#14-documents-module)
15. [Audit Logs](#15-audit-logs)
16. [Dashboards, Reports, Vendor Scoring](#16-dashboards-reports--vendor-scoring)
17. [Numbering Scheme](#17-numbering-scheme)
18. [Navigation & UX](#18-navigation--ux)
19. [Integrations](#19-integrations)
20. [Scheduled Jobs](#20-scheduled-jobs)
21. [Demo Data Seeder](#21-demo-data-seeder)
22. [Technology Stack](#22-technology-stack)
23. [Folder Structure](#23-folder-structure)
24. [Setup & Running](#24-setup--running)
25. [What's Awaiting Credentials](#25-whats-awaiting-credentials)

---

## 1. Platform Foundation

| Item | Detail |
|---|---|
| Framework | CodeIgniter 4.7.2 (single-app) |
| PHP | 8.2+ |
| Database | MariaDB 10.4 / MySQL 8 |
| Tables | **37** (13 migration files) |
| Front-end | Bootstrap 5.3 + Bootstrap Icons 1.11 + Poppins (Google Fonts) |
| Charts | Chart.js 4.4 |
| Maps | Leaflet 1.9 + OpenStreetMap (no API key required) |
| PDF | dompdf 3.1 (for invoices + dispatch-pack documents) |
| Theme | White background · black foreground · grey button palette |
| Design language | Mobile-first · every table collapses to card rows below 768 px |
| Session handling | CI4 FileHandler (configurable to DB via seeded `ci_sessions`) |
| Security | CSRF globally enabled · bcrypt password hashing · upload storage outside web root · controlled download handlers · soft-delete on all business entities |

---

## 2. Authentication, Roles & Permissions

### Login
- Email + password form with a clear "default admin" hint in development
- Re-login required on IP / cookie change (CI4 session defaults)
- Last-login timestamp + last-login IP captured on users
- `auth` and `guest` route filters keep authenticated and public routes separate

### RBAC Matrix

| Item | Detail |
|---|---|
| **Roles** | 9 seeded: Administrator · Management · CRM Executive · CRM Manager · Purchase Executive · Purchase Manager · Operations Executive · Operations Manager · Accounts |
| **Permission modules** | 23: dashboard · users · roles · clients · vendors · drivers · vehicles · lead_sources · leads · rfq · quotations · bookings · trips · gps · invoices · receipts · vendor_bills · vendor_payments · documents · whatsapp · reports · settings · audit_logs |
| **Action flags per module** | 6: `can_view` · `can_add` · `can_edit` · `can_delete` · `can_approve` · `can_export` |
| **Effective permission matrix** | 9 × 23 × 6 = **1,242 individual switches** |
| **Session-cached** | Permission map loaded on login; `auth:<module>` filter checks the cache per request — negligible overhead |
| **Live refresh** | Editing a role immediately refreshes the active user's session map |
| **Self-protection** | A user cannot delete their own account |

### Screens

| Route | What it does |
|---|---|
| `/users` | List (search, paginate) + Add / Edit / Delete / Reset-password |
| `/roles` | List roles |
| `/roles/:id/permissions` | Full 23 × 6 checkbox matrix |
| `/settings` | Multi-group settings editor (Company · Billing · Numbering) |

---

## 3. Masters

| Master | Key fields | Notes |
|---|---|---|
| **Clients** | code (auto), company, contact, mobile, alt-mobile, email, GSTIN, PAN, address, city, state, pincode, credit limit, credit days, status | `client_code` auto-generated `CL#####` |
| **Vendors** | code (auto), company, owner, mobile, WhatsApp no, email, bank details, GSTIN, PAN, rating (0–5 decimal), **is_preferred**, **is_blacklisted** | Rating + flags drive the RFQ ranking engine |
| **Vendor routes** | pickup_city, drop_city | Per-vendor (many-to-many with vendors) |
| **Vendor vehicle types** | vehicle_type | Per-vendor (many-to-many) |
| **Drivers** | name, mobile, license no + expiry, linked vendor | License expiry ready for alerting |
| **Vehicles** | registration, type, RC, permit, insurance no + expiry, fitness expiry | Unique constraint on `vehicle_number` |
| **Lead sources** | name, key (unique), status | 7 seeded: web, WhatsApp, call, referral, marketplace, social, manual |
| **WhatsApp templates** | key, audience (client/vendor/internal/driver), meta name, language, body, variables | 8 seeded covering RFQ / quote / placed / transit / delivered / POD reminder / invoice / payment reminder |
| **Trip expense categories** | name, default_is_billable, sort order, status | **15 seeded** across internal (toll, bhatta, fuel, parking, maamul) and billable (loading, unloading, detention, weighbridge, escort, multi-point) |
| **Settings** | key, value, group (company · billing · numbering) | Every numbering prefix lives here — run-time editable |

---

## 4. CRM — Leads

### Capture
- 20+ fields: source, route (pickup/drop city+state), vehicle type, material, weight/unit, priority (Low/Normal/High/Urgent), expected dispatch, assignee, remarks
- Either an existing client can be picked OR an ad-hoc company name + contact recorded
- Unique sequential `LD#####` number generated on save

### Workflow — 9 Statuses

```
New → Under Review → Sent to Purchase → Quote Received →
Sent to Client → Negotiation → Won / Lost / Closed
```

- **Status history** written automatically on every transition (actor + remarks + timestamp)
- Lost reason captured
- Inline change-status widget with remarks + optional lost reason

### Follow-ups Timeline
- Each follow-up: datetime, type (Call / WhatsApp / Email / Meeting / Note), discussion notes, optional next-follow-up datetime
- Sorted newest first; each entry shows the author and timestamp

### List Page Filters
- Free-text search (no/client/mobile/city)
- Status dropdown
- Assignee dropdown (active users only)
- Source dropdown
- Date range (from / to)

### Lead → RFQ
- One-click **Create RFQ** button on lead detail pre-fills route, vehicle, material, weight, loading date

---

## 5. Procurement — RFQ & Quotations

### RFQ Creation
- Opaque 32-bit random **masked reference** (`REF-XXXXXXXX`) generated automatically
- **Vendors are never shown the lead number, client name, client mobile, GST number, or any other client identifier**
- Vendor suggestion query ranked by:
  - Preferred flag (descending)
  - Rating (descending)
  - Route match (vendor_routes table)
  - Vehicle-type match (vendor_vehicle_types table)
  - Blacklisted vendors excluded
- Multi-select vendor grid on the create page

### WhatsApp Dispatch
- Bulk send to all selected vendors using the `rfq_vendor` template with 6 variables:
  - `{{1}}` masked reference
  - `{{2}}` route
  - `{{3}}` vehicle type
  - `{{4}}` material category
  - `{{5}}` weight + unit
  - `{{6}}` loading date
- Per-vendor response status tracked (Pending / Sent / Queued / Received / Failed)
- **Remind non-responders** action with reminder counter
- Queue-safe when WhatsApp credentials are blank — payload logged, nothing sent

### Quotation Capture
- **Automatic via WhatsApp webhook**: incoming vendor message → masked reference regex match → vendor lookup (by WA number / mobile with country-code tolerance) → INR amount regex with currency & k/l/lac suffix support → `quotations` row inserted with `response_source='whatsapp'` and computed `response_time_minutes`
- **Manual entry** form for phone/email responses
- Auto-promotes RFQ status: `Open → In Progress → Quote Received → Shortlisted → Awarded`

### Quotation Ranking Engine
Deterministic composite score (lower = better):

```
score  = normalized_price_index
       − (rating × 0.05, cap 0.25)     # rating bonus up to 5★
       − 0.05 if preferred
       + 1.0 if blacklisted
       + (transit_days × 0.02, cap 0.30)
       + (response_time_minutes × 0.00005, cap 0.10)
```

Each quote card shows the score + price index for transparency.

### Shortlist & Award
- **Shortlist** quotations (any count)
- **Award** one — writes a `quotation_comparison_logs` entry containing the full comparison snapshot (every quote's amount/rating/transit/source at the moment of award) so decisions are auditable
- Lead status auto-advances to `Sent to Client` when first quote received

---

## 6. WhatsApp Automation (Meta Cloud API)

### Provider-agnostic Service Layer

`App\Libraries\WhatsAppService`

| Method | Purpose |
|---|---|
| `sendTemplate()` | Named-template delivery with positional `{{N}}` variables |
| `sendText()` | Free-text messages (inside 24-hr customer care window) |
| `sendMediaUrl()` | Document / image / audio / video messages |
| `renderLocalTemplate()` | Local variable substitution for preview + 24-hr window text fallback |
| `downloadMedia()` | Fetches incoming media bytes by Meta media ID |
| `isConfigured()` | Gate used by UIs to show "API not set" warnings |
| `normalizeNumber()` | E.164 normalisation for Indian mobile numbers |

### Outgoing Logs (`whatsapp_logs`)
- Every outgoing message stored with: module + ref, recipient, template key, message type, delivery status (Queued / Sent / Delivered / Read / Failed), request + response payloads, error message, retry count, all delivery timestamps
- Admin screen at `/whatsapp/logs` with pagination and status filtering

### Webhook (`POST /webhooks/whatsapp`)
- Meta GET verification handshake (verify token configurable in `.env`)
- POST handler parses:
  - `messages[]` — text, image, document, audio, video, button, interactive
  - `statuses[]` — delivery status updates matched by `provider_message_id`
- Incoming stored in `whatsapp_incoming_messages` with auto-linked RFQ + vendor
- **Auto-captures quotations** when incoming message has a masked ref + vendor match + detectable INR amount

### Templates Admin (`/whatsapp/templates`)
- Inline CRUD with audience-type and language selectors
- Body editor with `{{1}}` … `{{N}}` variable documentation
- 8 seeded templates — must be created with the same `template_name` on Meta Business Manager

---

## 7. Operations — Bookings & Trips

### Bookings

| Field group | Fields |
|---|---|
| Identity | booking_no (auto), lead_id, client_id, vendor_id |
| Commercials | final_buy_rate, final_sell_rate, **margin_amount (auto-computed)** |
| Route | route_text, vehicle_type, load_details, loading_date |
| Billing | billing_party, **freight_mode** (To Pay / Paid / To Be Billed) |
| **Consignee** (new) | consignee_name, consignee_mobile, consignee_address, consignee_gstin |
| Instructions | free text |
| Lifecycle | booking_status (Pending / Approved / Handed Over / Completed / Cancelled), approved_by, approved_at |

- **Approve** → booking locks, parent lead auto-advances to `Won` + history logged
- **Handover** → auto-creates the Trip with route auto-split into loading/unloading points
- **Cancel** at any time (with confirm)

### Trips — 10-State Lifecycle

```
Booking Created → Vehicle Placed → Loading → In Transit →
Arrived → Unloading → Delivered → POD Received → Closed
                (or Cancelled at any point)
```

- Auto-timestamps: `dispatch_datetime` stamped on `In Transit`; `delivery_datetime` stamped on `Delivered`
- Status history written automatically on every transition
- Closing a trip auto-completes the parent booking

### Trip Actions (all on the trip detail page)

| Action | Result |
|---|---|
| **Assign driver & vehicle** | Pick from master OR type free text; canonical values auto-populate from masters |
| **Change status** | With optional notes + delay reason |
| **Upload POD** | PDF / image up to 10 MB → file stored under `writable/uploads/trips/<id>/`, `documents` row created, status auto-advances to `POD Received` |
| **Notify client on WA** | 3 one-click buttons: Vehicle Placed · In Transit · Delivered (each uses its approved template) |
| **Live Map** | Opens `/gps/trip/:id` with trail + latest fix |
| **Dispatch Pack** | Opens the 5-document hub (see §8) |
| **Create Invoice** | Opens invoice form pre-populated from booking + any unbilled billable expenses (see §10) |
| **Vendor Bill** | Opens vendor-bill form pre-filled to this trip |

---

## 8. Dispatch Pack (5 Auto-generated Documents)

Every trip gets a **unique LR number** (configurable prefix) assigned lazily on first view. Five A4-formatted documents are auto-generated, all print-friendly HTML and PDF:

| # | Document | Contents |
|---|---|---|
| 1 | **Lorry Receipt (LR / Bilty)** | Consignor + Consignee panels, route grid, vehicle/driver, freight mode stamp, goods grid, freight grid, T&Cs, 3 signature blocks, **EWB number + validity when present** |
| 2 | **Trip Sheet (internal)** | Booking + LR refs, pickup/drop with full contacts, load details, vendor buy-rate *(internal copy)*, emergency escalation |
| 3 | **Loading Advice** | Formal letter to consignor's loading supervisor with document-handover checklist (Tax Invoice, EWB, Packing List, Weighment Slip) |
| 4 | **POD Blank** | Pre-filled LR side + blank Consignee Acknowledgement with shortage/damage/remarks/signature boxes |
| 5 | **Gate Pass** | Vehicle + driver + load + IN/OUT/KM blocks + security signature |

### Hub Page (`/trips/:id/dispatch`)

- Sticky top toolbar with:
  - **Select documents ▾** dropdown (5 checkboxes, All / None quick buttons, count pill `N / 5`)
  - **Print** — browser-native A4 print, hidden sections skipped via `@media print`
  - **Download PDF** — combined PDF of selected subset
- Every document also has its own **Open in new tab** and **Download PDF** link
- Filename is descriptive: `DISPATCH_PACK_TR00001_LR_POD_BLANK.pdf`
- Excluded docs fade out with a yellow "Excluded from print / PDF" ribbon so selection is unambiguous
- Selection persists per trip in `localStorage` (key `tpt_dispatch_sel_{trip_id}`)

### Backend Routes

```
GET   /trips/:id/dispatch                      # hub viewer
GET   /trips/:id/dispatch/pack.pdf              # combined PDF (optional ?types=lr,pod-blank)
GET   /trips/:id/dispatch/:type                 # single HTML
GET   /trips/:id/dispatch/:type.pdf             # single PDF
POST  /trips/:id/dispatch/lr/generate           # manual LR issue
```

`:type` ∈ `{lr, trip-sheet, loading-advice, pod-blank, gate-pass}`

---

## 9. E-Way Bill Integration

Indian consignments >₹50K require an EWB generated on the NIC portal or via a GSP (GST Suvidha Provider). TPT integrates with the NIC IRP schema — same endpoint family used by the ClearTax e-invoice adapter.

### Schema

- `trips.ewb_no` · `ewb_date` · `ewb_valid_until` · `ewb_status` (None / Active / Cancelled)
- `ewb_logs` — every generate/fetch/cancel/manual action captures request + response + status

### EWB Card on Trip Page (`#ewb` anchor)

| State | What's shown |
|---|---|
| **No EWB yet** | Two side-by-side options: **Generate via API** (with distance-km input) · **Or enter EWB manually** (paste number + validity) |
| **Active EWB** | Number, generated-at, valid-until, pill `Active` (or red `Expired`) + Refresh / Cancel / Clear buttons |
| **Not configured** | Orange pill "API keys not set — use Manual Entry" |

### API Calls (via `App\Libraries\EwayBillService`)

| Action | HTTP | Endpoint |
|---|---|---|
| Generate | `POST` | `/ewayapi/v1.03/ewayapi` |
| Fetch | `GET` | `/ewayapi/v1.03/ewayapi?action=GetEwayBill&ewbNo=<no>` |
| Cancel | `POST` | `/ewayapi/v1.03/ewayapi?action=CanEWB` |

Authentication headers:

```
Authorization: Bearer <cleartax.apiKey>
Content-Type: application/json
gstin: <cleartax.gstin>
username: <ewb.gspUserName>         # optional
password: <ewb.gspPassword>         # optional
```

### Payload Shape (NIC schema, abbreviated)

```json
{
  "supplyType": "O", "subSupplyType": "1", "docType": "INV",
  "docNo": "LR00001", "docDate": "23/04/2026",
  "fromGstin": "...", "fromTrdName": "...", "fromStateCode": 0,
  "toGstin": "...",   "toTrdName": "...",   "toStateCode": 0,
  "totalValue": 55000, "cgstValue": 0, "sgstValue": 0, "igstValue": 2750,
  "transMode": "1", "transDistance": 1420,
  "transporterId": "...", "transDocNo": "LR00001", "vehicleNo": "MH12AB1234",
  "itemList": [{ "productName": "Freight", "hsnCode": 996791,
                 "quantity": 1, "taxableAmount": 55000, "igstRate": 5 }]
}
```

### Display on LR

When `trips.ewb_no` is populated, the LR template automatically prints:

```
E-Way Bill: 181020260017 · valid till 24-04-2026 06:00
```

directly under the LR header — no code changes required when you fill the credentials and go live.

### Behaviour Without Credentials

- `EwayBillService::isConfigured()` returns false
- **Generate via API** button still builds the exact payload and logs it to `ewb_logs` with status `Queued`
- User sees a clear "ClearTax credentials not set — payload queued. Generate on NIC portal and enter EWB number manually." message
- Manual-entry form accepts the 12-digit EWB number from the NIC portal and the validity datetime — immediately appears on the LR

### Alternative GSPs

Schema is identical; only `cleartax.baseUrl` changes:

| GSP | Base URL |
|---|---|
| ClearTax (default) | `https://api.cleartax.in` |
| Masters India | `https://einvapi.mastersindia.co` |
| GST-Hero | `https://api.gsthero.com` |
| WebTel | `https://api.webtelind.com` |

---

## 10. Trip Expenses

Every trip has internal costs (our P&L) and billable reimbursables (passed through to the client). This module handles both with full accounting integration.

### Schema

| Table | Purpose |
|---|---|
| `trip_expense_categories` | Master list with `default_is_billable` |
| `trip_expenses` | Actual entries with `is_billable`, `billed_on_invoice_id`, `paid_to`, `payment_mode`, `reference_no`, `document_id` (receipt), `remarks` |

### Seeded Categories (15)

| Category | Default billable? |
|---|---|
| Toll / FASTag · Diesel / Fuel · Driver Bhatta · Parking · Maamul / Border · POD Courier · Cleaning / Tarpaulin · Breakdown Recovery · Miscellaneous | Internal |
| Loading Charges · Unloading Charges · Detention · Weighbridge · Escort / Pilot · Multi-point Charges | Billable |

### Trip Expenses Card (on trip page, `#expenses` anchor)

- **Summary ribbon**: `Internal ₹X · Billable ₹Y · Unbilled to Client ₹Z`
- **Inline add form**: Date · Category (dropdown with default billable toggle) · Amount · Paid To (Driver/Vendor/Direct/Self) · Billable switch · Description · Mode · Reference · Receipt upload (PDF/image)
- **Smart default**: selecting a category auto-sets the Billable checkbox based on `default_is_billable`
- **Table** with edit/delete/unbill buttons, attached receipt link, invoice link when billed

### Invoice Auto-Injection

When you open `/invoices/from-trip/:id`:
1. Line 1 = agreed freight (`final_sell_rate`)
2. Lines 2…N = one line per **unbilled billable expense**, with HSN 996791 and default GST rate
3. Hidden `line_expense_id[]` field on each line tracks which expense the line represents
4. On **Save**, the controller:
   - Creates the invoice + items as normal
   - Finds every `line_expense_id > 0` in the posted form
   - Updates those expenses' `billed_on_invoice_id = <new invoice id>` — they now show as "Billed" everywhere

### Unbill Workflow

- Each billed expense row shows an "Unbill" button
- Click → `billed_on_invoice_id` set back to NULL
- Expense becomes available to re-inject into a fresh or revised invoice

### Impact on Analytics

**Dashboard** now shows two things tied to expenses:

| Widget | Formula |
|---|---|
| **True Margin (MTD)** | `booked margin − internal expenses MTD` |
| **Margin Leak banner** | Shown when `SUM(unbilled billable) > 0` — links to the Unbilled Billable report |

**Profitability report** now has 3 extra columns:

| Column | Formula |
|---|---|
| Int Exp (Internal Expenses) | `SUM(trip_expenses.amount WHERE is_billable=0)` per booking |
| Recov. (Billable Recovered) | `SUM(trip_expenses.amount WHERE is_billable=1 AND billed_on_invoice_id IS NOT NULL)` |
| True M. (True Margin) | `booked margin + billable_recovered − internal_expenses` |

Totals ribbon adds **Internal Exp · Billable Recovered · True Margin + True Margin %**.

### Three New Reports

| Report | What it shows |
|---|---|
| `/reports/trip-expenses` | Paginated list; filters: date range · category · type (billable/internal) · vendor · trip · totals ribbon |
| `/reports/unbilled-billable` | All billable expenses with `billed_on_invoice_id IS NULL` + running total + "Invoice trip" CTA per row |
| `/reports/expense-categories` | Grouped by category with Entries / Internal / Billable / Unbilled / Total + **Recovery %** (billed ÷ billable) |

---

## 11. GPS & Fleet Tracking (LocoNav)

### Adapter (`App\Libraries\LocoNavService`)

- Endpoint: `{baseUrl}/api/v1/vehicles/{vehicleNumber}/location`
- Tolerant response normaliser (accepts `{data: {latitude, ...}}` or flat `{lat, lng, ...}`)
- Upserts `latest_vehicle_status` by vehicle number
- Appends to `gps_logs` (full history)
- **Delay detection**:
  - Stale GPS >30 min → `delay_flag = 1`
  - Stationary (speed <5 km/h) >60 min while trip is `In Transit` → `delay_flag = 1`
  - Writes `delay_reason` onto the trip

### Fleet Map (`/gps`)

- Leaflet + OpenStreetMap tiles (no API key)
- One pin per active vehicle; popup shows trip, vehicle, client, route, speed, timestamp, delay pill, deep-link to trip map
- Table below the map with the same data

### Single-Trip Map (`/gps/trip/:id`)

- Full trail rendered as a polyline with start marker
- Latest position as a separate pin
- Last-fix card with coordinates, speed, address, delay flag

### Spark CLI Command

```
php spark tpt:gps-refresh
```

Polls LocoNav for every non-closed trip, refreshes the latest-status row, detects delays. Safe to schedule every 5–15 minutes via Windows Task Scheduler or cron:

```
*/10 * * * * C:\xampp\php\php.exe C:\xampp\htdocs\tpt\spark tpt:gps-refresh
```

---

## 12. Billing — Invoices, Receipts, E-Invoice

### Invoice Creation

- **From a trip** — pre-populates freight line + all unbilled billable expenses as separate line items
- **Standalone** — blank form
- Multi-line item editor with live JS totals (qty × rate → taxable → GST → line total)
- **GST engine**: decides CGST+SGST vs IGST automatically by comparing `client.state` vs `settings.company_state`
- Round-off captured separately
- Draft → Issued (finalize locks editing) → Partially Paid → Paid OR Cancelled

### PDF Generation

- **dompdf** A4 invoice template with company branding, consignor + consignee block, items grid, tax breakdown, totals, IRN/ACK footer when e-invoice generated
- **Stored** copy written to `writable/uploads/invoices/{invoice_no}.pdf`

### WhatsApp Actions

| Action | Template | Variables |
|---|---|---|
| **Share invoice** | `invoice_share` | invoice_no · total · due_date |
| **Payment reminder** | `payment_reminder` | invoice_no · balance_due · due_date |

### Receipts

- Record receipt against an invoice with date · mode (UPI / NEFT / RTGS / Cheque / Bank Transfer / Cash / Other) · amount · reference · notes
- Invoice auto-recalculates: `amount_received`, `balance_due`, `invoice_status` on every add / delete

### E-Invoice (ClearTax IRP)

| Item | Detail |
|---|---|
| Library | `App\Libraries\ClearTaxService` |
| Endpoint | `POST {baseUrl}/einv/v2/generate` |
| Payload | Full IRP schema with Version 1.1, TranDtls, DocDtls, SellerDtls, BuyerDtls, ItemList, ValDtls |
| Success | Writes `irn_no`, `ack_no`, `ack_date` back onto the invoice; receipt logged in `einvoice_logs` |
| Not configured | Payload built and logged with `irn_status = 'Queued'`; no network call |

---

## 13. Vendor Bills & Payments

### Vendor Bills

| Field | |
|---|---|
| Identity | vendor_id · trip_id · bill_no · bill_date · due_date |
| Commercials | bill_amount · **amount_paid (auto)** · **balance_due (auto)** · status (Open / Partially Paid / Paid / Cancelled) |
| **Bill Copy (optional)** | Upload PDF / image up to 10 MB — stored as a `documents` row, linked via `vendor_bills.document_id` |

### Bill Copy Upload

- File input on the create + edit forms (`enctype="multipart/form-data"`)
- Mime type captured before file is moved (fixes a temp-file gotcha)
- Stored under `writable/uploads/vendor-bills/<bill_id>/<random>.ext`
- **Replace** from the edit form (old file deleted automatically)
- **Remove** from the show page
- **Download** streams the original filename
- Visible in the global **Documents** page with `module = vendor_bill`

### Payments

- Record vendor payment with date · mode · amount · reference · notes
- Bill auto-recalculates: `amount_paid`, `balance_due`, `status` on every add / delete

---

## 14. Documents Module

Central cross-module document browser at `/documents`.

### Filters (laid out in a clean filter card, not stuffed in the header)

| Filter | Shows |
|---|---|
| Search | file name / remarks |
| Module | trip · vendor_bill · vendor · booking · invoice · rfq · lead · trip_expense |
| Type | POD · LR · Vendor Bill · Expense Receipt · Insurance · … |
| Status | Any / Pending / Verified / Rejected |

### Columns

ID · Module (with deep-link to the owning entity) · Type · File (name + size + MIME) · Source channel (upload / whatsapp) · Verification status · Uploaded at · By

### Per-row Actions

- **Download** — streams original filename
- **Mark Verified** / **Mark Rejected** (one-click)
- **Delete** (soft)

### Wired Modules

- **Trip POD** — auto-created on POD upload
- **Trip LR** — linked via `lr_no`
- **Vendor Bill** — optional bill-copy upload
- **Vendor Insurance** — manual upload
- **Trip Expense Receipt** — auto-created on expense-with-file
- **Invoice PDF** — every generation writes the stored copy

---

## 15. Audit Logs

### Coverage

Every significant action writes to `activity_logs`: login, lead/RFQ/booking/trip creation + status changes + approvals, invoice generation + finalize, receipt, vendor bill, vendor payment, WhatsApp dispatch, EWB actions — with `old_value_json` and `new_value_json` diffs where applicable.

### Viewer (`/audit-logs`)

- Filters: Search · Module · Action · User · Date from/to
- Color-coded action pills:
  - **Green** — create · approve
  - **Red** — delete
  - **Amber** — status · dispatch
  - **Grey** — update · login
- Per-row detail page with Before/After JSON displayed side-by-side in fenced blocks
- Pagination with query-string preservation

---

## 16. Dashboards, Reports & Vendor Scoring

### Executive Dashboard (`/dashboard`)

**12 KPI tiles** in 3 rows of 4:

| Row | Tiles |
|---|---|
| Row 1 | Revenue (MTD) · Collected (MTD) · **True Margin (MTD)** · Leads (MTD) |
| Row 2 | Open RFQs · Active Trips · Delayed · POD Pending |
| Row 3 | Receivables · Payables · Overdue Invoices · Unpaid Invoices |

**Margin Leak Banner** — red alert at the bottom when any billable expenses are unbilled, with a direct link to the Unbilled Billable report.

**Charts**:
- 6-month Revenue Trend (Chart.js bar + line combo: Billed · Received · Margin)
- Lead Funnel (horizontal bar, count per status)

**Top lists**: Top Clients · Top Vendors (by billed, margin, bookings, rating).

### Reports Hub (`/reports`)

| Report | Route | Highlights |
|---|---|---|
| **Profitability** | `/reports/profitability` | Booking-level sell/buy/margin + **Int Exp / Recov. / True M.** columns; filters: date · client · vendor · status; totals ribbon |
| **Receivables Aging** | `/reports/receivables` | 5 buckets (Current / 0-30 / 31-60 / 61-90 / 90+) + per-invoice drill-down |
| **Payables Aging** | `/reports/payables` | Same for vendor bills |
| **Lead Funnel** | `/reports/lead-funnel` | Counts + Chart.js viz |
| **Vendor Scorecard** | `/vendors/scoring` | Composite score with 7 metrics |
| **Trip Expenses** | `/reports/trip-expenses` | Filters: date · category · type · vendor · trip; totals ribbon |
| **Unbilled Billable** | `/reports/unbilled-billable` | Revenue leakage report with "Invoice trip" CTAs |
| **Expense Categories** | `/reports/expense-categories` | Grouped by category with Recovery % |

### Vendor Scorecard Algorithm

Higher = better, capped to keep the range reasonable:

```
score  = response_rate × 0.25           # up to +25
       + (120 − avg_response_minutes) × 0.10   # up to +12
       + pod_rate × 0.20                # up to +20
       + rating × 4                     # up to +20
       + 5 if preferred
       − cancel_rate × 0.50             # up to −50
       − 30 if blacklisted
```

Columns visible: RFQs · Response % · Avg Response · Win % · Bookings · Cancel % · POD % · Rating · Flags.

---

## 17. Numbering Scheme

All numbering uses `App\Libraries\NumberGenerator` with prefixes stored in **Settings** (run-time editable):

| Entity | Default | Setting key | Format |
|---|---|---|---|
| Leads | LD | `lead_prefix` | `LD00001` |
| RFQs | RFQ | `rfq_prefix` | `RFQ00001` |
| **RFQ masked reference** | REF | *n/a* | `REF-XXXXXXXX` (opaque random hex) |
| Bookings | BK | `booking_prefix` | `BK00001` |
| Trips | TR | `trip_prefix` | `TR00001` |
| Lorry Receipts | LR | `lr_prefix` | `LR00001` |
| Invoices | INV | `invoice_prefix` | `INV00001` |

Collision guard: the generator re-tries with the next integer until a free number is found.

---

## 18. Navigation & UX

### Accordion Sidebar

- Top-level sections are collapsible: **CRM · Purchase · Operations · Finance · Communication · Reports · Admin**
- Single-link items stay flat: **Dashboard · Documents**
- **Strict accordion**: opening one closes the others
- **Auto-opens the section containing the current page** on first render (server-driven `data-active-section`)
- **Persists user's last-opened section** per browser in `localStorage` (`tpt_open_section`)
- Active link highlighted with a dark pill; active section header becomes bold
- Mobile hamburger button toggles the sidebar as an overlay

### Documents Page Filter Card

- Filters live in their own card below the title (not stuffed in the header)
- Labelled inputs laid out in a `row g-2` with explicit column widths
- `Filter` button + a `✕` reset button that only appears when a filter is set

### Dispatch Pack Document Picker

- Single dropdown in the sticky toolbar with all 5 doc checkboxes + All / None quick buttons
- Selection count pill shows `N / 5`
- No scrolling required to configure the pack

---

## 19. Integrations

All 3 integrations are **config-driven** with queue-safe fallback — UIs never show a raw cURL error, and nothing silently fails.

| Provider | Library | Config keys | When not configured |
|---|---|---|---|
| **WhatsApp (Meta Cloud API, direct)** | `WhatsAppService` | `whatsapp.phoneNumberId` · `businessAccountId` · `accessToken` · `verifyToken` · `apiVersion` · `graphBase` | Messages logged with status `Queued` |
| **LocoNav GPS** | `LocoNavService` | `loconav.baseUrl` · `apiKey` | Refresh no-ops with a clear flash message |
| **ClearTax E-Invoice** | `ClearTaxService` | `cleartax.baseUrl` · `apiKey` · `gstin` | Payload stored in `einvoice_logs` status `Queued` |
| **ClearTax E-Way Bill** | `EwayBillService` | shares ClearTax creds + optional `ewb.gspUserName` · `ewb.gspPassword` | Payload stored in `ewb_logs` status `Queued`; manual-entry form always available |

---

## 20. Scheduled Jobs

### Spark Commands

```
php spark tpt:gps-refresh        # polls LocoNav for every active trip
```

### Recommended Windows Task Scheduler / cron entries

```
*/10 * * * *   C:\xampp\php\php.exe C:\xampp\htdocs\tpt\spark tpt:gps-refresh
```

Future additions (hooks already exist for these):
- `tpt:wa-retry` — resend failed WhatsApp messages
- `tpt:invoice-reminders` — auto-send payment reminders on due dates
- `tpt:ewb-expiry-alerts` — WhatsApp warning when an EWB is within 2 hours of expiry

---

## 21. Demo Data Seeder

Run any time with `C:\xampp\php\php.exe spark db:seed DemoDataSeeder`.  
**Idempotent** — wipes only its own scope (NEVER touches users, roles, permissions, settings, templates, expense categories).

| Entity | Count |
|---|---|
| Clients | 8 (across Maharashtra, Tamil Nadu, Delhi, WB, Gujarat, Kerala, Telangana) |
| Vendors | 10 (4 preferred, 1 blacklisted, ratings 2.9–4.7) |
| Drivers · Vehicles | 10 · 12 |
| Leads | 30 spread across all 9 statuses, aged 1–150 days |
| Lead follow-ups | ~15 |
| Lead status history | ~60 rows |
| RFQs | 20 (all Awarded) |
| RFQ vendors | 60 |
| Quotations | 60 with `response_source='whatsapp'` |
| Bookings | 8 (5 Completed · 2 Handed Over · 1 Approved) with consignees + freight modes |
| Trips | 8 with LR numbers and status history |
| **EWBs** | **7** (12-digit format, 72-hr validity) |
| **Trip expenses** | **32** (3-5 per trip, mix internal/billable, 70% of billable pre-billed) |
| **Unbilled billable (visible leakage)** | **₹14,195** |
| Invoices | 7 (5 Paid · 2 Partially Paid, ₹58K outstanding) |
| Receipts · Vendor bills · Vendor payments | 7 each |
| GPS trails | 12 log rows · 2 active-trip pins |
| Documents | 8 (POD, LR, Insurance — mixed Verified / Pending) |
| Activity logs | 102 across 15 action types |

---

## 22. Technology Stack

| Layer | Library / Version |
|---|---|
| Runtime | PHP 8.2 |
| Database | MariaDB 10.4 (MySQL 8 compatible) |
| Framework | CodeIgniter 4.7.2 |
| Dependency manager | Composer (latest) |
| Front-end CSS | Bootstrap 5.3 |
| Icons | Bootstrap Icons 1.11 |
| Typography | Poppins (Google Fonts) |
| Charts | Chart.js 4.4 (CDN) |
| Maps | Leaflet 1.9 + OpenStreetMap tiles |
| PDF | dompdf 3.1 (via composer) |
| Session | File-based (DB-ready via seeded `ci_sessions` table) |

---

## 23. Folder Structure

```
C:\xampp\htdocs\tpt\
├── app/
│   ├── Commands/
│   │   └── GpsRefresh.php
│   ├── Controllers/
│   │   ├── AuthController.php  BaseController.php  DashboardController.php
│   │   ├── UsersController.php  RolesController.php  SettingsController.php
│   │   ├── ClientsController.php  VendorsController.php  DriversController.php
│   │   ├── VehiclesController.php  LeadSourcesController.php
│   │   ├── LeadsController.php
│   │   ├── RfqController.php  QuotationsController.php
│   │   ├── BookingsController.php  TripsController.php
│   │   ├── DispatchController.php           # LR, Trip Sheet, Loading Advice, POD Blank, Gate Pass
│   │   ├── TripExpensesController.php       # NEW
│   │   ├── EwayBillsController.php          # NEW
│   │   ├── GpsController.php
│   │   ├── InvoicesController.php  ReceiptsController.php
│   │   ├── VendorBillsController.php  VendorPaymentsController.php
│   │   ├── DocumentsController.php  AuditLogsController.php
│   │   ├── ReportsController.php  VendorScoringController.php
│   │   ├── WhatsAppController.php  WebhookController.php
│   ├── Filters/                (AuthFilter, GuestFilter)
│   ├── Helpers/menu_helper.php
│   ├── Libraries/
│   │   ├── Auth.php
│   │   ├── NumberGenerator.php
│   │   ├── Analytics.php
│   │   ├── WhatsAppService.php
│   │   ├── LocoNavService.php
│   │   ├── ClearTaxService.php
│   │   ├── EwayBillService.php              # NEW
│   ├── Models/                 (23 models — one per logical entity)
│   ├── Database/
│   │   ├── Migrations/         (13 files)
│   │   ├── Seeds/
│   │   │   ├── PermissionSeeder.php
│   │   │   ├── TripExpenseCategorySeeder.php   # NEW
│   │   │   └── DemoDataSeeder.php
│   ├── Views/
│   │   ├── layouts/app.php
│   │   ├── auth/               (login)
│   │   ├── dashboard/          (with charts)
│   │   ├── leads/              (index, form, show)
│   │   ├── rfq/                (index, form, show)
│   │   ├── quotations/
│   │   ├── bookings/
│   │   ├── trips/
│   │   │   ├── index.php   show.php
│   │   │   ├── _ewb.php                     # NEW fragment
│   │   │   └── _expenses.php                # NEW fragment
│   │   ├── dispatch/           (hub + lr + trip_sheet + loading_advice + pod_blank + gate_pass + _styles)
│   │   ├── invoices/           (index, form, show, pdf)
│   │   ├── receipts/  vendor_bills/  vendor_payments/
│   │   ├── gps/                (index, trip)
│   │   ├── documents/  audit_logs/  whatsapp/  settings/
│   │   ├── reports/
│   │   │   ├── index.php   profitability.php  aging.php  lead_funnel.php
│   │   │   ├── trip_expenses.php             # NEW
│   │   │   ├── unbilled_billable.php         # NEW
│   │   │   └── expense_categories.php        # NEW
│   │   └── vendors/scoring.php
│   └── Config/
│       ├── App.php  Routes.php  Filters.php
├── public/
│   ├── index.php
│   └── assets/css/app.css  assets/js/app.js
├── writable/
│   ├── uploads/
│   │   ├── trips/<id>/         (PODs)
│   │   ├── vendor-bills/<id>/  (bill copies)
│   │   ├── trip-expenses/<id>/ (receipts)
│   │   └── invoices/           (generated PDFs)
│   └── logs/
├── vendor/                     (composer)
├── .env                        (all credentials + runtime config)
├── spark
└── FEATURES.md                 (this file)
```

---

## 24. Setup & Running

### One-time

```bash
# MySQL running on port 3306, Apache on port 80 via XAMPP
cd C:\xampp\htdocs\tpt
C:\xampp\php\php.exe spark migrate --all
C:\xampp\php\php.exe spark db:seed PermissionSeeder
C:\xampp\php\php.exe spark db:seed TripExpenseCategorySeeder
C:\xampp\php\php.exe spark db:seed DemoDataSeeder      # optional, for demo
```

### Daily

- Apache + MySQL started via `C:\xampp\xampp-control.exe`
- Open **http://localhost/tpt/public/**
- Login `admin@tpt.local` / `admin@123`

### Re-seed at any time

```bash
C:\xampp\php\php.exe spark db:seed DemoDataSeeder
```

This wipes only transactional data (bookings, trips, invoices, expenses, etc.) and keeps users/roles/settings/templates/categories intact.

---

## 25. What's Awaiting Credentials

All three integrations work **today** — they just queue instead of going live. Fill these in `.env` to flip the switch, no code changes needed:

```ini
# Meta WhatsApp Cloud API
whatsapp.phoneNumberId      = <your-phone-number-id>
whatsapp.businessAccountId  = <your-WABA-id>
whatsapp.accessToken        = <system-user-permanent-token>
whatsapp.verifyToken        = <random-string-used-on-webhook-setup>

# LocoNav GPS
loconav.apiKey              = <your-loconav-key>

# ClearTax GSP (E-Invoice + E-Way Bill share these)
cleartax.apiKey             = <your-cleartax-key>
cleartax.gstin              = <your-15-char-GSTIN>

# Optional — only if GSP requires username/password auth for EWB
ewb.enabled                 = true
ewb.gspUserName             = <portal-username>
ewb.gspPassword             = <portal-password>
```

The moment these are set:

- **WhatsApp** — RFQ dispatches actually send to vendors · client notifications on vehicle-placed/in-transit/delivered go out · invoice share works · incoming webhooks auto-capture quotations
- **LocoNav** — the fleet map shows live pins · the cron job updates `latest_vehicle_status` every 10 minutes · delay detection fires
- **ClearTax E-Invoice** — Generate IRN button on invoices produces real IRNs and ACKs
- **ClearTax E-Way Bill** — Generate button on trips produces a real 12-digit EWB number that prints on the LR PDF

---

## Version History

| Version | Highlights |
|---|---|
| **v1.0** | Core platform: auth + RBAC · CRM · RFQ + WhatsApp · Bookings + Trips + POD · Invoices + receipts + vendor bills + payments · GPS · Dashboard + reports · Documents · Audit logs |
| **v1.1** (this doc) | ✅ Accordion sidebar with state persistence · Documents page filter card redesign · **Dispatch Pack** (5 auto-generated documents, selective print & PDF with dropdown picker) · Vendor bill copy upload · **Trip Expenses** (15 seeded categories, invoice auto-inject, True Margin analytics, 3 new reports) · **E-Way Bill integration** (ClearTax GSP adapter, manual-entry fallback, LR template auto-prints EWB number + validity) |
