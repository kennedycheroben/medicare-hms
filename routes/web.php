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
Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
Route::get('/appointments/confirmation/{reference}', [AppointmentController::class, 'confirmation'])->name('appointments.confirmation');

// 8. Patient Information & Visiting Guides
Route::get('/patient-info', [PageController::class, 'patientInfo'])->name('patient-info');

// 9. Hospital Services & Specialized Facilities
Route::get('/services', [PageController::class, 'services'])->name('services');

// 10. News, Announcements & Health Advisories
Route::get('/news', [PageController::class, 'news'])->name('news');

// 11. Contact & Enquiries
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// 12. Patient Portal Entry
Route::get('/portal/login', [PageController::class, 'portalLogin'])->name('portal.login');
