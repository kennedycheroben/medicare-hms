<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MediCare Hospital Management System (Public Website)
|--------------------------------------------------------------------------
*/

// Minimal Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'application' => config('app.name'),
        'environment' => config('app.env'),
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');

// 1. Home
Route::get('/', [PageController::class, 'home'])->name('home');

// 2. About the Hospital
Route::get('/about', [PageController::class, 'about'])->name('about');

// 3. Departments Overview
Route::get('/departments', [PageController::class, 'departments'])->name('departments');

// 4. Department Details
Route::get('/departments/{slug}', [PageController::class, 'departmentDetail'])->name('departments.show');

// 5. Doctor Directory
Route::get('/doctors', [PageController::class, 'doctors'])->name('doctors');

// 6. Doctor Profile
Route::get('/doctors/{id}', [PageController::class, 'doctorProfile'])->name('doctors.show');

// 7. Appointment Booking & Submission
Route::get('/appointments/book', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('throttle:10,1')->name('appointments.store');
Route::get('/appointments/confirmation/{reference}', [AppointmentController::class, 'confirmation'])->name('appointments.confirmation');

// 8. Patient Information & Visiting Guides
Route::get('/patient-info', [PageController::class, 'patientInfo'])->name('patient-info');

// 9. Hospital Services & Specialized Facilities
Route::get('/services', [PageController::class, 'services'])->name('services');

// 10. News, Announcements & Health Advisories
Route::get('/news', [PageController::class, 'news'])->name('news');

// 11. Contact & Enquiries
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');

// 12. Patient Portal Entry
Route::get('/portal/login', [PageController::class, 'portalLogin'])->name('portal.login');

/*
|--------------------------------------------------------------------------
| Staff Administration & RBAC Authentication Routes
|--------------------------------------------------------------------------
*/

// Guest Staff Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'login'])->name('admin.login.submit');
});

// Authenticated Staff Logout
Route::post('/admin/logout', [\App\Http\Controllers\Admin\AdminAuthController::class, 'logout'])->middleware('auth')->name('admin.logout');

// Protected Staff Admin Group
Route::prefix('admin')->middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // Role-restricted routes enforcing least-privilege RBAC matrix
    Route::get('/staff', [\App\Http\Controllers\Admin\ModuleController::class, 'staff'])
        ->middleware('role:super_admin')
        ->name('admin.staff.index');

    Route::get('/reports', [\App\Http\Controllers\Admin\ModuleController::class, 'reports'])
        ->middleware('role:super_admin,hospital_director')
        ->name('admin.reports.index');

    Route::get('/clinical', [\App\Http\Controllers\Admin\ModuleController::class, 'clinical'])
        ->middleware('role:super_admin,doctor')
        ->name('admin.clinical.index');

    Route::get('/triage', [\App\Http\Controllers\Admin\ModuleController::class, 'triage'])
        ->middleware('role:super_admin,nurse,receptionist')
        ->name('admin.triage.index');

    Route::get('/pharmacy', [\App\Http\Controllers\Admin\ModuleController::class, 'pharmacy'])
        ->middleware('role:super_admin,pharmacist')
        ->name('admin.pharmacy.index');

    Route::get('/laboratory', [\App\Http\Controllers\Admin\ModuleController::class, 'laboratory'])
        ->middleware('role:super_admin,lab_technician')
        ->name('admin.laboratory.index');

    Route::get('/billing', [\App\Http\Controllers\Admin\ModuleController::class, 'billing'])
        ->middleware('role:super_admin,cashier')
        ->name('admin.billing.index');

    Route::get('/audit-logs', [\App\Http\Controllers\Admin\ModuleController::class, 'auditLogs'])
        ->middleware('role:super_admin')
        ->name('admin.audit.index');
});

