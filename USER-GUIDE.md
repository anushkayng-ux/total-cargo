# TPT Aggregator — User Guide

A complete walk-through of the platform for owners, managers, operations, finance and your customers. Read this end-to-end once, and you will understand every screen, every button, and most importantly — *why* the workflow looks the way it does.

> Audience: business users (owners, finance heads, ops managers, sales heads, key client users). No technical knowledge required.

---

## Table of contents

1. [What this platform does](#1-what-this-platform-does)
2. [Who logs in — roles & realms](#2-who-logs-in--roles--realms)
3. [The end-to-end business flow](#3-the-end-to-end-business-flow)
4. [Module-by-module guide](#4-module-by-module-guide)
   - 4.1 [Master data — clients, vendors, drivers, vehicles](#41-master-data)
   - 4.2 [CRM — leads, RFQs, quotations](#42-crm--leads-rfqs-quotations)
   - 4.3 [Operations — bookings, trips, GPS, POD](#43-operations--bookings-trips-gps-pod)
   - 4.4 [Finance — invoices, receipts, vendor bills, payments](#44-finance--invoices-receipts-vendor-bills-payments)
   - 4.5 [Compliance — GST, EWB, E-Invoice, TDS](#45-compliance)
   - 4.6 [Communication — WhatsApp & Email](#46-communication--whatsapp--email)
   - 4.7 [Documents](#47-documents)
   - 4.8 [Reports & dashboards](#48-reports--dashboards)
5. [Client portal — what your customers see](#5-client-portal)
6. [Mobile, GPS & the driver-phone app](#6-mobile-gps--the-driver-phone-app)
7. [Modular advanced features (toggle-on)](#7-modular-advanced-features)
8. [Admin & settings](#8-admin--settings)
9. [Support & help inside the app](#9-support--help-inside-the-app)
10. [Security, audit & data protection](#10-security-audit--data-protection)
11. [Implementation & onboarding plan](#11-implementation--onboarding-plan)
12. [Glossary](#12-glossary)

---

## 1. What this platform does

TPT Aggregator is a single, integrated platform that runs the **entire lifecycle** of an Indian transport / freight aggregator business:

| Stage | What happens | Where in the platform |
|------:|--------------|-----------------------|
| 1 | A customer enquiry comes in | **Leads** |
| 2 | You ask vendors for rates | **RFQ** |
| 3 | You quote the customer back | **Quotations** |
| 4 | The customer confirms | **Bookings** |
| 5 | You place a vehicle and start the journey | **Trips + GPS** |
| 6 | Goods are loaded, moved, delivered, POD captured | **Trips + e-POD** |
| 7 | You raise a GST invoice | **Invoices + E-Invoice + EWB** |
| 8 | You collect payment | **Receipts** |
| 9 | You book the vendor's bill, pay the vendor (less TDS) | **Vendor Bills + Vendor Payments** |
| 10 | Reports & MIS feed back into management decisions | **Reports** |

It also covers the cross-cutting concerns that make a real-world transport business work — WhatsApp & email comms, e-Way bills, e-Invoice, GST & TDS engine, document vault, audit trail, role-based access, mobile responsiveness, a client self-service portal, and an invisible super-admin for diagnostics.

> **Single source of truth.** Master data (clients, vendors, drivers, vehicles) is captured once and referenced everywhere — so a vendor's PAN, GSTIN and bank account flow automatically into every bill and TDS computation.

---

## 2. Who logs in — roles & realms

The platform has **three login realms** that cannot be confused with each other.

### 2.1 Staff realm (your team)

Login at `/login`. Pre-defined roles (you can also create custom ones):

- **Administrator** — full access.
- **Management** — view + approve, light edit. Best for owners and senior leadership.
- **CRM Executive / Manager** — leads, RFQs, quotations.
- **Purchase Executive / Manager** — vendors, RFQs, vendor scoring.
- **Operations Executive / Manager** — bookings, trips, GPS, POD.
- **Accounts** — invoices, receipts, vendor bills, vendor payments, statements.

Every screen is gated by **module × action** permissions — view, add, edit, delete, approve, export.

### 2.2 Client portal realm (your customers)

Login at `/portal`. Each client company can have multiple portal users with different sub-roles:

- **Owner** — invite teammates, raise bookings, see invoices.
- **Booker** — raise bookings, rebook, view live trips.
- **Accounts** — see invoices, statements, raise payment receipts.
- **Viewer** — read-only.

### 2.3 Super-Admin realm (invisible)

A specially-flagged user that does *not* appear in user lists, role assignments or normal audit logs (unless un-redacted by another super-admin). Used for diagnostics, SQL console, impersonation, feature flag toggles, maintenance mode and 2FA enforcement. Designed for the platform vendor — usually one or two trusted people.

> Sessions across realms are isolated by separate cookie keys; the system actively destroys an opposing-realm session when the same browser logs in to the other side, preventing accidental privilege escalation.

---

## 3. The end-to-end business flow

This section shows you the *one* canonical journey from enquiry to cash collected, with every status transition. Follow this with a real example and the rest of the platform makes immediate sense.

### Step 1 — Capture the lead

A customer asks for a quote (call, WhatsApp, website, marketplace, walk-in, referral, repeat customer via portal). It lands in **CRM → Leads** with a unique number like `LD-001234`.

A lead carries: source, date/time, contact name, mobile, email, pickup city/state, drop city/state, material, vehicle required, weight, expected dispatch date, priority, assigned CRM user.

Status flow: **New → Contacted → Qualified → Quoted → Won / Lost**.

> Lost leads are recorded *with reason* (price/timing/competitor/no-stock/other) — this drives the sales improvement report. We never delete leads.

### Step 2 — Raise an RFQ to vendors

Once qualified, click **Convert to RFQ**. The RFQ contains route + vehicle + material + weight + loading date. From the RFQ screen, you:

- Add multiple vendors (or use *suggest* to pull suggested vendors based on lane history and scorecard).
- **Dispatch** — sends the WhatsApp template `rfq_vendor` to all selected vendors at once.
- Receive their replies inline (vendor names with rates).
- Score and **shortlist** the best 1–3 quotes; **select final** one to lock in the buy price.

### Step 3 — Send the customer a quotation

From the RFQ, click **Generate Quotation**. The system pre-fills the buy rate; you add your margin. Send via WhatsApp template `quote_to_client` or email. The quote has a validity (default 7 days).

If the customer negotiates, **clone as new version** — never overwrite. Versions are visible side-by-side.

### Step 4 — Customer confirms → Booking

Click **Convert to Booking**. The lead becomes Won, the quote is locked, and a booking is created with a new number like `BK-002041`.

A booking captures: client, route (with possible multi-pickup / multi-drop stops), vehicle requirement, agreed sell rate, expected dispatch, special instructions, attached documents (PO, MSDS, delivery instructions).

Booking status flow: **Confirmed → Vehicle Placed → In Transit → Delivered → POD Received → Closed**.

> If the booking is over the client's credit limit, an approval workflow can be enforced — Management gets a one-click approve / reject task on the dashboard.

### Step 5 — Place a vehicle → Trip is created

From the booking, click **Place Vehicle**. Pick the vendor, vehicle (only compliant vehicles show up — see *Compliance gate*), driver (only KYC-cleared). The system:

- Creates a **trip** record automatically.
- Locks the vendor + vehicle + driver into the trip.
- Sends WhatsApp `vehicle_placed` to the client (vehicle no, driver name, driver phone).
- Generates a **dispatch pack PDF** — LR, trip sheet, loading advice, blank POD, gate pass — in one click.
- Issues a **driver-tracking link** (token-gated PWA URL) you can WhatsApp to the driver.

### Step 6 — Track the trip live

Trip status flow: **Placed → Loading → In Transit → Unloading → Delivered → POD Received**.

GPS sources used in priority order: **LocoNav → FastTag → Driver-Phone PWA → External link** (configurable per vehicle). The trip page shows a live map, last-known location, ETA, and a full ping log.

Each transition timestamps the trip and may trigger:

- WhatsApp / email update to the client.
- **Detention auto-accrual** if loading or unloading exceeded the free time agreed in the rate contract — added as a billable line item on the trip.
- **Driver advance** entries: cash/UPI advances handed to the driver during the trip; deducted from the final freight payable.

### Step 7 — Capture the e-POD

Two ways:

1. **Driver-phone PWA** — driver opens the PWA, the consignee signs on screen with their finger; the signature PNG is stamped with timestamp + GPS coordinate. Trip auto-moves to *POD Received*.
2. **Paper POD** — scan and upload on the trip page; mark *Verified* once verified by a back-office user.

### Step 8 — Raise the customer invoice

From a delivered + POD-received trip, click **Raise Invoice**. The system pre-fills:

- Client + billing GSTIN
- Line items: freight, detention, multi-pickup/drop, halting, insurance, any client-agreed surcharge.
- GST split — **IGST** if states differ; **CGST + SGST** if same.
- TCS / TDS adjustments where applicable.

Once posted, the invoice number is locked. If the company is over the e-Invoice threshold, **IRN + signed QR** is fetched from the IRP automatically. **E-Way Bill** can be generated from the same screen with Part-B (vehicle no.) auto-filled.

### Step 9 — Collect the payment

In **Receipts**, record the bank transfer / UPI / cheque / cash. Knock-off against one or more invoices, fully or partially. Unallocated money sits in the client's wallet for the next bill. Statements (PDF) can be shared via WhatsApp/email in two clicks.

### Step 10 — Book the vendor bill, pay the vendor

From the same trip, click **Create Vendor Bill**. The system:

- Pre-fills the vendor's freight, multi-pickup/drop charges, detention, advances given.
- Picks the right **GTA tax path** automatically — RCM (you pay GST and claim ITC) or FCM (vendor charges GST).
- Computes **TDS u/s 194C** — 1% (individuals/HUF) or 2% (other), or 20% if PAN missing.
- Net payable = bill - TDS - advances - any deposit hold.

Pay from **Vendor Payments**, knocking off the bill. TDS line items roll up into the quarterly **Form 16A** generator.

### Step 11 — Reports feed the loop

Sales conversion %, win rate, lost reasons, placement TAT, on-time %, POD ageing, receivables ageing, vendor scorecard, lane profitability — all live, all filterable. See [§4.8 Reports](#48-reports--dashboards).

---

## 4. Module-by-module guide

### 4.1 Master data

The four shared registries every other module reads from.

#### Clients
- Company name, billing state (drives IGST vs CGST+SGST split), GSTIN (format-validated), credit limit, payment terms.
- **Multiple contacts** per client — owner / accounts / dispatch — each with name, phone, email, designation.
- Optional **client portal access** — invite as Owner / Booker / Accounts / Viewer.
- Dashboard shows over-credit-limit clients in red; their bookings can require management approval.

#### Vendors
- GSTIN, **PAN** (drives TDS), bank account, GTA flag (RCM / FCM), reliability rating.
- **Multiple contacts** — Owner / Manager / Accountant / Driver coordinator. Phone is unique within the vendor.
- Each vehicle under a vendor has its own **GPS configuration** (provider, IMEI, tracking URL, notes).

#### Drivers
- Mandatory KYC: Aadhaar, Driving Licence, PAN — with expiry tracking.
- Mobile is used for WhatsApp + driver-phone PWA login.
- Language preference (English/Hindi/regional) for SMS templates.

#### Vehicles
- Registration, type (e.g., 32ft SXL, 22ft Container), tonnage, owner vendor, GPS config.
- **Compliance gate** — RC, fitness, permit, insurance, PUC. If any document is expired (or expires within the trip window), placement is **blocked** with a clear error. Renewals are recorded under the vehicle profile.

> Master data is the spine of the system. Get it clean once, and every downstream screen behaves correctly.

### 4.2 CRM — leads, RFQs, quotations

A complete CRM tuned to road freight, not a generic CRM. Key behaviors:

- **Multi-source lead capture** — website, WhatsApp inbound, marketplace push, manual.
- **Auto-assignment** of new leads to CRM execs (round-robin or by zone).
- **Lead followups** — call/email/visit log with next-action reminder.
- **RFQ broadcast** to vendors on WhatsApp; vendor replies are matched back to the RFQ.
- **Vendor scoring** influences which vendors are *suggested* for the next RFQ.
- **Quotation versions** — full history of negotiation rounds.
- **Win-rate vs. discount** report tells sales how much margin they're giving away to win.

### 4.3 Operations — bookings, trips, GPS, POD

#### Bookings
- Multi-pickup, multi-drop supported (each stop is a row with location, planned time, contact).
- Loading slot management — configurable per warehouse / dock (modular feature).
- Booking → Trip handover with one click.

#### Trips
- The execution side. Each trip has its own dispatch pack (LR, trip sheet, loading advice, blank POD, gate pass — generated as a combined PDF).
- **Trip expenses** — toll, fuel, food, driver bhatta — each can be marked billable (rolls up to the customer invoice) or not.
- **Driver advances / settlement** — advances deducted from the final freight payable.
- **Cargo insurance** — captured at trip level; can auto-quote (modular).
- **Detention dwell** — captured at loading & unloading; auto-bills the customer if over free time.

#### GPS
- Live map per trip, ping log, ETA, silent-alert if no ping > 2 hours.
- Sources are fused with priority — **LocoNav → FastTag → Driver-Phone PWA → External link**.
- The driver-phone PWA is a tokenised URL that the driver opens on any smartphone — no app store install. It buffers offline and pushes when connectivity returns.

#### e-POD
- Touch-screen signature on the driver phone (modular feature).
- Or scan + upload + verify.
- Both flows mark the trip as *POD Received* and unblock invoicing.

### 4.4 Finance — invoices, receipts, vendor bills, payments

#### Invoices
- One-click from a delivered trip with POD. Pre-filled GST and TDS adjustments.
- E-Invoice IRN + QR auto-fetched (when above threshold).
- E-Way Bill generated with Part-B auto-filled, extendable, cancellable within 24 hrs, supports consolidated EWB (one truck, many consignments).
- Share via WhatsApp / email; payment reminder template auto-fills the invoice details.
- Cancellable / re-issuable with full audit trail.

#### Receipts
- Bank / UPI / cheque / cash. Knock off one or many invoices fully or partially.
- Unallocated balance per client is shown on the next statement.
- Bank reconciliation report.

#### Vendor bills
- One-click from a delivered trip. **GTA tax** picked automatically (RCM vs FCM).
- **TDS u/s 194C** auto-deducted (1% / 2% / 20% no-PAN).
- Vendor advances, deposits, debit notes all considered in the net payable.

#### Vendor payments
- NEFT / RTGS / IMPS / UPI / Cheque / Cash.
- TDS register feeds the quarterly Form 16A generator (modular).

### 4.5 Compliance

#### GST
- Standard freight HSN **9965** with 5% (no ITC) / 12% (with ITC) — GTA's annual choice.
- IGST vs CGST+SGST split decided automatically from billing/from state.
- Reverse charge (RCM) handled per vendor flag.

#### E-Way Bill (NIC)
Mediated via ClearTax (configurable). All operations supported:
- **Generate** (from invoice or trip)
- **Part-B update** (vehicle no., mode, transporter ID)
- **Extend validity** (delays push journey past validity)
- **Cancel** (within 24 hrs, only if not verified by an officer)
- **Consolidated EWB** (one truck, many consignments)

#### E-Invoice (IRP)
- Auto-fired for invoices once turnover crosses ₹5 cr.
- IRN + signed QR rendered on the printable PDF.

#### TDS
- **194C** on freight (1% / 2% / 20% no-PAN).
- **194Q** on purchases over ₹50 lakh (0.1%).
- All deductions tracked at line level; quarterly Form 16A generated for each vendor (modular).

### 4.6 Communication — WhatsApp & Email

#### WhatsApp
- WhatsApp Business API integration. Templates must be both registered locally and **approved in Meta**.
- Built-in templates: RFQ broadcast, quote share, vehicle-placed, in-transit update, delivered, POD reminder, invoice share, payment reminder.
- Inbound messages land in **WhatsApp → Inbox** linked to the right lead/booking/trip.

#### Email
- Multi-driver: **Brevo, Amazon SES, Google SMTP**. Switch any time from settings.
- Beautiful HTML templates with merge variables; pre-loaded for welcome, password reset, invoice share, statement, payment reminder, generic notification.
- **Queue** with worker locks; a reaper resets stuck rows.
- **Open** (HMAC pixel) and **click** (HMAC redirector) tracking.
- **Bounce / spam / unsubscribe** webhooks from Brevo / SES (signature-verified).
- Email analytics dashboard: sent, delivered, opens, clicks, bounces, complaints, unsubscribes.

### 4.7 Documents

A unified document vault searchable across all modules:
- Client agreements, vendor PAN/GSTIN, driver KYC, vehicle RC/fitness/insurance/permit/PUC, POD scans, invoice copies, MSDS, COA, packing lists, weighbridge slips.
- Documents can be **verified** (4-eyes principle), tagged, and filtered by expiry.
- Expiry alerts on the dashboard 30/15/7 days before due.

### 4.8 Reports & dashboards

- **Sales** — leads, conversion %, win rate, lost reasons, quotation ageing.
- **Operations** — placement TAT, in-transit count, on-time %, detention hours, POD ageing.
- **Finance** — receivables ageing, payables ageing, unbilled trips, GST output/input register, TDS register.
- **Vendor scorecard** — composite 0-100 score on rate competitiveness, on-time, POD turnaround, detention frequency, KYC completeness, customer feedback.
- **Lane profitability** (modular) — revenue minus all costs (freight, detention, advances, tolls) per lane, ranked.
- **Email analytics** — drill-down per template, per recipient, per day.

All reports support: date range, role-based filters, CSV export, "save as default view".

---

## 5. Client portal

Your customer's team gets their own clean, branded login at `/portal`. They can:

- See live status of every booking & trip with GPS map.
- Raise a new booking from a calendar slot, or **Repeat last booking** for repeat lanes.
- Approve quotations sent by your sales team.
- Download POD, invoices, ledger, statements.
- Pay invoices (if a payment gateway is integrated).
- Manage their own team — Owner can invite Booker / Accounts / Viewer users.
- Set notification preferences per user.

> Portal logins are isolated from staff logins by separate cookies; logging into one realm explicitly destroys the other on the same browser. Forgot-password flow uses a token (does not change the password until the user clicks the link), so a request can never lock anyone out.

---

## 6. Mobile, GPS & the driver-phone app

- Every staff screen is **mobile responsive** — Bootstrap 5 grid, tested down to 360 px width.
- The **driver-phone PWA** is install-free: open the WhatsApp link, tap "Add to home screen" once, and the driver gets:
  - Wake-lock so the phone won't sleep while driving.
  - HTML5 watchPosition GPS reporting.
  - **Offline buffering** — pings queue when network is patchy and flush on reconnect.
  - **e-POD signature pad** for the consignee at delivery.
- GPS sources are fused. Even if you have no hardware GPS device, the driver-phone PWA + FastTag combo covers ~95 % of trips with zero device cost.

---

## 7. Modular advanced features

These features can be turned on/off by the platform vendor. They are not in the core booking flow — disabling them keeps everything else intact.

| Module | What it does |
|--------|--------------|
| **Rate Contracts** | Lock buy/sell rates for a client × lane × period. Bookings auto-pull contracted rates. |
| **Loading Slots** | Dock booking + slot management at warehouses. |
| **e-POD Signature** | Touch-screen signature on the driver phone, GPS-stamped. |
| **TDS Certificates** | Quarterly Form 16A generation per vendor. |
| **Vendor Deposits** | Refundable security deposits with hold/release/forfeit lifecycle. |
| **Lane Profitability** | P&L per lane report. |
| **Trip Insurance Auto-quote** | Cargo insurance quote pulled at booking time. |
| **Support Rating** | 1–5 star customer rating on resolved support tickets. |

---

## 8. Admin & settings

A single **Settings** screen organised by section:

- **Company** — name, logo, GSTIN, PAN, registered address. Drives invoice headers.
- **Numbering** — prefixes for Lead/RFQ/Booking/Trip/Invoice/LR.
- **Finance** — default GST rate, TCS, TDS thresholds, ageing buckets.
- **Email** — driver (Brevo/SES/SMTP), from address.
- **WhatsApp** — Meta credentials, default sender.
- **GPS sources** — priority order, FastTag credentials.
- **Approvals** — when to require management approval (over-credit-limit booking, large discount).
- **Feature flags** — toggle modular features.
- **Maintenance** — read-only banner across the app while the team migrates / runs jobs.
- **Support** — toggle customer rating on resolved support tickets.
- **Debug toolbar** — dev-mode toolbar (admins only, dev environment only).

**Roles** screen lets you create custom roles with per-module × per-action flags (view, add, edit, delete, approve, export). Role changes apply on the next page load — no logout needed.

---

## 9. Support & help inside the app

Two sections live inside the app for any logged-in user:

### Support
A built-in ticketing system. Users raise tickets (Bug / Improvement / Question / Other), with priority (Low / Normal / High / Urgent). The support team replies in-thread, can flag *internal notes* (invisible to reporter), and progresses status — Open → In Progress → Resolved → Closed (with Reopened for late replies). When enabled, reporters rate the resolution 1–5 stars.

### Help
A role-aware knowledge base. Topics are categorised (Getting Started, Master Data, CRM, Operations, Finance, Communication, Compliance, Client Portal, Reports, Admin, Super Admin) and tagged with the roles each topic is relevant for, so a Booking Executive sees only the topics that matter to them. Admins can create/edit topics in Markdown.

---

## 10. Security, audit & data protection

- **Authentication**: bcrypt password hashing; optional **TOTP 2FA** (Google Authenticator / Authy) per user, enforceable from super-admin.
- **Sessions**: separate cookie keys per realm; cross-realm impersonation is blocked.
- **Authorisation**: module × action RBAC; every controller method is double-gated (filter chain + in-method check).
- **CSRF** on every form. **XSS** prevented by HTML-escaping at output and a Markdown renderer that escapes input before parsing.
- **Rate limiting** on login, password reset, e-POD public endpoints.
- **Forgot-password** uses a one-time token; the password is changed only when the link is clicked, so a reset request can't lock anyone out.
- **Audit log** — every create / update / delete on a sensitive entity captures who / what / when / before-after.
- **Soft-delete** on business entities — nothing is gone forever.
- **File uploads** — extension allow-list + MIME verification (no SVG; XSS-safe).
- **Sensitive settings** (API keys, SMTP passwords) encrypted at rest.
- **SES / Brevo webhooks** — signature-verified; SES SubscriptionConfirmation is host-allow-listed against `sns.<region>.amazonaws.com` to prevent SSRF.

---

## 11. Implementation & onboarding plan

A typical rollout takes 3–4 weeks. Suggested phasing:

### Week 1 — Foundation
- Server provisioning, domain + SSL, base URL.
- `setup.php` web installer: configure DB, run migrations, restore the seed dump if needed, set company name / GSTIN / logo.
- Create staff users; assign roles. Invite the super-admin.
- Configure Email driver (Brevo recommended for fastest setup); send a test email.
- Configure WhatsApp credentials; submit templates for Meta approval.

### Week 2 — Master data
- Bulk import (or manual entry) of clients, vendors, drivers, vehicles.
- Capture GSTIN/PAN and bank details — these flow into compliance later.
- Upload existing master documents (RC, fitness, insurance, KYC).

### Week 3 — Pilot
- Run 5–10 real bookings end-to-end through the platform.
- Train CRM, Operations, Accounts on their respective screens.
- Configure approval thresholds, TDS rates, GST defaults.

### Week 4 — Go-live
- Open the **client portal** to one or two key customers.
- Switch off the legacy system.
- Start of monthly review cadence: scorecard, profitability, AR ageing.

> Migration tip: business records (clients/vendors/drivers/vehicles) come with the platform. Open invoices and receipts can be brought in via opening-balance entries. Closed history typically stays in the legacy system as read-only archive.

---

## 12. Glossary

| Term | Meaning |
|------|---------|
| **POD** | Proof of Delivery — a signed paper or digital confirmation that goods reached the consignee. |
| **LR** | Lorry Receipt / Goods Consignment Note — the transport contract document for one trip. |
| **EWB** | E-Way Bill — government electronic permit for moving goods worth > ₹50k between or within states. |
| **IRP / IRN** | Invoice Registration Portal / Invoice Reference Number — the e-Invoice infrastructure. |
| **GTA** | Goods Transport Agency — the GST classification for road freight providers. |
| **RCM** | Reverse Charge Mechanism — the recipient pays the GST instead of the supplier. |
| **FCM** | Forward Charge Mechanism — the supplier charges GST on the bill. |
| **TDS** | Tax Deducted at Source — under §194C for freight (1% individuals, 2% other, 20% no-PAN). |
| **TCS** | Tax Collected at Source — typically u/s 52 for e-commerce operators. |
| **HSN** | Harmonised System of Nomenclature — the GST commodity code (9965 for road freight). |
| **TAT** | Turn-Around-Time — KPI for how long a step takes (lead-to-quote, booking-to-placement, etc.). |
| **SOP** | Standard Operating Procedure — codified process; KPI shows adherence rate. |
| **PWA** | Progressive Web App — installable web app, used for the driver-phone tracking & e-POD. |
| **PAN** | Permanent Account Number — Indian tax ID; without it, TDS jumps to 20%. |
| **GSTIN** | GST Identification Number — 15-character business GST registration. |
| **2FA / TOTP** | Two-Factor Authentication using time-based one-time passwords. |
| **RBAC** | Role-Based Access Control — permissions are assigned to roles, roles to users. |

---

### Closing note

The platform is built on the principle that **logistics is operations + finance + compliance, all glued by good communication**. Every screen tries to keep that glue invisible — capture data once, show it everywhere it matters, automate every paperwork step that the law allows.

For any question this guide doesn't answer, raise a ticket from inside the app at **Support → Raise ticket**, or email your account manager. We read every ticket and continuously improve both the product and this guide.

— *TPT Aggregator team*
