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

// 6. Doctor Profile & Verified Patient Review Submission
Route::get('/doctors/{id}', [PageController::class, 'doctorProfile'])->name('doctors.show');
Route::post('/doctors/{doctor}/reviews', [\App\Http\Controllers\DoctorReviewSubmissionController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('doctors.reviews.store');

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
Route::get('/news/{slug}', [PageController::class, 'newsShow'])->name('news.show');

// 11. Contact & Enquiries
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');

// 12. Legal Documentation & User Guide
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/guide', [PageController::class, 'guide'])->name('guide');

// 12. Patient Portal Entry
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\PatientPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\PatientPortalController::class, 'login'])->middleware('throttle:20,1')->name('login.store');
    Route::get('/forgot-password', [\App\Http\Controllers\PatientPortalController::class, 'showForgot'])->name('forgot');
    Route::post('/forgot-password', [\App\Http\Controllers\PatientPortalController::class, 'sendReset'])->middleware('throttle:10,1')->name('forgot.send');
    Route::get('/activate/{token}', [\App\Http\Controllers\PatientPortalController::class, 'showActivate'])->name('activate');
    Route::post('/activate/{token}', [\App\Http\Controllers\PatientPortalController::class, 'activate'])->middleware('throttle:10,1')->name('activate.store');

    Route::middleware('patient.auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\PatientPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/password', [\App\Http\Controllers\PatientPortalController::class, 'editPassword'])->name('password.edit');
        Route::put('/password', [\App\Http\Controllers\PatientPortalController::class, 'updatePassword'])->name('password.update');
        Route::post('/logout', [\App\Http\Controllers\PatientPortalController::class, 'logout'])->name('logout');
    });
});

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

    // 1. Staff Management (SuperAdmin only)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/staff', [\App\Http\Controllers\Admin\StaffManagementController::class, 'index'])->name('admin.staff.index');
        Route::get('/staff/create', [\App\Http\Controllers\Admin\StaffManagementController::class, 'create'])->name('admin.staff.create');
        Route::post('/staff', [\App\Http\Controllers\Admin\StaffManagementController::class, 'store'])->name('admin.staff.store');
        Route::get('/staff/{staff}/edit', [\App\Http\Controllers\Admin\StaffManagementController::class, 'edit'])->name('admin.staff.edit');
        Route::put('/staff/{staff}', [\App\Http\Controllers\Admin\StaffManagementController::class, 'update'])->name('admin.staff.update');
        Route::post('/staff/{staff}/toggle-status', [\App\Http\Controllers\Admin\StaffManagementController::class, 'toggleStatus'])->name('admin.staff.toggle-status');
    });

    // 2. Patient Master Index (PMI)
    Route::get('/patients', [\App\Http\Controllers\Admin\PatientController::class, 'index'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.patients.index');

    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::get('/patients/create', [\App\Http\Controllers\Admin\PatientController::class, 'create'])->name('admin.patients.create');
        Route::post('/patients', [\App\Http\Controllers\Admin\PatientController::class, 'store'])->name('admin.patients.store');
        Route::get('/patients/{patient}/edit', [\App\Http\Controllers\Admin\PatientController::class, 'edit'])->name('admin.patients.edit');
        Route::put('/patients/{patient}', [\App\Http\Controllers\Admin\PatientController::class, 'update'])->name('admin.patients.update');
        Route::post('/patients/{patient}/portal-access', [\App\Http\Controllers\Admin\PatientPortalAccessController::class, 'issue'])->middleware('throttle:20,1')->name('admin.patients.portal-access');
    });

    Route::get('/patients/{patient}', [\App\Http\Controllers\Admin\PatientController::class, 'show'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.patients.show');

    // 3. Front-Desk Intake & Visits
    Route::get('/intake', [\App\Http\Controllers\Admin\IntakeController::class, 'index'])
        ->middleware('role:super_admin,receptionist,nurse')
        ->name('admin.intake.index');

    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::post('/intake/walk-in', [\App\Http\Controllers\Admin\IntakeController::class, 'walkIn'])->name('admin.intake.walk-in');
        Route::post('/intake/check-in', [\App\Http\Controllers\Admin\IntakeController::class, 'checkInAppointment'])->name('admin.intake.check-in');
    });

    // 3b. Staff Appointment Management & Workflow
    Route::get('/appointments', [\App\Http\Controllers\Admin\AppointmentManagementController::class, 'index'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.appointments.index');

    Route::get('/appointments/{appointment}', [\App\Http\Controllers\Admin\AppointmentManagementController::class, 'show'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.appointments.show');

    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::post('/appointments/{appointment}/confirm', [\App\Http\Controllers\Admin\AppointmentManagementController::class, 'confirm'])->name('admin.appointments.confirm');
        Route::post('/appointments/{appointment}/reschedule', [\App\Http\Controllers\Admin\AppointmentManagementController::class, 'reschedule'])->name('admin.appointments.reschedule');
        Route::post('/appointments/{appointment}/cancel', [\App\Http\Controllers\Admin\AppointmentManagementController::class, 'cancel'])->name('admin.appointments.cancel');
        Route::post('/appointments/{appointment}/reconcile', [\App\Http\Controllers\Admin\AppointmentManagementController::class, 'reconcile'])->name('admin.appointments.reconcile');
        Route::post('/appointments/{appointment}/check-in', [\App\Http\Controllers\Admin\AppointmentManagementController::class, 'checkIn'])->name('admin.appointments.check-in');
    });

    // 4. Nursing Triage Station & Vital Signs
    Route::get('/triage', [\App\Http\Controllers\Admin\TriageController::class, 'index'])
        ->middleware('role:super_admin,nurse,receptionist')
        ->name('admin.triage.index');

    Route::middleware('role:super_admin,nurse')->group(function () {
        Route::get('/triage/{visit}/assess', [\App\Http\Controllers\Admin\TriageController::class, 'assess'])->name('admin.triage.assess');
        Route::post('/triage/{visit}/assess', [\App\Http\Controllers\Admin\TriageController::class, 'store'])->name('admin.triage.store');
    });

    // 5. Functional Module Shells for other roles
    Route::get('/reports', [\App\Http\Controllers\Admin\ModuleController::class, 'reports'])
        ->middleware('role:super_admin,hospital_director')
        ->name('admin.reports.index');

    // Clinical Overview Shell (Accessible by SuperAdmin & Doctor)
    Route::get('/clinical', [\App\Http\Controllers\Admin\ModuleController::class, 'clinical'])
        ->middleware('role:super_admin,doctor')
        ->name('admin.clinical.index');

    // 5. Doctor Clinical Consultations & EHR Workspace (Doctor only)
    Route::middleware('role:doctor')->group(function () {
        Route::get('/consultations', [\App\Http\Controllers\Admin\ConsultationController::class, 'index'])->name('admin.consultations.index');
        Route::post('/consultations/start/{visit}', [\App\Http\Controllers\Admin\ConsultationController::class, 'start'])->name('admin.consultations.start');
        Route::get('/consultations/{consultation}', [\App\Http\Controllers\Admin\ConsultationController::class, 'show'])->name('admin.consultations.show');
        Route::put('/consultations/{consultation}', [\App\Http\Controllers\Admin\ConsultationController::class, 'update'])->name('admin.consultations.update');
        Route::post('/consultations/{consultation}/sign', [\App\Http\Controllers\Admin\ConsultationController::class, 'sign'])->name('admin.consultations.sign');
        Route::post('/consultations/{consultation}/addenda', [\App\Http\Controllers\Admin\ConsultationController::class, 'addendum'])->name('admin.consultations.addenda.store');
        Route::post('/consultations/{consultation}/prescriptions', [\App\Http\Controllers\Admin\PrescriptionController::class, 'store'])->name('admin.prescriptions.store');
        Route::post('/prescriptions/{prescription}/finalize', [\App\Http\Controllers\Admin\PrescriptionController::class, 'finalize'])->name('admin.prescriptions.finalize');
    });

    // Prescriptions Viewing & Cancellation
    Route::get('/prescriptions/{prescription}', [\App\Http\Controllers\Admin\PrescriptionController::class, 'show'])
        ->middleware('role:doctor,pharmacist,super_admin')
        ->name('admin.prescriptions.show');
    Route::post('/prescriptions/{prescription}/cancel', [\App\Http\Controllers\Admin\PrescriptionController::class, 'cancel'])
        ->middleware('role:doctor,pharmacist,super_admin')
        ->name('admin.prescriptions.cancel');

    // 6. Pharmacy Dispensary Queue (SuperAdmin & Pharmacist view queue; Dispensing pharmacist-only)
    Route::get('/pharmacy', [\App\Http\Controllers\Admin\PharmacyController::class, 'index'])
        ->middleware('role:super_admin,pharmacist')
        ->name('admin.pharmacy.index');

    Route::middleware('role:pharmacist')->group(function () {
        Route::get('/pharmacy/prescriptions/{prescription}', [\App\Http\Controllers\Admin\PharmacyController::class, 'show'])->name('admin.pharmacy.prescriptions.show');
        Route::post('/pharmacy/items/{item}/dispense', [\App\Http\Controllers\Admin\PharmacyController::class, 'dispense'])->name('admin.pharmacy.dispense');
        Route::post('/pharmacy/items/{item}/decline', [\App\Http\Controllers\Admin\PharmacyController::class, 'decline'])->name('admin.pharmacy.decline');
        Route::get('/pharmacy/history', [\App\Http\Controllers\Admin\PharmacyController::class, 'history'])->name('admin.pharmacy.history');
    });

    // 7. Medicine Inventory & Stock Movements
    Route::middleware('role:super_admin,hospital_director,pharmacist')->group(function () {
        Route::get('/inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('admin.inventory.index');
        Route::get('/inventory/medicines/{medicine}', [\App\Http\Controllers\Admin\InventoryController::class, 'show'])->name('admin.inventory.medicines.show');
        Route::get('/inventory/movements', [\App\Http\Controllers\Admin\InventoryController::class, 'movements'])->name('admin.inventory.movements');
    });

    Route::middleware('role:super_admin,pharmacist')->group(function () {
        Route::post('/inventory/medicines', [\App\Http\Controllers\Admin\InventoryController::class, 'storeMedicine'])->name('admin.inventory.medicines.store');
        Route::post('/inventory/medicines/{medicine}/batches', [\App\Http\Controllers\Admin\InventoryController::class, 'storeBatch'])->name('admin.inventory.batches.store');
        Route::post('/inventory/medicines/{medicine}/otc-sale', [\App\Http\Controllers\Admin\InventoryController::class, 'otcSale'])->name('admin.inventory.medicines.otc-sale');
        Route::post('/inventory/batches/{batch}/adjust', [\App\Http\Controllers\Admin\InventoryController::class, 'adjustStock'])->name('admin.inventory.batches.adjust');
        Route::post('/inventory/batches/{batch}/internal-use', [\App\Http\Controllers\Admin\InventoryController::class, 'internalUse'])->name('admin.inventory.batches.internal-use');
        Route::post('/inventory/batches/{batch}/wastage', [\App\Http\Controllers\Admin\InventoryController::class, 'wastage'])->name('admin.inventory.batches.wastage');
        Route::post('/inventory/batches/{batch}/return', [\App\Http\Controllers\Admin\InventoryController::class, 'returnStock'])->name('admin.inventory.batches.return');
    });

    Route::get('/laboratory', [\App\Http\Controllers\Admin\ModuleController::class, 'laboratory'])
        ->middleware('role:super_admin,lab_technician')
        ->name('admin.laboratory.index');

    Route::get('/billing', [\App\Http\Controllers\Admin\ModuleController::class, 'billing'])
        ->middleware('role:super_admin,cashier')
        ->name('admin.billing.index');

    Route::get('/audit-logs', [\App\Http\Controllers\Admin\ModuleController::class, 'auditLogs'])
        ->middleware('role:super_admin')
        ->name('admin.audit.index');

    // 8. Doctor Profile Verification & Review Moderation (SuperAdmin & Hospital Director)
    Route::middleware('role:super_admin,hospital_director')->group(function () {
        Route::get('/doctors-directory', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'index'])->name('admin.doctors.index');
        Route::get('/doctors-directory/{doctor}/edit', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'edit'])->name('admin.doctors.edit');
        Route::put('/doctors-directory/{doctor}', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'update'])->name('admin.doctors.update');
        Route::post('/doctors-directory/{doctor}/publish', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'publish'])->name('admin.doctors.publish');
        Route::post('/doctors-directory/{doctor}/unpublish', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'unpublish'])->name('admin.doctors.unpublish');

        Route::get('/doctor-reviews', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'reviews'])->name('admin.doctors.reviews');
        Route::post('/doctor-reviews/{review}/approve', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'approveReview'])->name('admin.doctors.reviews.approve');
        Route::post('/doctor-reviews/{review}/reject', [\App\Http\Controllers\Admin\DoctorManagementController::class, 'rejectReview'])->name('admin.doctors.reviews.reject');
    });

    // 9. News CMS (draft/edit/preview; publish & unpublish additionally enforced in controller)
    Route::middleware('role:super_admin,hospital_director')->prefix('news')->group(function () {
        $c = \App\Http\Controllers\Admin\ArticleController::class;
        Route::get('/', [$c, 'index'])->name('admin.articles.index');
        Route::get('/create', [$c, 'create'])->name('admin.articles.create');
        Route::post('/', [$c, 'store'])->name('admin.articles.store');
        Route::get('/{article}/edit', [$c, 'edit'])->name('admin.articles.edit');
        Route::put('/{article}', [$c, 'update'])->name('admin.articles.update');
        Route::get('/{article}/preview', [$c, 'preview'])->name('admin.articles.preview');
        Route::post('/{article}/publish', [$c, 'publish'])->name('admin.articles.publish');
        Route::post('/{article}/unpublish', [$c, 'unpublish'])->name('admin.articles.unpublish');
    });
});

