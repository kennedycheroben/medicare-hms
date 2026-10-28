<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AppointmentManagementController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\ContactInquiryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DoctorManagementController;
use App\Http\Controllers\Admin\DoctorProfileController;
use App\Http\Controllers\Admin\IntakeController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PatientPortalAccessController;
use App\Http\Controllers\Admin\PharmacyController;
use App\Http\Controllers\Admin\PrescriptionController;
use App\Http\Controllers\Admin\StaffManagementController;
use App\Http\Controllers\Admin\StaffTaskController;
use App\Http\Controllers\Admin\TriageController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DoctorReviewSubmissionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PatientPortalController;
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
Route::post('/doctors/{doctor}/reviews', [DoctorReviewSubmissionController::class, 'store'])
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
    Route::get('/login', [PatientPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [PatientPortalController::class, 'login'])->middleware('throttle:20,1')->name('login.store');
    Route::get('/forgot-password', [PatientPortalController::class, 'showForgot'])->name('forgot');
    Route::post('/forgot-password', [PatientPortalController::class, 'sendReset'])->middleware('throttle:10,1')->name('forgot.send');
    Route::get('/activate/{token}', [PatientPortalController::class, 'showActivate'])->name('activate');
    Route::post('/activate/{token}', [PatientPortalController::class, 'activate'])->middleware('throttle:10,1')->name('activate.store');

    Route::middleware('patient.auth')->group(function () {
        Route::get('/', [PatientPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/password', [PatientPortalController::class, 'editPassword'])->name('password.edit');
        Route::put('/password', [PatientPortalController::class, 'updatePassword'])->name('password.update');
        Route::post('/logout', [PatientPortalController::class, 'logout'])->name('logout');
    });
});

/*
|--------------------------------------------------------------------------
| Staff Administration & RBAC Authentication Routes
|--------------------------------------------------------------------------
*/

// Guest Staff Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});

// Authenticated Staff Logout
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth:web')->name('admin.logout');

// Protected Staff Admin Group
Route::prefix('admin')->middleware(['auth:web', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Notifications (All authenticated staff)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('admin.notifications.unread-count');
    Route::match(['GET', 'POST'], '/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('admin.notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('admin.notifications.read-all');

    // Notification Oversight (SuperAdmin & Hospital Director)
    Route::middleware('role:super_admin,hospital_director')->group(function () {
        Route::get('/notifications-oversight', [NotificationController::class, 'oversight'])->name('admin.notifications.oversight');
    });

    // 1. Staff Management (SuperAdmin only)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/staff', [StaffManagementController::class, 'index'])->name('admin.staff.index');
        Route::get('/staff/create', [StaffManagementController::class, 'create'])->name('admin.staff.create');
        Route::post('/staff', [StaffManagementController::class, 'store'])->name('admin.staff.store');
        Route::get('/staff/{staff}/edit', [StaffManagementController::class, 'edit'])->name('admin.staff.edit');
        Route::put('/staff/{staff}', [StaffManagementController::class, 'update'])->name('admin.staff.update');
        Route::post('/staff/{staff}/toggle-status', [StaffManagementController::class, 'toggleStatus'])->name('admin.staff.toggle-status');
    });

    // 2. Patient Master Index (PMI)
    Route::get('/patients', [PatientController::class, 'index'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.patients.index');

    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::get('/patients/create', [PatientController::class, 'create'])->name('admin.patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('admin.patients.store');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('admin.patients.edit');
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('admin.patients.update');
        Route::post('/patients/{patient}/portal-access', [PatientPortalAccessController::class, 'issue'])->middleware('throttle:20,1')->name('admin.patients.portal-access');
    });

    Route::get('/patients/{patient}', [PatientController::class, 'show'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.patients.show');

    // 3. Front-Desk Intake & Visits
    Route::get('/intake', [IntakeController::class, 'index'])
        ->middleware('role:super_admin,receptionist,nurse')
        ->name('admin.intake.index');

    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::post('/intake/walk-in', [IntakeController::class, 'walkIn'])->name('admin.intake.walk-in');
        Route::post('/intake/check-in', [IntakeController::class, 'checkInAppointment'])->name('admin.intake.check-in');
    });

    // 3b. Staff Appointment Management & Workflow
    Route::get('/appointments', [AppointmentManagementController::class, 'index'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.appointments.index');

    Route::get('/appointments/{appointment}', [AppointmentManagementController::class, 'show'])
        ->middleware('role:super_admin,hospital_director,doctor,nurse,receptionist')
        ->name('admin.appointments.show');

    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::post('/appointments/{appointment}/assign', [AppointmentManagementController::class, 'assign'])->name('admin.appointments.assign');
        Route::post('/appointments/{appointment}/confirm', [AppointmentManagementController::class, 'confirm'])->name('admin.appointments.confirm');
        Route::post('/appointments/{appointment}/reschedule', [AppointmentManagementController::class, 'reschedule'])->name('admin.appointments.reschedule');
        Route::post('/appointments/{appointment}/cancel', [AppointmentManagementController::class, 'cancel'])->name('admin.appointments.cancel');
        Route::post('/appointments/{appointment}/reconcile', [AppointmentManagementController::class, 'reconcile'])->name('admin.appointments.reconcile');
        Route::post('/appointments/{appointment}/check-in', [AppointmentManagementController::class, 'checkIn'])->name('admin.appointments.check-in');
    });

    // 4. Nursing Triage Station & Vital Signs
    Route::get('/triage', [TriageController::class, 'index'])
        ->middleware('role:super_admin,nurse,receptionist')
        ->name('admin.triage.index');

    Route::middleware('role:super_admin,nurse')->group(function () {
        Route::get('/triage/{visit}/assess', [TriageController::class, 'assess'])->name('admin.triage.assess');
        Route::post('/triage/{visit}/assess', [TriageController::class, 'store'])->name('admin.triage.store');
    });

    // 5. Functional Module Shells for other roles
    Route::get('/reports', [ModuleController::class, 'reports'])
        ->middleware('role:super_admin,hospital_director')
        ->name('admin.reports.index');

    // Clinical Overview Shell (Accessible by SuperAdmin & Doctor)
    Route::get('/clinical', [ModuleController::class, 'clinical'])
        ->middleware('role:super_admin,doctor')
        ->name('admin.clinical.index');

    // 5. Doctor Clinical Consultations & EHR Workspace (Doctor only)
    Route::middleware('role:doctor')->group(function () {
        Route::get('/doctor/profile', [DoctorProfileController::class, 'edit'])->name('doctor.profile.edit');
        Route::put('/doctor/profile', [DoctorProfileController::class, 'update'])->name('doctor.profile.update');

        Route::get('/consultations', [ConsultationController::class, 'index'])->name('admin.consultations.index');
        Route::post('/consultations/start/{visit}', [ConsultationController::class, 'start'])->name('admin.consultations.start');
        Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])->name('admin.consultations.show');
        Route::put('/consultations/{consultation}', [ConsultationController::class, 'update'])->name('admin.consultations.update');
        Route::post('/consultations/{consultation}/sign', [ConsultationController::class, 'sign'])->name('admin.consultations.sign');
        Route::post('/consultations/{consultation}/addenda', [ConsultationController::class, 'addendum'])->name('admin.consultations.addenda.store');
        Route::post('/consultations/{consultation}/prescriptions', [PrescriptionController::class, 'store'])->name('admin.prescriptions.store');
        Route::post('/prescriptions/{prescription}/finalize', [PrescriptionController::class, 'finalize'])->name('admin.prescriptions.finalize');
    });

    // Prescriptions Viewing & Cancellation
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])
        ->middleware('role:doctor,pharmacist,super_admin')
        ->name('admin.prescriptions.show');
    Route::post('/prescriptions/{prescription}/cancel', [PrescriptionController::class, 'cancel'])
        ->middleware('role:doctor,pharmacist,super_admin')
        ->name('admin.prescriptions.cancel');

    // 6. Pharmacy Dispensary Queue (SuperAdmin & Pharmacist view queue; Dispensing pharmacist-only)
    Route::get('/pharmacy', [PharmacyController::class, 'index'])
        ->middleware('role:super_admin,pharmacist')
        ->name('admin.pharmacy.index');

    Route::middleware('role:pharmacist')->group(function () {
        Route::get('/pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'show'])->name('admin.pharmacy.prescriptions.show');
        Route::post('/pharmacy/items/{item}/dispense', [PharmacyController::class, 'dispense'])->name('admin.pharmacy.dispense');
        Route::post('/pharmacy/items/{item}/decline', [PharmacyController::class, 'decline'])->name('admin.pharmacy.decline');
        Route::get('/pharmacy/history', [PharmacyController::class, 'history'])->name('admin.pharmacy.history');
    });

    // 7. Medicine Inventory & Stock Movements
    Route::middleware('role:super_admin,hospital_director,pharmacist')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory.index');
        Route::get('/inventory/medicines/{medicine}', [InventoryController::class, 'show'])->name('admin.inventory.medicines.show');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('admin.inventory.movements');
    });

    Route::middleware('role:super_admin,pharmacist')->group(function () {
        Route::post('/inventory/medicines', [InventoryController::class, 'storeMedicine'])->name('admin.inventory.medicines.store');
        Route::post('/inventory/medicines/{medicine}/batches', [InventoryController::class, 'storeBatch'])->name('admin.inventory.batches.store');
        Route::post('/inventory/medicines/{medicine}/otc-sale', [InventoryController::class, 'otcSale'])->name('admin.inventory.medicines.otc-sale');
        Route::post('/inventory/batches/{batch}/adjust', [InventoryController::class, 'adjustStock'])->name('admin.inventory.batches.adjust');
        Route::post('/inventory/batches/{batch}/internal-use', [InventoryController::class, 'internalUse'])->name('admin.inventory.batches.internal-use');
        Route::post('/inventory/batches/{batch}/wastage', [InventoryController::class, 'wastage'])->name('admin.inventory.batches.wastage');
        Route::post('/inventory/batches/{batch}/return', [InventoryController::class, 'returnStock'])->name('admin.inventory.batches.return');
    });

    Route::get('/laboratory', [ModuleController::class, 'laboratory'])
        ->middleware('role:super_admin,lab_technician')
        ->name('admin.laboratory.index');

    Route::get('/billing', [ModuleController::class, 'billing'])
        ->middleware('role:super_admin,cashier')
        ->name('admin.billing.index');

    Route::get('/audit-logs', [ModuleController::class, 'auditLogs'])
        ->middleware('role:super_admin')
        ->name('admin.audit.index');

    // 8. Doctor Profile Verification & Review Moderation (SuperAdmin & Hospital Director)
    Route::middleware('role:super_admin,hospital_director')->group(function () {
        Route::get('/doctors-directory', [DoctorManagementController::class, 'index'])->name('admin.doctors.index');
        Route::get('/doctors-directory/{doctor}/edit', [DoctorManagementController::class, 'edit'])->name('admin.doctors.edit');
        Route::put('/doctors-directory/{doctor}', [DoctorManagementController::class, 'update'])->name('admin.doctors.update');
        Route::post('/doctors-directory/{doctor}/publish', [DoctorManagementController::class, 'publish'])->name('admin.doctors.publish');
        Route::post('/doctors-directory/{doctor}/unpublish', [DoctorManagementController::class, 'unpublish'])->name('admin.doctors.unpublish');
        Route::post('/doctors-directory/{doctor}/approve', [DoctorManagementController::class, 'approveProfile'])->name('admin.doctors.approve');
        Route::post('/doctors-directory/{doctor}/return', [DoctorManagementController::class, 'returnForCorrection'])->name('admin.doctors.return');

        Route::get('/doctor-reviews', [DoctorManagementController::class, 'reviews'])->name('admin.doctors.reviews');
        Route::post('/doctor-reviews/{review}/approve', [DoctorManagementController::class, 'approveReview'])->name('admin.doctors.reviews.approve');
        Route::post('/doctor-reviews/{review}/reject', [DoctorManagementController::class, 'rejectReview'])->name('admin.doctors.reviews.reject');
    });

    // 9. News CMS (draft/edit/preview; publish & unpublish additionally enforced in controller)
    Route::middleware('role:super_admin,hospital_director')->prefix('news')->group(function () {
        $c = ArticleController::class;
        Route::get('/', [$c, 'index'])->name('admin.articles.index');
        Route::get('/create', [$c, 'create'])->name('admin.articles.create');
        Route::post('/', [$c, 'store'])->name('admin.articles.store');
        Route::get('/{article}/edit', [$c, 'edit'])->name('admin.articles.edit');
        Route::put('/{article}', [$c, 'update'])->name('admin.articles.update');
        Route::get('/{article}/preview', [$c, 'preview'])->name('admin.articles.preview');
        Route::post('/{article}/publish', [$c, 'publish'])->name('admin.articles.publish');
        Route::post('/{article}/unpublish', [$c, 'unpublish'])->name('admin.articles.unpublish');
    });

    // 10. Public Contact Enquiries & Support Tickets (SuperAdmin, Hospital Director, Receptionist)
    Route::middleware('role:super_admin,hospital_director,receptionist')->prefix('inquiries')->group(function () {
        Route::get('/', [ContactInquiryController::class, 'index'])->name('admin.inquiries.index');
        Route::get('/{inquiry}', [ContactInquiryController::class, 'show'])->name('admin.inquiries.show');
        Route::post('/{inquiry}/status', [ContactInquiryController::class, 'updateStatus'])->name('admin.inquiries.status');
    });

    // 11. Staff Task Delegations & Follow-Up (All Authenticated Staff)
    Route::prefix('tasks')->group(function () {
        Route::get('/', [StaffTaskController::class, 'index'])->name('admin.tasks.index');
        Route::post('/', [StaffTaskController::class, 'store'])->name('admin.tasks.store');
        Route::get('/{task}', [StaffTaskController::class, 'show'])->name('admin.tasks.show');
        Route::put('/{task}', [StaffTaskController::class, 'update'])->name('admin.tasks.update');
    });
});
