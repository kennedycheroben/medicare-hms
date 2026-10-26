@extends('layouts.public')

@section('title', 'User Workbook & Practical System Guide')
@section('meta_description', 'Official User Workbook and Operations Manual for patients, clinicians, and administrative staff using ' . config('hospital.name', 'MediCare Hospital') . '.')

@section('content')

    <x-page-header
        title="User Workbook &amp; Guide"
        subtitle="Practical Operational Manual for Patients, Clinicians &amp; Administrative Teams"
        :breadcrumbs="[['label' => 'System Guide']]"
    />

    <section class="py-5 bg-white">
        <div class="container" style="max-width: 1000px;">

            <!-- Overview Header -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-5 bg-light">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <span class="badge bg-primary px-3 py-2 rounded-pill mb-3 fw-bold">Hospital Operations Manual</span>
                        <h2 class="fw-bold mb-3" style="color: var(--heading-color);">Welcome to MediCare HMS</h2>
                        <p class="text-secondary leading-relaxed mb-0">
                            MediCare Hospital Management System (HMS) is a unified digital healthcare platform designed to streamline outpatient care, clinical triage, electronic health records (EHR), physician consultations, FEFO pharmacy dispensing, inventory tracking, and patient engagement across our medical facility.
                        </p>
                    </div>
                    <div class="col-lg-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 110px; height: 110px;">
                            <i class="bi bi-book-half text-primary fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table of Contents Navigation Pills -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-5 bg-white border">
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="#welcome" class="btn btn-sm btn-outline-primary rounded-pill">1. Platform Overview</a>
                    <a href="#getting-started" class="btn btn-sm btn-outline-primary rounded-pill">2. Getting Started</a>
                    <a href="#patient-flows" class="btn btn-sm btn-outline-primary rounded-pill">3. Patient Guide</a>
                    <a href="#clinical-workflows" class="btn btn-sm btn-outline-primary rounded-pill">4. Clinical &amp; Staff Guide</a>
                    <a href="#pharmacy-inventory" class="btn btn-sm btn-outline-primary rounded-pill">5. Pharmacy &amp; Inventory</a>
                    <a href="#security-privacy" class="btn btn-sm btn-outline-primary rounded-pill">6. Security &amp; Privacy</a>
                    <a href="#troubleshooting" class="btn btn-sm btn-outline-primary rounded-pill">7. Common Questions &amp; Support</a>
                </div>
            </div>

            <!-- 1. Platform Overview -->
            <div id="welcome" class="mb-5 pt-3">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">1. Who is MediCare HMS For?</h3>
                <p class="text-secondary leading-relaxed">
                    The platform caters to two core audiences through purpose-built interfaces:
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-primary border-3">
                            <h5 class="fw-bold text-primary mb-2"><i class="bi bi-person-heart me-2"></i>For Patients</h5>
                            <ul class="text-secondary small ps-3 mb-0">
                                <li class="mb-2">Search clinical specialists and clinic schedules.</li>
                                <li class="mb-2">Book outpatient consultation requests online.</li>
                                <li class="mb-2">Activate private Patient Portal accounts via secure email links.</li>
                                <li class="mb-0">Access visit history, upcoming appointments, and medication prescriptions.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-success border-3">
                            <h5 class="fw-bold text-success mb-2"><i class="bi bi-hospital me-2"></i>For Hospital Staff</h5>
                            <ul class="text-secondary small ps-3 mb-0">
                                <li class="mb-2"><strong>Reception:</strong> Patient Master Index (PMI), intake check-in, appointment management.</li>
                                <li class="mb-2"><strong>Nurses:</strong> Initial Assessment &amp; Vital Signs recorded during clinical triage.</li>
                                <li class="mb-2"><strong>Doctors:</strong> Consultation workbench, clinical diagnoses, formulary e-prescriptions.</li>
                                <li class="mb-2"><strong>Pharmacists:</strong> FEFO batch dispensing, inventory adjustments, OTC sales, wastage.</li>
                                <li class="mb-0"><strong>Leadership:</strong> Audit trails, physician publication, and news CMS.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- 2. Getting Started & Sign-In -->
            <div id="getting-started" class="mb-5 pt-3">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">2. Getting Started &amp; Authentication</h3>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-4 border rounded-4 bg-light h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-person-lock me-2 text-primary"></i>Patient Portal Sign-In</h6>
                            <p class="small text-secondary mb-3">
                                Registered patients sign in using either their <strong>Medical Record Number (MRN)</strong> (e.g. <code>MRN-94821</code>) or registered email address alongside their secure password.
                            </p>
                            <ol class="small text-secondary ps-3 mb-0">
                                <li class="mb-1">Navigate to <a href="{{ route('portal.login') }}" class="text-primary fw-semibold">Patient Portal Login</a>.</li>
                                <li class="mb-1">Enter your MRN or Email and Password.</li>
                                <li class="mb-0">If first time, activate your account via the email link provided by registry staff.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 border rounded-4 bg-light h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock me-2 text-success"></i>Staff Administration Sign-In</h6>
                            <p class="small text-secondary mb-3">
                                Hospital practitioners and operational personnel authenticate through the secure staff portal.
                            </p>
                            <ol class="small text-secondary ps-3 mb-0">
                                <li class="mb-1">Visit <a href="{{ route('admin.login') }}" class="text-success fw-semibold">Staff Administrative Portal</a>.</li>
                                <li class="mb-1">Enter authorized staff email and password.</li>
                                <li class="mb-0">Staff sessions are automatically routed to your role-specific dashboard with RBAC enforcement.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- 3. Patient Workflows -->
            <div id="patient-flows" class="mb-5 pt-3">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">3. Step-by-Step Patient Workflows</h3>

                <div class="accordion" id="patientAccordion">
                    <!-- Workflow 1: Booking -->
                    <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden">
                        <h2 class="accordion-header" id="headingBooking">
                            <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBooking" aria-expanded="true" aria-controls="collapseBooking">
                                <i class="bi bi-calendar-plus text-primary me-2"></i>How to Book an Outpatient Appointment Online
                            </button>
                        </h2>
                        <div id="collapseBooking" class="accordion-collapse collapse show" aria-labelledby="headingBooking" data-bs-parent="#patientAccordion">
                            <div class="accordion-body text-secondary small leading-relaxed">
                                <ol class="ps-3 mb-2">
                                    <li class="mb-2">Visit the <a href="{{ route('appointments.create') }}" class="text-primary fw-semibold">Appointment Booking Page</a>.</li>
                                    <li class="mb-2">Select your clinical department (e.g. Cardiology, Pediatrics) and preferred specialist physician.</li>
                                    <li class="mb-2">Pick an available date and preferred consultation time slot.</li>
                                    <li class="mb-2">Enter your full legal name, Kenyan mobile number (e.g., <code>+254 712 345 678</code> or <code>0712 345 678</code>), and email address.</li>
                                    <li class="mb-0">Submit your request. You will receive an on-screen booking reference code, followed by reception confirmation.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow 2: Portal Activation -->
                    <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden">
                        <h2 class="accordion-header" id="headingActivation">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseActivation" aria-expanded="false" aria-controls="collapseActivation">
                                <i class="bi bi-envelope-check text-success me-2"></i>Activating Your Patient Portal Account
                            </button>
                        </h2>
                        <div id="collapseActivation" class="accordion-collapse collapse" aria-labelledby="headingActivation" data-bs-parent="#patientAccordion">
                            <div class="accordion-body text-secondary small leading-relaxed">
                                <p>To protect patient health confidentiality, portal accounts are provisioned via verified hospital registration:</p>
                                <ol class="ps-3 mb-2">
                                    <li class="mb-2">During registration, request portal access at the Reception Registry Desk.</li>
                                    <li class="mb-2">A single-use activation link will be automatically dispatched to your verified email address.</li>
                                    <li class="mb-2">Click the link (valid for 48 hours) and create a secure password of at least 10 characters containing letters and numbers.</li>
                                    <li class="mb-0">Once saved, you can log in immediately to view your clinic schedule and prescriptions.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow 3: Doctor Reviews -->
                    <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden">
                        <h2 class="accordion-header" id="headingReviews">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReviews" aria-expanded="false" aria-controls="collapseReviews">
                                <i class="bi bi-star-fill text-warning me-2"></i>Submitting a Verified Physician Review
                            </button>
                        </h2>
                        <div id="collapseReviews" class="accordion-collapse collapse" aria-labelledby="headingReviews" data-bs-parent="#patientAccordion">
                            <div class="accordion-body text-secondary small leading-relaxed">
                                <p>We maintain an authentic, verified review system to ensure genuine patient feedback:</p>
                                <ul class="ps-3 mb-0">
                                    <li class="mb-2">Reviews are only permitted from patients who have completed an in-person clinical visit with that specific doctor.</li>
                                    <li class="mb-2">Verification requires entering your MRN and contact phone number.</li>
                                    <li class="mb-2">You can choose whether your name appears publicly or anonymously.</li>
                                    <li class="mb-0">Reviews undergo administrative moderation to ensure clinical professionalism before publication.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- 4. Staff Clinical Workflows -->
            <div id="clinical-workflows" class="mb-5 pt-3">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">4. Clinical Operations &amp; Staff Procedures</h3>

                <div class="row g-4">
                    <!-- Reception Flow -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-secondary p-2"><i class="bi bi-people fs-6"></i></span>
                                <h5 class="fw-bold mb-0">Reception &amp; Intake</h5>
                            </div>
                            <ol class="small text-secondary ps-3 mb-0">
                                <li class="mb-2"><strong>Appointment Desk:</strong> Review online requests under <em>Appointments</em>. Confirm, reschedule, or cancel with documented reasons.</li>
                                <li class="mb-2"><strong>Check-In Action:</strong> Convert confirmed appointments into active clinic visits with a single click.</li>
                                <li class="mb-2"><strong>Patient Master Index:</strong> Register new patients, issue unique MRN codes, and dispatch portal activation links.</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Nurse Triage Flow -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-success p-2"><i class="bi bi-activity fs-6"></i></span>
                                <h5 class="fw-bold mb-0">Initial Assessment &amp; Triage (Nurses)</h5>
                            </div>
                            <ol class="small text-secondary ps-3 mb-0">
                                <li class="mb-2">Open active queue under <em>Initial Assessment &amp; Vital Signs</em>.</li>
                                <li class="mb-2">Record temperature, systolic/diastolic blood pressure, heart rate, respiratory rate, and oxygen saturation (SpO2).</li>
                                <li class="mb-2">Enter patient height and weight (BMI automatically calculated).</li>
                                <li class="mb-0">Document known medical allergies and assign triage priority (Urgent vs Standard).</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Doctor Consultation Flow -->
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-teal p-2" style="background-color: #0d9488; color: #fff;"><i class="bi bi-clipboard2-pulse fs-6"></i></span>
                                <h5 class="fw-bold mb-0">Doctor Consultation &amp; E-Prescribing</h5>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <h6 class="fw-bold text-dark small mb-1">1. Review Clinical Vitals</h6>
                                        <p class="text-muted small mb-0">Inspect triage vital signs, alerts for allergy warnings, and prior medical history in the patient file.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <h6 class="fw-bold text-dark small mb-1">2. Document Consultation</h6>
                                        <p class="text-muted small mb-0">Record chief complaint, clinical examination findings, and primary/secondary diagnostic codes.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <h6 class="fw-bold text-dark small mb-1">3. Prescribe Medications</h6>
                                        <p class="text-muted small mb-0">Select formulary medicines with auto-dosage, frequency (e.g. TDS, BD), duration, and route. Finalize to send directly to pharmacy.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- 5. Pharmacy & Inventory Integrity -->
            <div id="pharmacy-inventory" class="mb-5 pt-3">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">5. Pharmacy &amp; FEFO Inventory Management</h3>
                <p class="text-secondary leading-relaxed">
                    MediCare HMS operates an audited, First-Expiry-First-Out (FEFO) inventory and dispensing workflow to prevent stock spoilage and ensure pharmaceutical accuracy.
                </p>

                <div class="card border-0 bg-light rounded-4 p-4 mb-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam text-warning me-2"></i>Inventory Operations Supported</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <ul class="text-secondary small ps-3 mb-0">
                                <li class="mb-2"><strong>FEFO Prescription Dispensing:</strong> Automatically selects the earliest expiring unexpired batch. Decrements stock in real-time.</li>
                                <li class="mb-2"><strong>Over-the-Counter (OTC) Sales:</strong> Direct outpatient pharmacy sales with recorded customer details and instant stock deduction.</li>
                                <li class="mb-0"><strong>Low Stock Filtering:</strong> Instant URL filter (<code>/admin/inventory?low_stock=1</code>) displaying items at or below reorder threshold.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="text-secondary small ps-3 mb-0">
                                <li class="mb-2"><strong>Ward Internal Use:</strong> Record medication requisitioned by in-hospital units (ICU, Maternity, Ward).</li>
                                <li class="mb-2"><strong>Damage &amp; Wastage:</strong> Mandatory recording of broken, contaminated, or expired vials with documented reason.</li>
                                <li class="mb-0"><strong>Supplier Returns:</strong> Deduct stock returned to distributor with batch reference.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- 6. Security, Privacy & Compliance -->
            <div id="security-privacy" class="mb-5 pt-3">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">6. Privacy, Security &amp; Safe Account Practices</h3>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 bg-white h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-key text-primary me-2"></i>Token Security</h6>
                            <p class="small text-muted mb-0">Activation links and password reset tokens are stored as irreversible SHA-256 hashes. They expire automatically and can only be used once.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 bg-white h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-shaded text-success me-2"></i>Guard Separation</h6>
                            <p class="small text-muted mb-0">Patient portal authentication is partitioned under a dedicated guard. Patients cannot access staff portals, and staff sessions cannot authenticate as patients.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 bg-white h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-clock-history text-info me-2"></i>Audit Logging</h6>
                            <p class="small text-muted mb-0">All security-critical actions (logins, dispensing, status changes) are recorded in an immutable audit ledger with user, IP, and timestamp.</p>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- 7. Common Problems & FAQs -->
            <div id="troubleshooting" class="mb-5 pt-3">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">7. Frequently Asked Questions &amp; Troubleshooting</h3>

                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden">
                        <h2 class="accordion-header" id="faq1">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq1" aria-expanded="false" aria-controls="collapseFaq1">
                                What should I do if my portal activation link says "This link cannot be used"?
                            </button>
                        </h2>
                        <div id="collapseFaq1" class="accordion-collapse collapse" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small">
                                For security, each activation link is single-use and expires after 48 hours. If expired, navigate to the <a href="{{ route('portal.forgot') }}" class="text-primary fw-semibold">Forgot Password</a> page to request a fresh 60-minute reset link, or ask the hospital registry desk to re-dispatch your link.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden">
                        <h2 class="accordion-header" id="faq2">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq2" aria-expanded="false" aria-controls="collapseFaq2">
                                How can I change my appointment date or time?
                            </button>
                        </h2>
                        <div id="collapseFaq2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small">
                                To reschedule an appointment, contact our front desk at <strong>{{ config('hospital.phone', '+254 20 790 0000') }}</strong> or submit a new appointment request through the portal. Reception staff can reschedule your existing booking directly from their appointment board.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden">
                        <h2 class="accordion-header" id="faq3">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq3" aria-expanded="false" aria-controls="collapseFaq3">
                                Why is my medicine not available for online refill?
                            </button>
                        </h2>
                        <div id="collapseFaq3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small">
                                In compliance with Pharmacy and Poisons Board (PPB) regulations, prescription medications require active clinician authorization. Refills cannot be dispensed without clinical consultation if your original prescription course has concluded.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden">
                        <h2 class="accordion-header" id="faq4">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq4" aria-expanded="false" aria-controls="collapseFaq4">
                                How do I report a technical issue or request support?
                            </button>
                        </h2>
                        <div id="collapseFaq4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small">
                                Reach our hospital IT Helpdesk at <strong>{{ config('hospital.email', 'support@medicare.co.ke') }}</strong> or visit the Administration Office during OPD operational hours ({{ config('hospital.opd_hours', 'Mon - Sat: 8:00 AM - 6:00 PM') }}).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Box -->
            <div class="card border-0 rounded-4 p-4 shadow-sm bg-light border text-center">
                <h5 class="fw-bold mb-2 text-dark">Need Further Assistance?</h5>
                <p class="text-muted small mb-3">Our Patient Care Coordination and Clinical Support teams are here to assist you.</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('contact') }}" class="btn btn-primary px-4 fw-bold"><i class="bi bi-envelope me-2"></i>Contact Hospital</a>
                    <a href="tel:{{ config('hospital.phone', '+254 20 790 0000') }}" class="btn btn-outline-secondary px-4 fw-bold"><i class="bi bi-telephone me-2"></i>Call {{ config('hospital.phone', '+254 20 790 0000') }}</a>
                </div>
            </div>

        </div>
    </section>

@endsection
