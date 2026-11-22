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
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TreatmentPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : Inertia::render('Welcome', ['canLogin' => Route::has('login'), 'canRegister' => Route::has('register')]))->name('welcome');

Route::get('/pricing', [\App\Http\Controllers\PricingController::class, 'show'])->name('pricing');
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

Route::middleware(['auth', 'clinic', 'writable'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('patients', PatientController::class);
    Route::post('patients/{patient}/consents', [ConsentController::class, 'store'])->name('consents.store');
    Route::post('patients/{patient}/odontogram', [OdontogramController::class, 'upsert'])->name('odontogram.upsert');
    Route::post('patients/{patient}/plans', [TreatmentPlanController::class, 'store'])->name('plans.store');

    Route::post('plans/{plan}/transition', [TreatmentPlanController::class, 'transition'])->name('plans.transition');
    Route::post('plans/{plan}/items', [TreatmentPlanController::class, 'storeItem'])->name('plans.items.store');
    Route::patch('plan-items/{item}', [TreatmentPlanController::class, 'updateItem'])->name('plans.items.update');

    Route::get('recalls', [\App\Http\Controllers\RecallController::class, 'index'])->name('recalls.index');
    Route::post('recalls', [\App\Http\Controllers\RecallController::class, 'store'])->name('recalls.store');
    Route::post('recalls/{recall}/transition', [\App\Http\Controllers\RecallController::class, 'transition'])->name('recalls.transition');

    Route::get('reports', \App\Http\Controllers\ReportController::class)->name('reports');

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
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/clinic', [SettingsController::class, 'updateClinic'])->name('settings.clinic.update');
    Route::post('settings/branches', [SettingsController::class, 'storeBranch'])->name('settings.branches.store');
    Route::post('settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::post('settings/users/{user}/toggle', [SettingsController::class, 'toggleUser'])->name('settings.users.toggle');

    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
});

