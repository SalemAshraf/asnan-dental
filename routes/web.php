<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OdontogramController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TreatmentPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'clinic'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('patients', PatientController::class);
    Route::post('patients/{patient}/consents', [ConsentController::class, 'store'])->name('consents.store');
    Route::post('patients/{patient}/odontogram', [OdontogramController::class, 'upsert'])->name('odontogram.upsert');
    Route::post('patients/{patient}/plans', [TreatmentPlanController::class, 'store'])->name('plans.store');

    Route::post('plans/{plan}/transition', [TreatmentPlanController::class, 'transition'])->name('plans.transition');
    Route::post('plans/{plan}/items', [TreatmentPlanController::class, 'storeItem'])->name('plans.items.store');
    Route::patch('plan-items/{item}', [TreatmentPlanController::class, 'updateItem'])->name('plans.items.update');

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

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/clinic', [SettingsController::class, 'updateClinic'])->name('settings.clinic.update');
    Route::post('settings/branches', [SettingsController::class, 'storeBranch'])->name('settings.branches.store');
    Route::post('settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::post('settings/users/{user}/toggle', [SettingsController::class, 'toggleUser'])->name('settings.users.toggle');

    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
});

