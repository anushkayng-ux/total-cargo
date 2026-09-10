<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', static function () {
    return redirect()->to(site_url('dashboard'));
});

// Auth (staff)
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login',  'AuthController::login');
    $routes->post('login', 'AuthController::attemptLogin', ['filter' => 'throttle:20,60']);
});
// 2FA prompt (no guest filter — user has been password-validated but not yet logged-in)
$routes->get('login/2fa',  'AuthController::twoFactorForm');
$routes->post('login/2fa', 'AuthController::twoFactorSubmit', ['filter' => 'throttle:20,60']);
$routes->get('logout', 'AuthController::logout', ['filter' => 'auth']);

// Staff forgot/reset password (separate from client portal flow)
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('forgot',         'AuthController::forgotForm');
    $routes->post('forgot',        'AuthController::forgotSubmit', ['filter' => 'throttle:5,300']);
    $routes->get('reset/(:any)',   'AuthController::resetForm/$1');
    $routes->post('reset/(:any)',  'AuthController::resetSubmit/$1', ['filter' => 'throttle:10,300']);
});

// ─────────────────────────── Super-Admin "Sys" Console ───────────────────────────
// All routes are silently 404 to anyone without is_super_admin=1
$routes->group('sys', ['filter' => 'superadmin'], static function ($routes) {
    $routes->get('/',                          'SysController::dashboard');

    // Step-up auth gate (used by destructive actions)
    $routes->get('step-up',                    'SysController::stepUpForm');
    $routes->post('step-up',                   'SysController::stepUpSubmit');

    // Feature flags
    $routes->get('feature-flags',              'SysController::featureFlags');
    $routes->post('feature-flags/(:num)/toggle','SysController::toggleFlag/$1');

    // Maintenance + IP allowlist
    $routes->post('maintenance/toggle',        'SysController::toggleMaintenance');
    $routes->post('ip-allowlist',              'SysController::saveIpAllowlist');

    // Cache + sessions
    $routes->post('cache/clear',               'SysController::clearCache');
    $routes->post('sessions/purge',            'SysController::purgeSessions',  ['filter' => 'superadmin:stepup']);

    // SQL console (read-only)
    $routes->get('sql',                        'SysController::sqlForm');
    $routes->post('sql',                       'SysController::sqlRun',         ['filter' => 'superadmin:stepup']);

    // Email queue
    $routes->get('email-queue',                'SysController::emailQueue');
    $routes->post('email-queue/flush',         'SysController::emailFlush');
    $routes->post('email-queue/(:num)/retry',  'SysController::emailRetry/$1');

    // Migrations
    $routes->get('migrations',                 'SysController::migrations');
    $routes->post('migrations/run',            'SysController::migrationsRun', ['filter' => 'superadmin:stepup']);

    // Audit log (read-only)
    $routes->get('audit',                      'SysController::auditLog');

    // Profile + 2FA
    $routes->get('profile',                    'SysController::profile');
    $routes->post('profile/totp/start',        'SysController::totpStart');
    $routes->post('profile/totp/enable',       'SysController::totpEnable');
    $routes->post('profile/totp/disable',      'SysController::totpDisable');

    // Impersonation
    $routes->get('impersonate',                'SysController::userPicker');
    $routes->post('impersonate/(:num)',        'SysController::impersonate/$1');
    $routes->post('impersonate/stop',          'SysController::stopImpersonate');

    // Backup
    $routes->post('backup',                    'SysController::backup', ['filter' => 'superadmin:stepup']);
});

// Authenticated area
$routes->get('dashboard',          'DashboardController::index',     ['filter' => 'auth:dashboard']);
$routes->get('dashboard/widgets',  'DashboardController::widgets',   ['filter' => 'auth:dashboard']);
$routes->post('dashboard/widgets', 'DashboardController::saveWidgets',['filter' => 'auth:dashboard']);
$routes->get('compliance',         'ComplianceController::index',    ['filter' => 'auth']);

// Staff self-service profile + 2FA (open to any logged-in user)
$routes->group('profile', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/',                  'ProfileController::index');
    $routes->post('totp/start',        'ProfileController::totpStart');
    $routes->post('totp/enable',       'ProfileController::totpEnable');
    $routes->post('totp/disable',      'ProfileController::totpDisable');
    $routes->post('change-password',   'ProfileController::changePassword', ['filter' => 'throttle:5,300']);
});

// Server health (no auth — should be hittable by uptime monitors)
$routes->get('_health', 'HealthController::index');
$routes->get('_notifications/counts', 'NotificationsController::counts', ['filter' => 'auth']);
$routes->get('notifications',           'NotificationsController::index',   ['filter' => 'auth']);
$routes->get('notifications/test',      'NotificationsController::test',    ['filter' => 'auth']);
$routes->get('notifications/(:num)/read','NotificationsController::read/$1', ['filter' => 'auth']);
$routes->post('notifications/read-all', 'NotificationsController::readAll',  ['filter' => 'auth']);
$routes->get('_search',               'GlobalSearchController::index',  ['filter' => 'auth']);
$routes->get('_views',                'SavedViewsController::index',    ['filter' => 'auth']);
$routes->post('_views/save',          'SavedViewsController::save',     ['filter' => 'auth']);
$routes->post('_views/delete',        'SavedViewsController::delete',   ['filter' => 'auth']);

// Leads (CRM)
$routes->group('leads', ['filter' => 'auth:leads'], static function ($routes) {
    $routes->get('/',                  'LeadsController::index');
    $routes->get('export',             'LeadsController::export');
    $routes->post('bulk',              'LeadsController::bulk');
    $routes->get('create',             'LeadsController::create');
    $routes->post('store',             'LeadsController::store');
    $routes->get('(:num)',             'LeadsController::show/$1');
    $routes->get('(:num)/edit',        'LeadsController::edit/$1');
    $routes->post('(:num)',            'LeadsController::update/$1');
    $routes->post('(:num)/delete',     'LeadsController::delete/$1');
    $routes->post('(:num)/status',     'LeadsController::changeStatus/$1');
    $routes->get('(:num)/quotation.pdf','LeadsController::quotationPdf/$1');
    $routes->post('(:num)/assign',     'LeadsController::assign/$1');
    $routes->post('(:num)/followups',  'LeadsController::addFollowup/$1');
});

// Admin / RBAC
$routes->group('users', ['filter' => 'auth:users'], static function ($routes) {
    $routes->get('/',              'UsersController::index');
    $routes->get('create',         'UsersController::create');
    $routes->post('store',         'UsersController::store');
    $routes->get('(:num)/edit',    'UsersController::edit/$1');
    $routes->post('(:num)',        'UsersController::update/$1');
    $routes->post('(:num)/delete', 'UsersController::delete/$1');
});

$routes->group('roles', ['filter' => 'auth:roles'], static function ($routes) {
    $routes->get('/',              'RolesController::index');
    $routes->get('create',         'RolesController::create');
    $routes->post('store',         'RolesController::store');
    $routes->get('(:num)/edit',    'RolesController::edit/$1');
    $routes->post('(:num)',        'RolesController::update/$1');
    $routes->get('(:num)/permissions',  'RolesController::permissions/$1');
    $routes->post('(:num)/permissions', 'RolesController::savePermissions/$1');
});

$routes->group('settings', ['filter' => 'auth:settings'], static function ($routes) {
    $routes->get('/',         'SettingsController::index');
    $routes->post('/',        'SettingsController::save');
    $routes->post('logo',     'SettingsController::uploadLogo');
    $routes->post('logo/remove', 'SettingsController::removeLogo');
    $routes->post('email',    'SettingsController::saveEmail');
    $routes->post('email/test','SettingsController::sendTestEmail');
    $routes->post('alerts',   'SettingsController::saveAlerts');
    $routes->post('debug-toolbar','SettingsController::saveDebugToolbar');
    $routes->post('gps',      'SettingsController::saveGpsSources');
});

// Masters
$routes->group('clients', ['filter' => 'auth:clients'], static function ($routes) {
    $routes->get('/',                'ClientsController::index');
    $routes->get('create',           'ClientsController::create');
    $routes->post('store',           'ClientsController::store');
    $routes->get('assign-owners',    'ClientsController::assignOwners');
    $routes->post('assign-owners',   'ClientsController::assignOwnersSave');
    $routes->get('(:num)/edit',      'ClientsController::edit/$1');
    $routes->post('(:num)',          'ClientsController::update/$1');
    $routes->post('(:num)/delete',   'ClientsController::delete/$1');
});

$routes->group('vendors', ['filter' => 'auth:vendors'], static function ($routes) {
    $routes->get('/',              'VendorsController::index');
    $routes->get('create',         'VendorsController::create');
    $routes->post('store',         'VendorsController::store');
    $routes->get('(:num)/edit',    'VendorsController::edit/$1');
    $routes->post('(:num)',        'VendorsController::update/$1');
    $routes->post('(:num)/delete', 'VendorsController::delete/$1');
});

// Excel / CSV importer for clients & vendors. Each upload runs the same
// parse → review → confirm flow; auth is checked against the matching module.
$routes->get('import',                      'ImportController::chooser',          ['filter' => 'auth:clients']);
$routes->post('import',                     'ImportController::chooserSubmit',    ['filter' => 'auth:clients']);
$routes->get('import/clients',              'ImportController::form/clients',     ['filter' => 'auth:clients']);
$routes->get('import/clients/template',     'ImportController::template/clients', ['filter' => 'auth:clients']);
$routes->post('import/clients/parse',       'ImportController::parse/clients',    ['filter' => 'auth:clients']);
$routes->get('import/clients/review',       'ImportController::review/clients',   ['filter' => 'auth:clients']);
$routes->post('import/clients/confirm',     'ImportController::confirm/clients',  ['filter' => 'auth:clients']);

$routes->get('import/vendors',              'ImportController::form/vendors',     ['filter' => 'auth:vendors']);
$routes->get('import/vendors/template',     'ImportController::template/vendors', ['filter' => 'auth:vendors']);
$routes->post('import/vendors/parse',       'ImportController::parse/vendors',    ['filter' => 'auth:vendors']);
$routes->get('import/vendors/review',       'ImportController::review/vendors',   ['filter' => 'auth:vendors']);
$routes->post('import/vendors/confirm',     'ImportController::confirm/vendors',  ['filter' => 'auth:vendors']);

$routes->group('drivers', ['filter' => 'auth:drivers'], static function ($routes) {
    $routes->get('/',              'DriversController::index');
    $routes->get('create',         'DriversController::create');
    $routes->post('store',         'DriversController::store');
    $routes->get('(:num)/edit',    'DriversController::edit/$1');
    $routes->post('(:num)',        'DriversController::update/$1');
    $routes->post('(:num)/delete',     'DriversController::delete/$1');
    $routes->post('(:num)/verify-kyc', 'DriversController::verifyKyc/$1');
});

$routes->group('vehicles', ['filter' => 'auth:vehicles'], static function ($routes) {
    $routes->get('/',              'VehiclesController::index');
    $routes->get('create',         'VehiclesController::create');
    $routes->post('store',         'VehiclesController::store');
    $routes->get('(:num)/edit',    'VehiclesController::edit/$1');
    $routes->post('(:num)',        'VehiclesController::update/$1');
    $routes->post('(:num)/delete', 'VehiclesController::delete/$1');
});

// RFQ + Quotations
$routes->group('rfq', ['filter' => 'auth:rfq'], static function ($routes) {
    $routes->get('/',                              'RfqController::index');
    $routes->get('queue',                          'RfqController::queue');
    $routes->get('create',                         'RfqController::create');
    $routes->get('suggest',                        'RfqController::suggest');
    $routes->post('store',                         'RfqController::store');
    $routes->get('(:num)',                         'RfqController::show/$1');
    $routes->post('(:num)/dispatch',               'RfqController::dispatch/$1');
    $routes->post('(:num)/assign',                 'RfqController::assign/$1');
    $routes->post('(:num)/remind',                 'RfqController::remind/$1');
    $routes->post('(:num)/close',                  'RfqController::close/$1');
    $routes->post('(:num)/vendors',                'RfqController::addVendor/$1');
    $routes->post('(:num)/vendors/(:num)/remove',  'RfqController::removeVendor/$1/$2');
    $routes->post('(:num)/quotations/manual',      'QuotationsController::storeManual/$1');
    $routes->post('(:num)/quotations/(:num)/shortlist',   'QuotationsController::shortlist/$1/$2');
    $routes->post('(:num)/quotations/(:num)/unshortlist', 'QuotationsController::unshortlist/$1/$2');
    $routes->post('(:num)/quotations/(:num)/select',      'QuotationsController::selectFinal/$1/$2');
});

$routes->get('quotations', 'QuotationsController::index', ['filter' => 'auth:quotations']);

// Operational calendar (staff)
$routes->group('calendar', ['filter' => 'auth:bookings'], static function ($routes) {
    $routes->get('/',     'CalendarController::index');
    $routes->get('feed',  'CalendarController::feed');
});

// Bookings
// Fast Docket / LR creation — auth-piggybacks on the trips permission since
// creating a docket is really "make a trip that can print an LR".
$routes->group('dockets', ['filter' => 'auth:trips'], static function ($routes) {
    $routes->get('create',                    'DocketsController::create');
    // Support both /dockets/create/{tripId} (the picker-friendly URL) and the
    // legacy /dockets/from-trip/{id} form. Both prefill the create form.
    $routes->get('create/(:num)',             'DocketsController::createFromTrip/$1');
    $routes->get('from-trip/(:num)',          'DocketsController::createFromTrip/$1');
    $routes->get('pending',                   'DocketsController::pendingDockets');
    $routes->post('store',                    'DocketsController::store');
});

// Arrival — dedicated per-trip unloading + POD + feedback log page.
$routes->group('arrival', ['filter' => 'auth:trips'], static function ($routes) {
    $routes->get('/',                'ArrivalController::index');
    $routes->post('(:num)/save',     'ArrivalController::save/$1');
});

// Purchase-side aggregate pages for Driver Advance / Bhatta and Cargo
// Insurance — the two cards we lifted off the Trip page live here now.
$routes->group('purchase', ['filter' => 'auth:trips'], static function ($routes) {
    $routes->get('advances',   'PurchaseOpsController::advances');
    $routes->get('insurance',  'PurchaseOpsController::insurance');
});

$routes->group('bookings', ['filter' => 'auth:bookings'], static function ($routes) {
    $routes->get('/',                       'BookingsController::index');
    $routes->get('export',                  'BookingsController::export');
    $routes->post('bulk',                   'BookingsController::bulk');
    $routes->get('create',                  'BookingsController::create');
    $routes->get('from-rfq/(:num)',         'BookingsController::createFromRfq/$1');
    $routes->post('store',                  'BookingsController::store');
    $routes->get('(:num)',                  'BookingsController::show/$1');
    $routes->get('(:num)/edit',             'BookingsController::edit/$1');
    $routes->post('(:num)',                 'BookingsController::update/$1');
    $routes->post('(:num)/approve',         'BookingsController::approve/$1');
    $routes->post('(:num)/assign',          'BookingsController::assign/$1');
    $routes->post('(:num)/cancel',          'BookingsController::cancel/$1');
    $routes->post('(:num)/delete',          'BookingsController::delete/$1');
    $routes->post('(:num)/handover',        'BookingsController::handover/$1');
});

// Invoices
$routes->group('invoices', ['filter' => 'auth:invoices'], static function ($routes) {
    $routes->get('/',                       'InvoicesController::index');
    $routes->get('export',                  'InvoicesController::export');
    $routes->post('bulk',                   'InvoicesController::bulk');
    $routes->get('create',                  'InvoicesController::create');
    $routes->get('from-trip/(:num)',        'InvoicesController::createFromTrip/$1');
    $routes->get('consolidated',            'InvoicesController::consolidatedPicker');
    $routes->post('consolidated',           'InvoicesController::consolidatedCreate');
    $routes->post('store',                  'InvoicesController::store');
    $routes->get('(:num)',                  'InvoicesController::show/$1');
    $routes->get('(:num)/edit',             'InvoicesController::edit/$1');
    $routes->post('(:num)',                 'InvoicesController::update/$1');
    $routes->post('(:num)/finalize',        'InvoicesController::finalize/$1');
    $routes->post('(:num)/cancel',          'InvoicesController::cancel/$1');
    $routes->post('(:num)/delete',          'InvoicesController::delete/$1');
    $routes->get('(:num)/pdf',              'InvoicesController::pdf/$1');
    $routes->post('(:num)/share-wa',        'InvoicesController::shareWa/$1');
    $routes->post('(:num)/share-email',     'InvoicesController::shareEmail/$1');
    $routes->post('(:num)/remind',          'InvoicesController::sendPaymentReminder/$1');
    $routes->post('(:num)/remind-email',    'InvoicesController::sendPaymentReminderEmail/$1');
    $routes->post('(:num)/irn',             'InvoicesController::generateIrn/$1');
});

// Client receipts
$routes->group('receipts', ['filter' => 'auth:receipts'], static function ($routes) {
    $routes->get('/',                      'ReceiptsController::index');
    $routes->post('store',                 'ReceiptsController::store');
    $routes->post('(:num)/delete',         'ReceiptsController::delete/$1');
    // Bank-statement CSV import → auto-match flow
    $routes->get('import',                 'BankImportController::form');
    $routes->post('import/parse',          'BankImportController::parse');
    $routes->get('import/review',          'BankImportController::review');
    $routes->post('import/confirm',        'BankImportController::confirm');
});

// Vendor bills
$routes->group('vendor-bills', ['filter' => 'auth:vendor_bills'], static function ($routes) {
    $routes->get('/',                       'VendorBillsController::index');
    $routes->get('create',                  'VendorBillsController::create');
    $routes->get('from-trip/(:num)',        'VendorBillsController::createFromTrip/$1');
    $routes->post('store',                  'VendorBillsController::store');
    $routes->get('(:num)',                  'VendorBillsController::show/$1');
    $routes->get('(:num)/edit',             'VendorBillsController::edit/$1');
    $routes->post('(:num)',                 'VendorBillsController::update/$1');
    $routes->get('(:num)/bill-file',        'VendorBillsController::billFile/$1');
    $routes->post('(:num)/bill-file/remove','VendorBillsController::removeBillFile/$1');
    $routes->post('(:num)/cancel',          'VendorBillsController::cancel/$1');
    $routes->post('(:num)/delete',          'VendorBillsController::delete/$1');
});

// Vendor payments
$routes->group('vendor-payments', ['filter' => 'auth:vendor_payments'], static function ($routes) {
    $routes->get('/',              'VendorPaymentsController::index');
    $routes->post('store',         'VendorPaymentsController::store');
    $routes->post('(:num)/delete', 'VendorPaymentsController::delete/$1');
});

// GPS
$routes->group('gps', ['filter' => 'auth:gps'], static function ($routes) {
    $routes->get('/',                    'GpsController::index');
    $routes->post('refresh-all',         'GpsController::refreshAll');
    $routes->get('trip/(:num)',          'GpsController::trip/$1');
    $routes->post('trip/(:num)/refresh', 'GpsController::refreshTrip/$1');
});

// Trips
$routes->group('trips', ['filter' => 'auth:trips'], static function ($routes) {
    $routes->get('/',                          'TripsController::index');
    $routes->get('export',                     'TripsController::export');
    $routes->post('bulk',                      'TripsController::bulk');
    $routes->get('(:num)',                     'TripsController::show/$1');
    $routes->post('(:num)/assign',             'TripsController::assign/$1');
    $routes->post('(:num)/assign-staff',       'TripsController::assignStaff/$1');
    $routes->post('(:num)/parties',            'TripsController::saveParties/$1');
    $routes->post('(:num)/status',             'TripsController::changeStatus/$1');
    $routes->post('(:num)/pod',                'TripsController::uploadPod/$1');
    $routes->get('(:num)/documents/(:num)',    'TripsController::downloadDocument/$1/$2');
    $routes->post('(:num)/notify-client',      'TripsController::notifyClient/$1');
    $routes->post('(:num)/driver-track/start', 'TripsController::startDriverTrack/$1');
    $routes->post('(:num)/driver-track/stop',  'TripsController::stopDriverTrack/$1');

    // Staff side of the driver chat thread (reply + view driver-uploaded attachments)
    $routes->post('(:num)/driver-chat/reply',                'TripsController::driverChatReply/$1');
    $routes->get('(:num)/driver-chat/attach/(:num)',         'TripsController::driverChatAttachment/$1/$2');

    // Dispatch Pack — viewer + single-doc PDFs + combined pack PDF
    $routes->get('(:num)/dispatch',                                                             'DispatchController::hub/$1');
    $routes->get('(:num)/dispatch/pack\.pdf',                                                   'DispatchController::packPdf/$1');
    $routes->post('(:num)/dispatch/lr/generate',                                                'DispatchController::generateLr/$1');
    $routes->get('(:num)/dispatch/(lr|trip-sheet|loading-advice|pod-blank|gate-pass)',          'DispatchController::single/$1/$2');
    $routes->get('(:num)/dispatch/(lr|trip-sheet|loading-advice|pod-blank|gate-pass)\.pdf',     'DispatchController::singlePdf/$1/$2');

    // Trip expenses
    $routes->post('(:num)/expenses',               'TripExpensesController::store/$1');
    $routes->post('(:num)/expenses/(:num)',        'TripExpensesController::update/$1/$2');
    $routes->post('(:num)/expenses/(:num)/delete', 'TripExpensesController::delete/$1/$2');
    $routes->post('(:num)/expenses/(:num)/unbill', 'TripExpensesController::unbill/$1/$2');
    $routes->get('(:num)/expenses/(:num)/receipt', 'TripExpensesController::receipt/$1/$2');

    // E-Way Bill
    $routes->post('(:num)/ewb/generate',       'EwayBillsController::generate/$1');
    $routes->post('(:num)/ewb/fetch',          'EwayBillsController::fetch/$1');
    $routes->post('(:num)/ewb/cancel',         'EwayBillsController::cancel/$1');
    $routes->post('(:num)/ewb/manual',         'EwayBillsController::setManual/$1');
    $routes->post('(:num)/ewb/clear',          'EwayBillsController::clearEwb/$1');
    $routes->post('(:num)/ewb/partb',          'EwayBillsController::partBUpdate/$1');
    $routes->post('(:num)/ewb/extend',         'EwayBillsController::extendValidity/$1');
    $routes->post('(:num)/ewb/refresh-status', 'EwayBillsController::refreshStatus/$1');

    // Multi-pickup / multi-drop stops
    $routes->post('(:num)/stops',                       'TripStopsController::store/$1');
    $routes->post('(:num)/stops/(:num)',                'TripStopsController::update/$1/$2');
    $routes->post('(:num)/stops/(:num)/delete',         'TripStopsController::delete/$1/$2');

    // Driver advance / bhatta
    $routes->post('(:num)/advances',                    'TripAdvancesController::store/$1');
    $routes->post('(:num)/advances/(:num)/settle',      'TripAdvancesController::settle/$1/$2');
    $routes->post('(:num)/advances/(:num)/delete',      'TripAdvancesController::delete/$1/$2');

    // Cargo insurance + detention dwell
    $routes->post('(:num)/insurance',                   'TripsController::saveInsurance/$1');
    $routes->post('(:num)/dwell',                       'TripsController::saveDwell/$1');
});

// Consolidated EWB (across trips) — separate top-level route so it isn't trip-scoped
$routes->post('ewb/consolidate', 'EwayBillsController::consolidate', ['filter' => 'auth:trips']);

// ─────────────────────── Modular advanced features ───────────────────────
// All gated by a feature flag (super-admin toggle) AND the existing RBAC permission.
// Disabling the flag silently 404s the module — core booking flow stays intact.

// Rate contracts
$routes->group('rate-contracts', ['filter' => ['auth:rate_contracts','feature:rate_contracts']], static function ($routes) {
    $routes->get('/',                'RateContractsController::index');
    $routes->get('create',           'RateContractsController::create');
    $routes->post('store',           'RateContractsController::store');
    $routes->get('(:num)/edit',      'RateContractsController::edit/$1');
    $routes->post('(:num)',          'RateContractsController::update/$1');
    $routes->post('(:num)/delete',   'RateContractsController::delete/$1');
});

// Loading slots — nested under bookings
$routes->group('bookings', ['filter' => ['auth:bookings','feature:loading_slots']], static function ($routes) {
    $routes->post('(:num)/slots',                  'LoadingSlotsController::store/$1');
    $routes->post('(:num)/slots/(:num)',           'LoadingSlotsController::update/$1/$2');
    $routes->post('(:num)/slots/(:num)/delete',    'LoadingSlotsController::delete/$1/$2');
});

// e-POD signing (public consignee + staff start/revoke)
$routes->get('epod/(:any)',          'EpodController::index/$1',  ['filter' => 'throttle:30,60']);
$routes->post('epod/(:any)/submit',  'EpodController::submit/$1', ['filter' => 'throttle:10,60']);
$routes->group('trips', ['filter' => ['auth:trips','feature:epod']], static function ($routes) {
    $routes->post('(:num)/epod/start',  'TripsController::startEpod/$1');
    $routes->post('(:num)/epod/revoke', 'TripsController::revokeEpod/$1');
});

// TDS certificates
$routes->group('tds-certificates', ['filter' => ['auth:tds_certificates','feature:tds_certificates']], static function ($routes) {
    $routes->get('/',              'TdsCertificatesController::index');
    $routes->post('reconcile',     'TdsCertificatesController::reconcile');
    $routes->post('(:num)',        'TdsCertificatesController::update/$1');
});

// Vendor deposits ledger
$routes->group('vendor-deposits', ['filter' => ['auth:vendor_deposits','feature:vendor_deposits']], static function ($routes) {
    $routes->get('/',                  'VendorDepositsController::index');
    $routes->get('(:num)',             'VendorDepositsController::ledger/$1');
    $routes->post('(:num)/record',     'VendorDepositsController::record/$1');
});

// Lane profitability report
$routes->get('reports/lane-profitability', 'LaneProfitabilityController::index',
    ['filter' => ['auth:reports','feature:lane_profitability']]);

// Trip insurance auto-quote
$routes->group('trips', ['filter' => ['auth:trips','feature:trip_insurance']], static function ($routes) {
    $routes->post('(:num)/insurance/quote',          'TripInsuranceController::quote/$1');
    $routes->post('(:num)/insurance/(:num)/bind',    'TripInsuranceController::bind/$1/$2');
});

// E-POD must be exempt from CSRF (public consignee POSTs from a phone, no cookie path)
// — already covered by the global CSRF except '*' rules below.

// Support tickets — open to any logged-in user (raise tickets, view own).
// Agent powers (assign / resolve / close / view-all) are gated inside the
// controller via `support.can_approve`.
$routes->group('support', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/',                  'SupportController::index');
    $routes->get('create',             'SupportController::create');
    $routes->post('store',             'SupportController::store');
    $routes->get('(:num)',             'SupportController::show/$1');
    $routes->post('(:num)/reply',      'SupportController::reply/$1');
    $routes->post('(:num)/update',     'SupportController::update/$1');
    $routes->post('(:num)/rate',       'SupportController::rate/$1');
});

// Help & documentation — open to any logged-in user; admin CRUD inside the
// controller is gated via `help.can_add` / `help.can_edit` / `help.can_delete`.
$routes->group('help', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/',                       'HelpController::index');
    $routes->get('admin',                   'HelpController::adminIndex');
    $routes->get('admin/create',            'HelpController::create');
    $routes->post('admin/store',            'HelpController::store');
    $routes->get('(:num)/edit',             'HelpController::edit/$1');
    $routes->post('(:num)/update',          'HelpController::update/$1');
    $routes->post('(:num)/delete',          'HelpController::delete/$1');
    $routes->get('(:any)',                  'HelpController::show/$1');
});

// WhatsApp admin
$routes->group('whatsapp', ['filter' => 'auth:whatsapp'], static function ($routes) {
    $routes->get('logs',                       'WhatsAppController::logs');
    $routes->get('inbox',                      'WhatsAppController::inbox');
    $routes->get('templates',                  'WhatsAppController::templates');
    $routes->post('templates/save',            'WhatsAppController::saveTemplate');
    $routes->post('templates/(:num)/delete',   'WhatsAppController::deleteTemplate/$1');
    $routes->post('test-send',                 'WhatsAppController::testSend', ['filter' => 'throttle:5,60']);
    $routes->get('debug-send',                 'WhatsAppController::debugSend',  ['filter' => 'throttle:10,60']);
    $routes->get('debug-probe',                'WhatsAppController::debugProbe',   ['filter' => 'throttle:3,60']);
    $routes->get('debug-webhook',              'WhatsAppController::debugWebhook', ['filter' => 'throttle:10,60']);
    $routes->get('debug-spy',                  'WhatsAppController::debugSpy',     ['filter' => 'throttle:20,60']);
});

// Public, token-gated vendor quote-submission flow (NO auth, NO CSRF)
$routes->get('quote/(:any)',  'PublicQuoteController::form/$1',  ['filter' => 'throttle:60,60']);
$routes->post('quote/(:any)', 'PublicQuoteController::submit/$1', ['filter' => 'throttle:20,60']);

// Public, token-gated client-feedback collection (NO auth, NO CSRF)
$routes->get('feedback/(:any)',  'FeedbackController::form/$1',   ['filter' => 'throttle:60,60']);
$routes->post('feedback/(:any)', 'FeedbackController::submit/$1', ['filter' => 'throttle:10,60']);

// Public, token-gated client live-tracking page (NO auth, NO CSRF)
$routes->get('track/(:segment)/data', 'ClientTrackingController::data/$1',  ['filter' => 'throttle:120,60']);
$routes->get('track/(:segment)',      'ClientTrackingController::index/$1', ['filter' => 'throttle:60,60']);

// ─────────────────────────── HRMS ───────────────────────────
$routes->group('hrms', ['filter' => 'auth:hrms'], static function ($routes) {
    // Self-service
    $routes->get('profile',             'HrmsController::myProfile');
    $routes->post('profile',            'HrmsController::saveProfile');
    $routes->get('attendance',          'HrmsController::myAttendance');
    $routes->post('punch-in',           'HrmsController::punchIn');
    $routes->post('punch-out',          'HrmsController::punchOut');
    $routes->get('leaves',              'HrmsController::myLeaves');
    $routes->get('leaves/apply',        'HrmsController::applyLeaveForm');
    $routes->post('leaves/apply',       'HrmsController::applyLeave');
    // Admin / approver
    $routes->get('team',                'HrmsController::team');
    $routes->get('team/(:num)',         'HrmsController::employeeEdit/$1');
    $routes->post('team/(:num)',        'HrmsController::employeeSave/$1');
    $routes->post('team/(:num)/salary', 'HrmsController::saveSalaryComponents/$1');
    $routes->get('approvals',           'HrmsController::leaveApprovals');
    $routes->post('leaves/(:num)/decide','HrmsController::approveLeave/$1');
    $routes->get('team-attendance',     'HrmsController::teamAttendance');
    $routes->get('holidays',            'HrmsController::holidays');
    $routes->post('holidays',           'HrmsController::holidayStore');
    $routes->post('holidays/(:num)/delete', 'HrmsController::holidayDelete/$1');
});

// ─────────────────────────── Meetings (staff app) ───────────────────────────
$routes->group('meetings', ['filter' => 'auth:meetings'], static function ($routes) {
    $routes->get('/',              'MeetingsController::index');
    $routes->get('create',         'MeetingsController::create');
    $routes->post('/',             'MeetingsController::store');
    $routes->get('(:num)',         'MeetingsController::show/$1');
    $routes->post('(:num)',        'MeetingsController::update/$1');
});

// ─────────────────────────── Meeting PWA (auth + token-gated) ───────────────────────────
// Requires staff login. The token alone is not enough — the logged-in user must
// own the meeting OR have meetings.can_approve. Visiting while logged-out is
// intercepted by AuthFilter which stashes the intended URL; after login the
// user is sent back to /m/<token> automatically.
$routes->group('m', ['filter' => 'auth:meetings'], static function ($routes) {
    $routes->get('(:any)',                'MeetingPwaController::index/$1');
    $routes->post('(:any)/punch/(:any)',  'MeetingPwaController::punch/$1/$2', ['filter' => 'throttle:30,60']);
    $routes->post('(:any)/notes',         'MeetingPwaController::notes/$1',    ['filter' => 'throttle:30,60']);
});

// ─────────────────────────── Payroll ───────────────────────────
$routes->group('payroll', ['filter' => 'auth:payroll'], static function ($routes) {
    $routes->get('/',                'PayrollController::runs');
    $routes->post('new',             'PayrollController::newRun');
    $routes->get('(:num)',           'PayrollController::runDetail/$1');
    $routes->post('(:num)/finalise', 'PayrollController::finaliseRun/$1');
    $routes->get('payslip/(:num)',   'PayrollController::payslip/$1');
});

// Webhooks (NO auth, NO CSRF — external services call these)
$routes->get('webhooks/whatsapp',  'WebhookController::whatsappVerify');
$routes->post('webhooks/whatsapp', 'WebhookController::whatsappReceive');
$routes->post('webhooks/brevo',    'WebhookController::brevoEvents');
$routes->post('webhooks/ses',      'WebhookController::sesEvents');

// Driver-phone tracking PWA (public, token-gated). Each trip has a one-shot
// token; the driver opens /d/<token> to share location while driving.
$routes->get('d/(:any)/manifest.webmanifest', 'DriverTrackController::manifest/$1');
$routes->get('d/(:any)/sw.js',                'DriverTrackController::serviceWorker/$1');
$routes->post('d/(:any)/ping',                'DriverTrackController::ping/$1',      ['filter' => 'throttle:120,60']);
$routes->post('d/(:any)/stop',                'DriverTrackController::stop/$1',      ['filter' => 'throttle:30,60']);
$routes->post('d/(:any)/milestone',           'DriverTrackController::milestone/$1', ['filter' => 'throttle:30,60']);
$routes->post('d/(:any)/pod',                 'DriverTrackController::pod/$1',       ['filter' => 'throttle:10,60']);
$routes->post('d/(:any)/message',             'DriverTrackController::sendMessage/$1', ['filter' => 'throttle:30,60']);
$routes->get('d/(:any)/messages',             'DriverTrackController::messages/$1',    ['filter' => 'throttle:120,60']);
$routes->get('d/(:any)/attach/(:num)',        'DriverTrackController::attachment/$1/$2', ['filter' => 'throttle:60,60']);
$routes->get('d/(:any)/doc/(:num)',           'DriverTrackController::viewDoc/$1/$2',    ['filter' => 'throttle:60,60']);
$routes->get('d/(:any)/dispatch/(lr|trip-sheet|loading-advice|pod-blank|gate-pass)\.pdf',
                                              'DriverTrackController::dispatchDoc/$1/$2', ['filter' => 'throttle:30,60']);
$routes->get('d/(:any)',                      'DriverTrackController::index/$1');

// Email tracking (public, no auth) — pixel, click wrapper, unsubscribe link, view-in-browser
$routes->get('email-track/open/(:any)',   'EmailTrackController::open/$1',        ['filter' => 'throttle:120,60']);
$routes->get('email-track/click/(:any)',  'EmailTrackController::click/$1',       ['filter' => 'throttle:60,60']);
$routes->get('email-track/unsub/(:any)',  'EmailTrackController::unsubscribe/$1');
$routes->post('email-track/unsub/(:any)', 'EmailTrackController::unsubscribe/$1', ['filter' => 'throttle:5,60']);
$routes->get('email-track/web/(:any)',    'EmailTrackController::webView/$1',     ['filter' => 'throttle:60,60']);

// Email templates (staff)
$routes->group('email-templates', ['filter' => 'auth:email'], static function ($routes) {
    $routes->get('/',                   'EmailTemplatesController::index');
    $routes->get('create',              'EmailTemplatesController::create');
    $routes->post('store',              'EmailTemplatesController::store');
    $routes->get('(:num)/edit',         'EmailTemplatesController::edit/$1');
    $routes->post('(:num)',             'EmailTemplatesController::update/$1');
    $routes->post('(:num)/delete',      'EmailTemplatesController::delete/$1');
    $routes->get('(:num)/preview',      'EmailTemplatesController::preview/$1');
});

// Email logs (staff)
$routes->group('email-logs', ['filter' => 'auth:email'], static function ($routes) {
    $routes->get('/',                          'EmailLogsController::index');
    $routes->get('(:num)',                     'EmailLogsController::show/$1');
    $routes->post('(:num)/retry',              'EmailLogsController::retry/$1');
    $routes->post('unsubscribes/add',          'EmailLogsController::unsubscribeAdd');
    $routes->post('unsubscribes/(:num)/remove','EmailLogsController::unsubscribeRemove/$1');
});

// Documents (global browser across all modules)
$routes->group('documents', ['filter' => 'auth:documents'], static function ($routes) {
    $routes->get('/',                  'DocumentsController::index');
    $routes->get('(:num)/download',    'DocumentsController::download/$1');
    $routes->post('(:num)/verify',     'DocumentsController::verify/$1');
    $routes->post('(:num)/delete',     'DocumentsController::delete/$1');
});

// Internal team comments (staff only) — threaded on bookings, trips, leads
$routes->group('comments', ['filter' => 'auth:comments'], static function ($routes) {
    $routes->post('(booking|trip|lead)/(:num)',               'CommentsController::store/$1/$2');
    $routes->post('(booking|trip|lead)/(:num)/(:num)/delete', 'CommentsController::delete/$1/$2/$3');
});

// Audit logs
$routes->group('audit-logs', ['filter' => 'auth:audit_logs'], static function ($routes) {
    $routes->get('/',        'AuditLogsController::index');
    $routes->get('(:num)',   'AuditLogsController::show/$1');
});

// Reports + vendor scoring
// Reports hub itself only needs login — the view auto-filters cards by what
// each user can actually access. Individual reports are gated by the underlying
// module (Payables → vendor_bills, Receivables → invoices, Lead Funnel → leads,
// …) so URL-typing can't bypass the role boundary.
// Each report is its own permission (report_*) so a role can be granted, say,
// Payables Aging without inheriting Profitability / SOP KPIs / etc. The hub
// itself only needs login — it auto-filters cards by what the user is allowed.
$routes->group('reports', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/',                    'ReportsController::index');
    $routes->get('executive',            'ReportsController::executive',         ['filter' => 'auth:report_sop_kpis']);
    $routes->get('sop-kpis',             'ReportsController::sopKpis',           ['filter' => 'auth:report_sop_kpis']);
    $routes->get('email-analytics',      'ReportsController::emailAnalytics',    ['filter' => 'auth:report_email_analytics']);
    $routes->get('profitability',        'ReportsController::profitability',     ['filter' => 'auth:report_profitability']);
    $routes->get('receivables',          'ReportsController::receivables',       ['filter' => 'auth:report_receivables']);
    $routes->get('payables',             'ReportsController::payables',          ['filter' => 'auth:report_payables']);
    $routes->get('lead-funnel',          'ReportsController::leadFunnel',        ['filter' => 'auth:report_lead_funnel']);
    $routes->get('trip-expenses',        'ReportsController::tripExpenses',      ['filter' => 'auth:report_trip_expenses']);
    $routes->get('unbilled-billable',    'ReportsController::unbilledBillable',  ['filter' => 'auth:report_unbilled_billable']);
    $routes->get('expense-categories',   'ReportsController::expenseCategories', ['filter' => 'auth:report_expense_categories']);
});
$routes->get('vendors/scoring', 'VendorScoringController::index', ['filter' => 'auth:report_vendor_scoring']);

// GST returns — exports for offline upload / accountant handoff
$routes->group('gst-returns', ['filter' => 'auth:invoices'], static function ($routes) {
    $routes->get('/',           'GstReturnsController::index');
    $routes->get('gstr1\.csv',  'GstReturnsController::gstr1');
    $routes->get('gstr3b\.csv', 'GstReturnsController::gstr3b');
});

$routes->group('lead-sources', ['filter' => 'auth:lead_sources'], static function ($routes) {
    $routes->get('/',              'LeadSourcesController::index');
    $routes->post('store',         'LeadSourcesController::store');
    $routes->post('(:num)',        'LeadSourcesController::update/$1');
    $routes->post('(:num)/delete', 'LeadSourcesController::delete/$1');
});

// Cities master — soft master used by clients/vendors/leads/RFQs autocomplete
$routes->get('cities/lookup', 'CitiesController::lookup', ['filter' => 'auth']);
$routes->group('cities', ['filter' => 'auth:cities'], static function ($routes) {
    $routes->get('/',              'CitiesController::index');
    $routes->get('create',         'CitiesController::create');
    $routes->post('store',         'CitiesController::store');
    $routes->get('(:num)/edit',    'CitiesController::edit/$1');
    $routes->post('(:num)',        'CitiesController::update/$1');
    $routes->post('(:num)/delete', 'CitiesController::delete/$1');
});

// Staff-side: manage portal users for a given client
$routes->group('clients/(:num)/portal-users', ['filter' => 'auth:client_portal'], static function ($routes) {
    $routes->get('/',                       'ClientUsersController::index/$1');
    $routes->post('invite',                 'ClientUsersController::invite/$1');
    $routes->post('(:num)/toggle',          'ClientUsersController::toggle/$1/$2');
    $routes->post('(:num)/reset',           'ClientUsersController::resetPassword/$1/$2');
    $routes->post('(:num)/delete',          'ClientUsersController::delete/$1/$2');
});

// ─────────────────────────── Client Portal ───────────────────────────
// Public portal auth (guests only)
$routes->group('portal', ['filter' => 'clientguest'], static function ($routes) {
    $routes->get('login',          'Portal\AuthController::login');
    $routes->post('login',         'Portal\AuthController::attemptLogin', ['filter' => 'throttle:20,60']);
    $routes->get('invite/(:any)',  'Portal\AuthController::acceptInvite/$1');
    $routes->post('invite/(:any)', 'Portal\AuthController::submitInvite/$1', ['filter' => 'throttle:10,300']);
    $routes->get('forgot',          'Portal\AuthController::forgotForm');
    $routes->post('forgot',         'Portal\AuthController::forgotSubmit', ['filter' => 'throttle:5,300']);
    $routes->get('reset/(:any)',    'Portal\AuthController::resetForm/$1');
    $routes->post('reset/(:any)',   'Portal\AuthController::resetSubmit/$1', ['filter' => 'throttle:10,300']);
});

// Authenticated portal — every route is gated by clientauth
$routes->group('portal', ['filter' => 'clientauth'], static function ($routes) {
    $routes->get('/',                              'Portal\DashboardController::index');
    $routes->post('logout',                        'Portal\AuthController::logout');

    // Calendar
    $routes->get('calendar',                       'Portal\CalendarController::index', ['filter' => 'clientauth:view_bookings']);
    $routes->get('calendar/feed',                  'Portal\CalendarController::feed', ['filter' => 'clientauth:view_bookings']);

    // Bookings (read-only) + rebook
    $routes->get('bookings',                       'Portal\BookingsController::index', ['filter' => 'clientauth:view_bookings']);
    $routes->get('bookings/(:num)',                'Portal\BookingsController::show/$1', ['filter' => 'clientauth:view_bookings']);
    $routes->get('bookings/(:num)/rebook',         'Portal\BookingsController::rebook/$1', ['filter' => 'clientauth:request_booking']);

    // Trips (read-only with milestones + last-known GPS)
    $routes->get('trips',                          'Portal\TripsController::index', ['filter' => 'clientauth:view_trips']);
    $routes->get('trips/(:num)',                   'Portal\TripsController::show/$1', ['filter' => 'clientauth:view_trips']);

    // Invoices + PDF
    $routes->get('invoices',                       'Portal\InvoicesController::index', ['filter' => 'clientauth:view_invoices']);
    $routes->get('invoices/(:num)',                'Portal\InvoicesController::show/$1', ['filter' => 'clientauth:view_invoices']);
    $routes->get('invoices/(:num)/pdf',            'Portal\InvoicesController::pdf/$1', ['filter' => 'clientauth:view_invoices']);

    // Ledger
    $routes->get('ledger',                         'Portal\LedgerController::index', ['filter' => 'clientauth:view_ledger']);

    // Booking request (creates a Lead)
    $routes->get('request',                        'Portal\BookingRequestController::form', ['filter' => 'clientauth:request_booking']);
    $routes->post('request',                       'Portal\BookingRequestController::submit', ['filter' => 'clientauth:request_booking']);

    // Profile + password + notification preferences
    $routes->get('profile',                        'Portal\ProfileController::index');
    $routes->post('profile/notifications',         'Portal\ProfileController::saveNotifications');
    $routes->get('profile/change-password',        'Portal\ProfileController::changePasswordForm');
    $routes->post('profile/change-password',       'Portal\ProfileController::changePassword');
});
