# TPT Aggregator — CodeIgniter 4

Transport aggregator CRM + Purchase + Operations + Billing + WhatsApp + GPS platform.

## Phase 1 — what's already done

- CI4 4.7.2 project scaffolded
- Database `tpt_db` with all 35 tables (via migrations)
- RBAC: `roles`, `permissions`, `role_permissions` + `users`
- Auth + session login (bcrypt password hashing, CSRF on all POSTs)
- Permission-driven sidebar (menu auto-hides sections the user cannot access)
- Poppins + Bootstrap 5, white/black/grey theme, mobile-first layout
- Screens: Dashboard, Users, Roles, Role Permissions, Settings, Clients, Vendors, Drivers, Vehicles, Lead Sources
- Seeded: 9 roles, 23 module permissions, default admin, 7 lead sources, 8 WhatsApp templates, company settings

## Local setup (XAMPP / Windows)

1. Start **Apache** and **MySQL** from XAMPP Control Panel.
2. Confirm the database exists (already created during setup):
   ```
   C:\xampp\mysql\bin\mysql.exe -u root -e "SHOW DATABASES LIKE 'tpt_db';"
   ```
3. Browse to **http://localhost/tpt/public/**
4. Login with the seeded admin:
   - Email: `admin@tpt.local`
   - Password: `admin@123`

## Re-running migrations / seeds

```
cd C:\xampp\htdocs\tpt
C:\xampp\php\php.exe spark migrate --all
C:\xampp\php\php.exe spark db:seed PermissionSeeder
```

## Folder structure

```
app/
  Controllers/      AuthController, DashboardController, Users, Roles, Settings, Clients, Vendors, Drivers, Vehicles, LeadSources
  Models/           BaseModel, UserModel, RoleModel, PermissionModel, RolePermissionModel, ClientModel, VendorModel, DriverModel, VehicleModel, LeadSourceModel, SettingModel
  Libraries/        Auth.php
  Filters/          AuthFilter.php, GuestFilter.php
  Helpers/          menu_helper.php
  Database/
    Migrations/     35 tables across 10 migration files
    Seeds/          PermissionSeeder.php
  Views/
    layouts/app.php
    auth/login.php
    dashboard/, users/, roles/, settings/, clients/, vendors/, drivers/, vehicles/, lead_sources/
public/
  assets/css/app.css
  assets/js/app.js
.env                # DB + WhatsApp + LocoNav + ClearTax config (secrets blank — fill before Phase 3+)
```

## Integrations (configured in .env, wired and ready)

| Integration | Library | Config keys | Behavior when creds blank |
|---|---|---|---|
| WhatsApp (Meta Cloud API) | `App\Libraries\WhatsAppService` | `whatsapp.*` | Messages queued in `whatsapp_logs` with status=`Queued` |
| GPS (LocoNav) | `App\Libraries\LocoNavService` | `loconav.*`  | Refresh no-ops gracefully; UI shows warning banner |
| E-Invoice (ClearTax) | `App\Libraries\ClearTaxService` | `cleartax.*` | Payload persisted in `einvoice_logs` with status=`Queued` |

## Cron / scheduled tasks

GPS polling should run every 5–15 minutes. Use Windows Task Scheduler or cron:

```
*/10 * * * *  C:\xampp\php\php.exe C:\xampp\htdocs\tpt\spark tpt:gps-refresh
```

The command polls LocoNav for every trip that is **not** Closed/Cancelled, upserts `latest_vehicle_status`, appends to `gps_logs`, and raises `delay_flag` when a trip has stale GPS (>30 min) or has been stationary (>60 min) while In Transit.

## Roadmap — next phases

2. Leads + CRM follow-ups + status history
3. RFQ flow + masked WhatsApp dispatch + quotation comparison engine
4. Booking conversion → Trip lifecycle → POD capture
5. Invoices (GST breakup + PDF) → Receipts / Vendor Bills / Vendor Payments
6. LocoNav GPS sync, latest-vehicle-status, delay detection
7. ClearTax e-invoice (IRN + ACK)
8. Dashboards, vendor scoring, management reports

## Security notes

- CSRF enforced globally, except `webhooks/*` and `api/*`
- Auth filter on every authenticated route; per-route module permission check (`auth:<module>`)
- All passwords hashed with `password_hash(..., PASSWORD_BCRYPT)`
- Prepared statements via CI4 query builder throughout
- Soft deletes on users, clients, vendors, leads, bookings, trips, invoices, documents
