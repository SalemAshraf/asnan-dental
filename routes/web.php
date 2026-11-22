<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Inertia\Inertia;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OdontogramController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientImageController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TreatmentPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : Inertia::render('Welcome', ['canLogin' => Route::has('login'), 'canRegister' => Route::has('register')]))->name('welcome');

Route::get('/pricing', [\App\Http\Controllers\PricingController::class, 'show'])->name('pricing');
Route::get('/sitemap.xml', function () {
    $base = rtrim(config('app.url'), '/');
    $paths = ['/', '/pricing', '/register', '/login'];
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($paths as $p) {
        $xml .= '<url><loc>'.htmlspecialchars($base.$p, ENT_XML1).'</loc></url>';
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

Route::get('/b/{token}', [\App\Http\Controllers\PublicBookingController::class, 'form'])->name('booking.form');
Route::post('/b/{token}', [\App\Http\Controllers\PublicBookingController::class, 'submit'])->middleware('throttle:10,1')->name('booking.submit');
Route::post('/pricing/inquiry', [\App\Http\Controllers\PricingController::class, 'inquiry'])->middleware('throttle:6,1')->name('pricing.inquiry');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::prefix('platform')->name('platform.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\PlatformController::class, 'loginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\PlatformController::class, 'login'])->name('login.post');
    Route::middleware('platform')->group(function () {
        $c = \App\Http\Controllers\PlatformController::class;
        Route::post('/logout', [$c, 'logout'])->name('logout');
        Route::get('/', [$c, 'dashboard'])->name('dashboard');
        Route::get('/clinics', [$c, 'clinics'])->name('clinics');
        Route::get('/clinics/{clinic}', [$c, 'clinic'])->name('clinic')->whereNumber('clinic');
        Route::patch('/clinics/{clinic}/suspend', [$c, 'suspend'])->name('suspend');
        Route::patch('/clinics/{clinic}/activate', [$c, 'activate'])->name('activate');
        Route::put('/clinics/{clinic}/subscription', [$c, 'subscribe'])->name('subscribe');
        Route::get('/plans', [$c, 'plans'])->name('plans');
        Route::post('/plans', [$c, 'savePlan'])->name('plans.store');
        Route::put('/plans/{plan}', [$c, 'savePlan'])->name('plans.update');
        Route::get('/inquiries', [$c, 'inquiries'])->name('inquiries');
        Route::patch('/inquiries/{inquiry}', [$c, 'inquiryStatus'])->name('inquiries.status');
    });
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Read-only AI connector (Claude): OAuth 2.1 + MCP. Public metadata/registration/token endpoints; consent requires a signed-in user.
Route::get('/.well-known/oauth-protected-resource/{any?}', [\App\Http\Controllers\OauthController::class, 'resourceMetadata'])->where('any', '.*');
Route::get('/.well-known/oauth-authorization-server/{any?}', [\App\Http\Controllers\OauthController::class, 'serverMetadata'])->where('any', '.*');
Route::post('/oauth/register', [\App\Http\Controllers\OauthController::class, 'register'])->middleware('throttle:20,1');
Route::post('/oauth/token', [\App\Http\Controllers\OauthController::class, 'token'])->middleware('throttle:60,1');
Route::post('/oauth/revoke', [\App\Http\Controllers\OauthController::class, 'revoke'])->middleware('throttle:60,1');
Route::post('/mcp', [\App\Http\Controllers\McpController::class, 'handle'])->middleware('throttle:60,1')->name('mcp');
Route::middleware(['auth', 'clinic'])->group(function () {
    Route::get('/oauth/authorize', [\App\Http\Controllers\OauthController::class, 'authorizeForm'])->name('oauth.authorize');
    Route::post('/oauth/authorize', [\App\Http\Controllers\OauthController::class, 'decide'])->name('oauth.decide');
    // Revoking access is always allowed, including for a read-only (pending activation) clinic.
    Route::delete('/settings/ai-connections/{family}', [SettingsController::class, 'aiConnectionRevoke'])->name('settings.ai.disconnect');
});

Route::middleware(['auth', 'clinic', 'writable'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('patients', PatientController::class);
    Route::post('patients/{patient}/consents', [ConsentController::class, 'store'])->name('consents.store');
    Route::post('patients/{patient}/odontogram', [OdontogramController::class, 'upsert'])->name('odontogram.upsert');
    Route::post('patients/{patient}/images', [PatientImageController::class, 'store'])->name('patient-images.store');
    Route::get('patient-images/{image}/file', [PatientImageController::class, 'file'])->name('patient-images.file');
    Route::delete('patient-images/{image}', [PatientImageController::class, 'destroy'])->name('patient-images.destroy');
    Route::post('patients/{patient}/prescriptions', [\App\Http\Controllers\PrescriptionController::class, 'store'])->name('prescriptions.store');
    Route::put('prescriptions/{prescription}', [\App\Http\Controllers\PrescriptionController::class, 'update'])->name('prescriptions.update');
    Route::delete('prescriptions/{prescription}', [\App\Http\Controllers\PrescriptionController::class, 'destroy'])->name('prescriptions.destroy');
    Route::post('patients/{patient}/plans', [TreatmentPlanController::class, 'store'])->name('plans.store');

    Route::post('plans/{plan}/transition', [TreatmentPlanController::class, 'transition'])->name('plans.transition');
    Route::post('plans/{plan}/items', [TreatmentPlanController::class, 'storeItem'])->name('plans.items.store');
    Route::patch('plan-items/{item}', [TreatmentPlanController::class, 'updateItem'])->name('plans.items.update');

    Route::get('recalls', [\App\Http\Controllers\RecallController::class, 'index'])->name('recalls.index');
    Route::post('recalls', [\App\Http\Controllers\RecallController::class, 'store'])->name('recalls.store');
    Route::post('recalls/{recall}/transition', [\App\Http\Controllers\RecallController::class, 'transition'])->name('recalls.transition');
    Route::patch('reminders/{reminder}', [\App\Http\Controllers\ReminderMessageController::class, 'update'])->name('reminders.update');
    Route::get('expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::patch('team/{user}/commission', [\App\Http\Controllers\CommissionController::class, 'update'])->name('team.commission');
    Route::get('lab-cases', [\App\Http\Controllers\LabCaseController::class, 'index'])->name('lab.index');
    Route::post('lab-cases', [\App\Http\Controllers\LabCaseController::class, 'store'])->name('lab.store');
    Route::put('lab-cases/{labCase}', [\App\Http\Controllers\LabCaseController::class, 'update'])->name('lab.update');
    Route::post('lab-cases/{labCase}/transition', [\App\Http\Controllers\LabCaseController::class, 'transition'])->name('lab.transition');
    Route::delete('lab-cases/{labCase}', [\App\Http\Controllers\LabCaseController::class, 'destroy'])->name('lab.destroy');
    Route::get('inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/items', [\App\Http\Controllers\InventoryController::class, 'storeItem'])->name('inventory.items.store');
    Route::post('inventory/movements', [\App\Http\Controllers\InventoryController::class, 'storeMovement'])->name('inventory.movements.store');
    Route::post('inventory/rules', [\App\Http\Controllers\InventoryController::class, 'storeRule'])->name('inventory.rules.store');
    Route::delete('inventory/rules/{serviceSupply}', [\App\Http\Controllers\InventoryController::class, 'destroyRule'])->name('inventory.rules.destroy');

    Route::get('reports', \App\Http\Controllers\ReportController::class)->name('reports');
    Route::get('reports/export', [\App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');

    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('appointments/{appointment}/transition', [AppointmentController::class, 'transition'])->name('appointments.transition');

    Route::resource('services', ServiceController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('plans/{plan}/invoice', [InvoiceController::class, 'storeFromPlan'])->name('invoices.fromPlan');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'addPayment'])->name('invoices.payments.store');

    Route::post('setup-guide/dismiss', [\App\Http\Controllers\SetupGuideController::class, 'dismiss'])->middleware('role:owner')->name('setup.dismiss');
    Route::post('setup-guide/show', [\App\Http\Controllers\SetupGuideController::class, 'show'])->middleware('role:owner')->name('setup.show');
    Route::post('booking-requests/{bookingRequest}/accept', [\App\Http\Controllers\BookingRequestController::class, 'accept'])->name('booking.accept');
    Route::post('booking-requests/{bookingRequest}/decline', [\App\Http\Controllers\BookingRequestController::class, 'decline'])->name('booking.decline');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/clinic', [SettingsController::class, 'updateClinic'])->name('settings.clinic.update');
    Route::post('settings/branches', [SettingsController::class, 'storeBranch'])->name('settings.branches.store');
    Route::post('settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::post('settings/users/{user}/toggle', [SettingsController::class, 'toggleUser'])->name('settings.users.toggle');
    Route::patch('settings/ai', [SettingsController::class, 'aiToggle'])->name('settings.ai');

    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
});

