<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HelpTopicSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $b   = $db->table('help_topics');

        $topics = $this->topics();

        foreach ($topics as $i => $t) {
            if ($b->where('slug', $t['slug'])->countAllResults() > 0) continue;
            $t['sort_order'] = $t['sort_order'] ?? (10 + $i);
            $t['is_published'] = 1;
            $t['created_at'] = $now;
            $t['updated_at'] = $now;
            $b->insert($t);
        }
    }

    private function topics(): array
    {
        return [
            // ── Getting Started ─────────────────────────────────────────────
            [
                'slug' => 'welcome',
                'category' => 'Getting Started',
                'title' => 'Welcome to TPT Aggregator',
                'applicable_roles' => 'all',
                'body_md' => <<<MD
# Welcome

TPT Aggregator is an end-to-end platform for an Indian transport / freight aggregator. It covers the full lifecycle:

1. **CRM** — capture leads, raise RFQs to vendors, send quotations to clients.
2. **Operations** — convert a confirmed booking into a trip, place a vehicle, track GPS, capture POD.
3. **Finance** — raise GST invoices, record receipts, post vendor bills, run TDS, file E-Way bills.
4. **Communication** — WhatsApp, Email and a Client Portal for self-service bookings.
5. **Compliance** — E-Way bill (NIC), E-Invoice (IRP), GTA tax (RCM/FCM), TDS u/s 194C.

> **Tip:** Use the left sidebar to navigate, the global search (top-bar) to jump to records, and the **Calendar** to see today's pickups & deliveries.
MD,
            ],
            [
                'slug' => 'logging-in',
                'category' => 'Getting Started',
                'title' => 'Logging in & resetting your password',
                'applicable_roles' => 'all',
                'body_md' => <<<MD
## Logging in

- Open the app URL given by your administrator.
- Enter your **email** and **password**.
- If 2FA is enabled for your account, enter the 6-digit code from your authenticator app.

## Forgot password?

Use the **Forgot password** link on the login page. You will get a reset email valid for 30 minutes. The password is **only** changed when you click the link and submit a new one — so requesting a reset will never lock you out.

## Sessions

- Idle session timeout is configurable by your admin (default 60 minutes).
- Logging in invalidates any open session in the *opposite* realm (staff vs. portal) on the same browser, so impersonation across realms is prevented.
MD,
            ],
            [
                'slug' => 'tour-the-dashboard',
                'category' => 'Getting Started',
                'title' => 'A tour of the dashboard',
                'applicable_roles' => 'all',
                'body_md' => <<<MD
The **dashboard** is the landing page after login and is tailored to your role:

- **KPI tiles** — open leads, today's bookings, in-transit trips, unbilled trips, overdue invoices.
- **SOP / TAT** widgets — Standard Operating Procedure adherence, Turn-Around-Times for lead → quote, booking → placement, delivery → POD.
- **Calendar mini-widget** — today's pickup & delivery events.
- **Comments inbox** — internal @mentions on leads, bookings, trips, invoices that you should action.

> Tiles are **clickable** — they take you to the underlying filtered list.
MD,
            ],

            // ── Master Data ─────────────────────────────────────────────────
            [
                'slug' => 'master-data-overview',
                'category' => 'Master Data',
                'title' => 'Master data: clients, vendors, drivers, vehicles',
                'applicable_roles' => 'admin,management,crm_exec,crm_mgr,pur_exec,pur_mgr,ops_exec,ops_mgr',
                'body_md' => <<<MD
Master data is shared across the app. Get this clean once and the rest of the workflow stays clean.

- **Clients** — companies you sell to. Capture GSTIN, billing address, credit limit, payment terms.
- **Vendors** — fleet owners or transporters you buy capacity from. Capture GSTIN, PAN (drives TDS), bank details.
- **Drivers** — KYC-validated. Aadhaar/DL/PAN with expiry alerts.
- **Vehicles** — RC, fitness, insurance, PUC, permit. Compliance gate blocks placement if any document is expired.

> Bookings, RFQs and trips can only be raised against **active** master records.
MD,
            ],
            [
                'slug' => 'manage-clients',
                'category' => 'Master Data',
                'title' => 'Adding & managing clients',
                'applicable_roles' => 'admin,management,crm_exec,crm_mgr,accounts',
                'body_md' => <<<MD
## Add a client

Go to **Clients → New**.

Required: company name, billing state (drives IGST/CGST split), GSTIN (validated by format), default credit terms.

## Credit limit & payment terms

The dashboard's **Receivables** card uses these to flag *overdue* and *over-limit* clients in red. Bookings against an over-limit client need approval (see *Settings → Approvals*).

## Client portal access

You can grant **Client Portal** access from the client's profile. Choose a role:

- **Owner** — full access, can invite team members, can rebook.
- **Booker** — can raise bookings; cannot see invoices.
- **Accounts** — can see invoices, receipts, statements; cannot raise bookings.
- **Viewer** — read-only.

> Inviting a portal user sends them an email with a one-time **set-password** link.
MD,
            ],
            [
                'slug' => 'manage-vendors',
                'category' => 'Master Data',
                'title' => 'Vendors, contacts & GPS configuration',
                'applicable_roles' => 'admin,management,pur_exec,pur_mgr,ops_exec,ops_mgr,accounts',
                'body_md' => <<<MD
## Vendor profile

A vendor is the fleet owner / transporter who supplies capacity. Capture:

- **PAN** — drives TDS u/s 194C (1% individual, 2% otherwise). Without PAN, TDS jumps to 20%.
- **GSTIN** — drives whether GTA tax is RCM (recipient pays) or FCM (vendor pays).
- **Bank account** — for vendor payouts.
- **Rating** — 1–5, used by RFQ scoring.

## Contacts

A vendor can have multiple people:

- Owner / Manager / Accountant / Driver coordinator
- Each: name, phone, email, designation
- **Phone is unique within a vendor** — prevents duplicate contacts.

When you place a vehicle, the system pings the contact whose role matches the situation (e.g., Driver coordinator gets the placement WhatsApp).

## GPS configuration on vehicles

On each *Vehicle* record (under the vendor) you can set:

- **GPS provider** — LocoNav, FastTag, Driver Phone PWA, External link.
- **IMEI / device ID** for hardware GPS.
- **Tracking URL** — paste the share link from the provider; the GPS module pulls position from this when other sources are unavailable.
- **Notes** — login info, last verification date, etc.

The GPS router prioritizes: LocoNav → FastTag → Driver Phone PWA → External link.
MD,
            ],
            [
                'slug' => 'manage-drivers',
                'category' => 'Master Data',
                'title' => 'Drivers & KYC',
                'applicable_roles' => 'admin,management,pur_exec,pur_mgr,ops_exec,ops_mgr',
                'body_md' => <<<MD
Driver KYC is mandatory before a driver can be placed on a trip.

Required documents: **Aadhaar, Driving Licence, PAN**. The system tracks expiry on each and blocks assignment when any is expired.

For each driver, capture: name, mobile (used for WhatsApp + PWA login), home address, blood group (safety), emergency contact, language preference (English/Hindi/regional).

> The Driver Phone PWA login uses the driver's mobile + a one-time code; once logged in, the phone reports GPS automatically when a trip is active.
MD,
            ],
            [
                'slug' => 'manage-vehicles',
                'category' => 'Master Data',
                'title' => 'Vehicles & compliance gate',
                'applicable_roles' => 'admin,management,pur_exec,pur_mgr,ops_exec,ops_mgr',
                'body_md' => <<<MD
For each vehicle: registration number, vehicle type (e.g., 32ft SXL, 22ft Container), tonnage, owner vendor, GPS config.

## Compliance gate

The system tracks expiry dates for:
- RC (Registration Certificate)
- Fitness certificate
- National / State permit
- Insurance
- PUC

If **any** is expired or expires within the trip window, placement is blocked with a clear error. Renewals are recorded under the vehicle profile.
MD,
            ],

            // ── CRM ─────────────────────────────────────────────────────────
            [
                'slug' => 'crm-leads',
                'category' => 'CRM',
                'title' => 'Capturing & qualifying leads',
                'applicable_roles' => 'admin,management,crm_exec,crm_mgr',
                'body_md' => <<<MD
A **lead** is an inbound enquiry. Sources can be Website, WhatsApp, Inbound Call, Referral, Marketplace, Social, Manual Entry, Client Portal.

## Lead lifecycle

\`New → Contacted → Qualified → Quoted → Won / Lost\`

- **New** — captured but not yet touched.
- **Contacted** — first call/email made.
- **Qualified** — confirmed real opportunity (route, vehicle, weight, expected dispatch).
- **Quoted** — quotation sent.
- **Won** — converted to booking.
- **Lost** — record reason (price/timing/competitor/other).

> Mark a lead **Lost with reason** rather than deleting it — the *Lost reason* report drives sales improvement.
MD,
            ],
            [
                'slug' => 'crm-rfq-quotations',
                'category' => 'CRM',
                'title' => 'RFQs to vendors & quotations to clients',
                'applicable_roles' => 'admin,management,crm_exec,crm_mgr,pur_exec,pur_mgr',
                'body_md' => <<<MD
## RFQ — Request For Quote (to vendors)

From a qualified lead, raise an **RFQ** to multiple vendors at once. The RFQ contains route, vehicle type, material, weight, loading date.

You can:
- **Broadcast** the RFQ via WhatsApp template \`rfq_vendor\`.
- **Receive** vendor replies in the RFQ thread.
- **Score** vendors (rate, reliability, past performance).
- **Pick** the winning vendor and convert to a booking.

## Quotation — to client

Once you have your buy rate, mark up and send a **quotation** to the client. The quotation has a validity period (default 7 days). When the client confirms, **Convert to Booking** — the lead becomes Won, the quotation is locked, and a booking is created.

> Quotations are versioned. If the client negotiates, create a new version rather than overwriting — for audit and the *Win-rate vs. negotiation* report.
MD,
            ],

            // ── Operations ──────────────────────────────────────────────────
            [
                'slug' => 'ops-bookings',
                'category' => 'Operations',
                'title' => 'Bookings — from confirmation to dispatch',
                'applicable_roles' => 'admin,management,crm_exec,crm_mgr,ops_exec,ops_mgr',
                'body_md' => <<<MD
A **booking** is a confirmed order. It captures: client, route (pickup → drop with possible multi-pickup / multi-drop), vehicle requirement, sell rate, expected dispatch, special instructions.

## Booking lifecycle

\`Confirmed → Vehicle Placed → In Transit → Delivered → POD Received → Closed\`

## What happens at each step

- **Confirmed**: ops team gets a task to *find a vehicle*.
- **Vehicle Placed**: a *trip* record is created automatically. Driver is locked in. WhatsApp goes to the client (\`vehicle_placed\` template).
- **In Transit**: GPS starts streaming. Loading slot, loading time, unloading slot are captured.
- **Delivered**: time-stamped at unloading. Detention auto-accrues if loading or unloading exceeded the free time configured in the rate contract.
- **POD Received**: e-POD signature (driver phone) or scanned paper POD uploaded.
- **Closed**: invoice raised; nothing more to do.

> A booking can be **rebooked** from the Client Portal (Owner/Booker roles) — useful for repeat lanes.
MD,
            ],
            [
                'slug' => 'ops-trips-and-pod',
                'category' => 'Operations',
                'title' => 'Trips, e-POD & detention',
                'applicable_roles' => 'admin,management,ops_exec,ops_mgr',
                'body_md' => <<<MD
A **trip** is the execution side of a booking. One booking = one trip (or many, in multi-vehicle scenarios).

## Status flow

\`Placed → Loading → In Transit → Unloading → Delivered → POD Received\`

Each transition timestamps the trip and may trigger:
- WhatsApp / Email to the client.
- **Detention auto-accrual** — if loading or unloading exceeds free time, the system creates a detention line item on the trip with the rate from the rate contract.
- **Driver advance** — record cash/UPI advances to the driver against the trip; deducted from the final freight payable.

## e-POD

Open the trip on the **Driver PWA** and let the consignee sign on screen. The signature is captured as PNG, stamped with timestamp + GPS coordinate. Trip auto-moves to **POD Received**.

Paper POD: scan and upload on the trip page; mark *Verified* once verified.
MD,
            ],
            [
                'slug' => 'ops-gps-tracking',
                'category' => 'Operations',
                'title' => 'GPS tracking — sources & priority',
                'applicable_roles' => 'admin,management,ops_exec,ops_mgr',
                'body_md' => <<<MD
The platform supports four GPS sources, used in priority order:

1. **LocoNav** — direct API, polled every 5 min.
2. **FastTag** — provider-agnostic; uses NHAI plaza pings.
3. **Driver Phone PWA** — when the driver opens the PWA on their phone, location is reported via HTML5 \`watchPosition\`. Wake Lock keeps the screen on; offline buffer queues pings when connectivity is lost.
4. **External link** — paste a tracking share URL from any third-party provider.

Configure on each Vehicle profile (see *Vendors & GPS configuration* topic).

## In the UI

- **Trip → Map** tab shows live position + the route taken.
- **GPS log** records every ping with source, timestamp, lat/lng, speed.
- ETA is computed from current position + average speed of last 30 min.

> If GPS is silent for >2 hours during In Transit, the trip is flagged red on the dashboard.
MD,
            ],

            // ── Finance ─────────────────────────────────────────────────────
            [
                'slug' => 'finance-invoices',
                'category' => 'Finance',
                'title' => 'Invoices, GST & E-Invoice',
                'applicable_roles' => 'admin,management,accounts',
                'body_md' => <<<MD
## Raising an invoice

From a delivered trip with POD received, click **Raise Invoice**. The system pre-fills:

- Client + billing GSTIN
- Line items (freight, detention, multi-pickup, halting charges, insurance)
- GST split — IGST if states differ, CGST+SGST if same
- TCS / TDS adjustments

You can edit before posting. Once posted, the invoice number is locked.

## E-Invoice (IRP)

If your turnover crosses the threshold (currently ₹5 cr aggregate), invoices auto-fire to the **IRP** for IRN + signed QR. The QR is rendered on the printable PDF.

## E-Way Bill on the invoice

If the invoice triggers an EWB requirement (value > ₹50k inter-state, etc.), generate **EWB** from the invoice page. Part-B (vehicle no.) is auto-filled if a trip is linked. You can also extend the EWB or cancel it within 24 hrs.
MD,
            ],
            [
                'slug' => 'finance-receipts',
                'category' => 'Finance',
                'title' => 'Receiving payments & ageing',
                'applicable_roles' => 'admin,management,accounts',
                'body_md' => <<<MD
- **Receipts** are credits to the client account: bank transfer, UPI, cheque, cash.
- A receipt can be **knocked off** against one or more invoices fully or partially.
- **Unallocated** balance sits in the client's wallet and shows on the next statement.
- **Ageing buckets**: 0-30, 31-60, 61-90, 90+. Configurable in *Settings → Finance*.

## Statements

Generate a client statement from the client profile or by date range. Downloadable as PDF and shareable via WhatsApp/Email directly from the same screen.
MD,
            ],
            [
                'slug' => 'finance-vendor-bills-tds',
                'category' => 'Finance',
                'title' => 'Vendor bills, TDS u/s 194C & Form 16A',
                'applicable_roles' => 'admin,management,accounts,pur_exec,pur_mgr',
                'body_md' => <<<MD
## Vendor bill

Created from a delivered trip → records the *buy* side: freight to the vendor, advances given to the driver, detention, plus GTA tax handling.

### GTA tax: RCM vs FCM

- **GTA under RCM** — *you* (the recipient) pay GST and claim ITC. Vendor invoice is GST-zero. This is the default when the vendor's GSTIN status flag = "RCM".
- **GTA under FCM** — vendor charges 5%/12% on the bill; you pay it and claim ITC.

The system picks the right path automatically based on vendor flags.

## TDS u/s 194C

For freight to a transporter (who has not declared <10 vehicles), TDS applies:
- **1%** if vendor is an individual / HUF
- **2%** otherwise
- **20%** if PAN is missing (avoid this — capture PAN at onboarding)

TDS is auto-deducted on the vendor bill. Net payable = bill - TDS - advances.

## Form 16A (TDS certificate)

Quarterly Form 16A can be generated from **Reports → TDS Certificates**. It includes vendor PAN, deduction summary, and challan reference (entered manually after filing TDS return).
MD,
            ],
            [
                'slug' => 'finance-vendor-payments',
                'category' => 'Finance',
                'title' => 'Vendor payments & deposits',
                'applicable_roles' => 'admin,management,accounts',
                'body_md' => <<<MD
## Payments

From an unpaid vendor bill, hit **Pay**. Choose mode (NEFT/RTGS/IMPS/UPI/Cheque/Cash), bank account, transaction reference. Payment knocks off the bill (or part of it).

## Vendor security deposits

Optional — track refundable deposits taken from new vendors:
- Hold against trips with pending POD.
- Auto-release after a configurable cooling period.
- Forfeit if vendor abandons a trip (with audit trail).

> Enable / disable from **Settings → Feature Flags → Vendor Deposits**.
MD,
            ],

            // ── Communication ───────────────────────────────────────────────
            [
                'slug' => 'comm-whatsapp',
                'category' => 'Communication',
                'title' => 'WhatsApp templates',
                'applicable_roles' => 'admin,management,crm_exec,crm_mgr,ops_exec,ops_mgr',
                'body_md' => <<<MD
The platform integrates with the WhatsApp Business API. Templates must be both **registered locally** and **approved in Meta** before they can fire.

Built-in templates:

- \`rfq_vendor\` — broadcast RFQ to vendors
- \`quote_to_client\` — share quotation
- \`vehicle_placed\` — notify client when vehicle is placed
- \`trip_in_transit\` — periodic location update
- \`trip_delivered\` — delivery confirmation
- \`pod_reminder\` — chase POD from vendor
- \`invoice_share\` — send invoice
- \`payment_reminder\` — chase payment

Variables in the body use \`{{1}}, {{2}}, ...\` matching the order in the template registration in Meta.
MD,
            ],
            [
                'slug' => 'comm-email',
                'category' => 'Communication',
                'title' => 'Email templates, queue & analytics',
                'applicable_roles' => 'admin,management,accounts',
                'body_md' => <<<MD
## Drivers

Configure one of: **Brevo, Amazon SES, Google SMTP** in *Settings → Email*.

## Templates

HTML templates with merge variables. Pre-loaded templates cover: welcome, password reset, invoice share, payment reminder, statement, generic notification.

## Queue

All outbound mail is queued. A worker process claims rows (with \`worker_id\` lock) and dispatches. A **reaper** resets rows stuck in *Sending* >5 min back to *Queued*.

## Tracking

- **Open**: HMAC-signed pixel.
- **Click**: HMAC-signed link redirector.
- **Bounce / Spam**: webhook from Brevo/SES; for SES, the SubscriptionConfirmation is signature-verified.

Analytics dashboard: sent, delivered, opens, clicks, bounces, complaints.
MD,
            ],

            // ── Compliance ──────────────────────────────────────────────────
            [
                'slug' => 'compliance-eway-bill',
                'category' => 'Compliance',
                'title' => 'E-Way Bill — generate, Part-B, extend, cancel, consolidated',
                'applicable_roles' => 'admin,management,ops_exec,ops_mgr,accounts',
                'body_md' => <<<MD
The platform integrates with the **NIC EWB** API (mediated via ClearTax, configured in Settings).

Operations supported on every EWB:

- **Generate** — from an invoice or trip.
- **Part-B update** — vehicle number, mode (Road/Rail/Air/Ship), transporter ID. Auto-filled from the linked trip.
- **Extend validity** — when delays push the journey past the EWB validity (validity = 1 day per 200 km in normal cargo).
- **Cancel** — only within 24 hrs of generation, only if not verified by an officer.
- **Consolidated EWB** — one truck, multiple consignments → one consolidated EWB.

> If the EWB API errors out, the failure is logged with the exact error code; you can retry without losing form data.
MD,
            ],
            [
                'slug' => 'compliance-gst-tds',
                'category' => 'Compliance',
                'title' => 'GST, TCS, TDS — practical reference',
                'applicable_roles' => 'admin,management,accounts',
                'body_md' => <<<MD
## GST on transport (GTA)

- Freight is taxable under HSN **9965**.
- **5%** without ITC, or **12%** with ITC — vendor's choice, declared annually.
- **RCM** is the common practice: the recipient pays GST.

## TCS

If you operate as an **e-commerce operator** (aggregator), TCS u/s 52 may apply on supplies through your platform. Configure rates in *Settings → Finance → TCS*.

## TDS

- **194C** on freight (1% or 2%, 20% without PAN).
- **194Q** on purchases over ₹50 lakh (0.1%).
- Vendor records PAN/Aadhaar; the system computes TDS automatically on each vendor bill.
MD,
            ],

            // ── Client Portal ───────────────────────────────────────────────
            [
                'slug' => 'portal-client-overview',
                'category' => 'Client Portal',
                'title' => 'Client portal — for your customers',
                'applicable_roles' => 'admin,management,crm_exec,crm_mgr,client_owner,client_booker,client_accounts,client_viewer',
                'body_md' => <<<MD
The client portal is a separate login at \`/portal\` for your customer's team.

## Roles

- **Owner** — invite teammates, raise bookings, see invoices.
- **Booker** — raise bookings, rebook, view live trips.
- **Accounts** — see invoices, statements, raise payment receipts.
- **Viewer** — read-only.

## What clients can do

- Raise a booking from a calendar slot or "Repeat last booking".
- Track live GPS of their trips.
- Download POD, invoices, statements.
- Approve quotations sent by your sales team.
- Pay invoices via UPI / payment gateway (if integrated).

> Client logins use a different cookie key than staff (\`cu_*\` vs \`auth_*\`) and the two cannot exist on the same browser at the same time — preventing accidental privilege escalation.
MD,
            ],

            // ── Reports ─────────────────────────────────────────────────────
            [
                'slug' => 'reports-overview',
                'category' => 'Reports',
                'title' => 'Reports & dashboards',
                'applicable_roles' => 'admin,management,crm_mgr,ops_mgr,pur_mgr,accounts',
                'body_md' => <<<MD
Reports cover the full P&L:

- **Sales** — leads, conversion %, win rate, lost reasons, quotation ageing.
- **Operations** — placement TAT, in-transit count, on-time %, detention hours, POD ageing.
- **Finance** — receivables ageing, payables ageing, unbilled trips, GST output/input, TDS register.
- **Vendor** — scorecard (rate, reliability, on-time, detention frequency, KYC status).
- **Lane profitability** — revenue minus all costs (freight, detention, advances, tolls) per lane.

Most reports support: date range, role-based filters, CSV export, "save as default view".
MD,
            ],
            [
                'slug' => 'reports-vendor-scorecard',
                'category' => 'Reports',
                'title' => 'Vendor scorecard explained',
                'applicable_roles' => 'admin,management,pur_exec,pur_mgr',
                'body_md' => <<<MD
The **Vendor Scorecard** ranks vendors on a 0-100 score based on:

- **Rate competitiveness** (vs. lane average)  — 30%
- **On-time placement & delivery** — 30%
- **POD turnaround** — 15%
- **Detention frequency** — 10%
- **KYC / compliance** completeness — 10%
- **Client feedback rating** — 5%

Use the scorecard to:
- Tier vendors (A/B/C) and route the next RFQ to your A-tier first.
- Drop vendors with score <40 or persistent KYC gaps.
MD,
            ],

            // ── Admin ───────────────────────────────────────────────────────
            [
                'slug' => 'admin-roles-permissions',
                'category' => 'Admin',
                'title' => 'Roles, permissions & access control',
                'applicable_roles' => 'admin,management',
                'body_md' => <<<MD
The system uses **module × action** RBAC: each role gets per-module flags for *view, add, edit, delete, approve, export*.

## Built-in roles

- Administrator (everything)
- Management (view + approve everywhere)
- CRM Executive / Manager
- Purchase Executive / Manager
- Operations Executive / Manager
- Accounts

## Adding a custom role

*Roles → New*. Pick role key (machine name) and label. Set the flags per module. Assign the new role to users.

> Role changes take effect on the user's **next page load** — no logout needed.
MD,
            ],
            [
                'slug' => 'admin-settings',
                'category' => 'Admin',
                'title' => 'Settings — what to configure where',
                'applicable_roles' => 'admin,management',
                'body_md' => <<<MD
**Settings → Company** — name, logo, GSTIN, PAN, registered address. Drives invoice headers.

**Settings → Numbering** — prefixes for Lead/RFQ/Booking/Trip/Invoice/LR.

**Settings → Finance** — default GST rate, TCS, TDS thresholds, ageing buckets.

**Settings → Email** — driver (Brevo/SES/SMTP), from address, tracking pixel HMAC key.

**Settings → WhatsApp** — Meta credentials, default sender.

**Settings → Approvals** — when to require management approval (over-credit-limit booking, large discount, etc.).

**Settings → Feature Flags** — toggle modular features (Rate Contracts, Loading Slots, e-POD signature, TDS Certificates, Vendor Deposits, Lane Profitability, Trip Insurance Auto-Quote, Support Rating, etc.).

**Settings → Maintenance** — put the app in read-only maintenance mode (super admin only).
MD,
            ],
            [
                'slug' => 'admin-feature-flags',
                'category' => 'Admin',
                'title' => 'Modular features & feature flags',
                'applicable_roles' => 'admin,management',
                'body_md' => <<<MD
Several advanced features are **modular** — turn them on only if you need them:

| Flag | What it does |
|------|--------------|
| \`rate_contracts\` | Lock buy/sell rates for a client × lane × period |
| \`loading_slots\` | Dock booking + slot management |
| \`epod_signature\` | Touch-screen signature on Driver PWA |
| \`tds_certificates\` | Quarterly Form 16A generation |
| \`vendor_deposits\` | Refundable security deposits from vendors |
| \`lane_profitability\` | P&L per lane report |
| \`trip_insurance\` | Auto-quote cargo insurance on bookings |
| \`support_rating_enabled\` | Allow customers to rate support tickets |

Defense-in-depth: flag-gated routes also have an \`auth:<module>\` filter, so flipping a flag off prevents both access *and* sidebar surfacing.
MD,
            ],

            // ── Super Admin (invisible) ─────────────────────────────────────
            [
                'slug' => 'superadmin',
                'category' => 'Super Admin',
                'title' => 'Super Admin — diagnostics & maintenance',
                'applicable_roles' => 'super',
                'body_md' => <<<MD
The **Super Admin** is invisible to other admins — does not appear in user lists, role assignments, or audit logs unless you specifically un-redact in *Sys → Audit*.

## Tools

- **Diagnostics** — PHP/MariaDB versions, ext loaded, queue depths, slow queries, broken cron jobs.
- **SQL console** — read-only by default. Switch to write only with a per-session confirm.
- **Impersonation** — log in *as* any user. The session carries an \`impersonator_id\` and every action audit-logs both.
- **TOTP 2FA toggle** — per-user enforcement.
- **Maintenance mode** — read-only banner across the app while you migrate / run jobs.
- **Feature flags** — same as Admin, plus dangerous ones (debug toolbar, query log, dev-only routes).

> Super-admin actions appear in the audit log under a dedicated "Sys" channel.
MD,
            ],

            // ── Support / Help (meta) ───────────────────────────────────────
            [
                'slug' => 'how-to-use-support',
                'category' => 'Getting Started',
                'title' => 'How to raise a support ticket',
                'applicable_roles' => 'all',
                'body_md' => <<<MD
1. Open the **Support** section from the left sidebar.
2. Click **Raise ticket**.
3. Pick the **Type** — Bug, Improvement, Question, Other.
4. Set **Priority** — Low, Normal, High, Urgent.
5. Paste the screen URL where you saw the issue (helpful for bugs).
6. Write a clear subject and a description with steps to reproduce.

## After raising

- You'll get a ticket number like \`SUP00123\`.
- The support team will reply in the ticket thread; you'll be notified by email.
- A reply from you on a *Resolved* ticket reopens it automatically.
- Once your ticket is **Resolved** or **Closed**, you can rate the support 1–5 stars (if your admin has enabled rating).
MD,
            ],
        ];
    }
}
