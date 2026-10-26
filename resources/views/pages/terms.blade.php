@extends('layouts.public')

@section('title', 'Terms of Service & Clinical Care Guidelines')
@section('meta_description', 'Terms and conditions governing outpatient appointments, Patient Portal usage, and clinical care policies at ' . config('hospital.name', 'MediCare Hospital') . '.')

@section('content')

    <x-page-header
        title="Terms of Service"
        subtitle="Patient Agreement, Clinical Policies &amp; Digital Portal Guidelines"
        :breadcrumbs="[['label' => 'Terms of Service']]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4" style="max-width: 900px;">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark shadow-sm">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Terms Notice:</strong> These terms of service reflect operational guidelines modeled in the MediCare HMS software. Facility-specific contractual terms and liability provisions are established by deploying organizations.
            </div>
        </div>
    @endif

    <section class="py-5 bg-white">
        <div class="container" style="max-width: 900px;">

            <!-- Important Emergency Notice -->
            <div class="alert alert-danger border-0 rounded-4 p-4 mb-5 shadow-sm">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i>
                    <div>
                        <h5 class="fw-bold mb-1 text-danger">Emergency Care Notice</h5>
                        @if(config('hospital.is_demo'))
                            <p class="mb-0 text-dark small">
                                This website, public booking interface, and Patient Portal are an <strong>interactive software demonstration</strong> and are <strong>NOT for actual medical emergencies</strong>. If you or someone in your care is experiencing severe chest pain, heavy bleeding, difficulty breathing, acute trauma, or loss of consciousness, immediately call national emergency dispatch (<strong>999 / 112</strong> in Kenya) or proceed to the nearest physical hospital emergency department. This platform does not operate an active medical dispatch.
                            </p>
                        @else
                            <p class="mb-0 text-dark small">
                                This website, public booking interface, and the Patient Portal are <strong>NOT for immediate life-threatening medical emergencies</strong>. If you or someone in your care is experiencing severe chest pain, heavy bleeding, difficulty breathing, acute trauma, or loss of consciousness, immediately call our 24/7 Emergency Line at <strong>{{ config('hospital.emergency_phone', '+254 700 911 911') }}</strong> or report directly to our Emergency Department.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Section 1: Introduction -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">1. Agreement to Terms</h3>
                <p class="text-secondary leading-relaxed">
                    By booking an appointment, requesting portal activation, or accessing the services of {{ config('hospital.name', 'MediCare Hospital') }} ("the Hospital"), you agree to these Terms of Service, along with our <a href="{{ route('privacy') }}" class="text-primary fw-semibold">Privacy Policy</a>.
                    <em>Please note that these terms reflect operational healthcare workflows; formal contractual and insurance agreements are confirmed at hospital registration.</em>
                </p>
            </div>

            <!-- Section 2: Outpatient Appointments & Scheduling -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">2. Appointment Booking &amp; Scheduling</h3>
                <div class="card border-0 bg-light rounded-4 p-4">
                    <ul class="mb-0 text-secondary ps-3">
                        <li class="mb-2"><strong>Appointment Requests:</strong> Online submissions through our website are appointment requests subject to department scheduling, physician availability, and clinical triage.</li>
                        <li class="mb-2"><strong>Confirmation:</strong> An appointment is officially scheduled once reviewed and confirmed by our reception desk. You will receive an SMS or phone confirmation.</li>
                        <li class="mb-2"><strong>Arrival Times:</strong> Outpatients should arrive at the front desk 15 minutes prior to their scheduled consultation for vital signs assessment and intake check-in.</li>
                        <li class="mb-0"><strong>Cancellations &amp; Rescheduling:</strong> If you cannot attend your scheduled appointment, please notify us at least 24 hours in advance through our front desk phone ({{ config('hospital.phone', '+254 20 790 0000') }}) so the slot can be released to other patients.</li>
                    </ul>
                </div>
            </div>

            <!-- Section 3: Patient Portal Usage & Account Security -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">3. Patient Portal Access &amp; Responsibilities</h3>
                <p class="text-secondary leading-relaxed">
                    Our authenticated Patient Portal enables registered patients to review their consultation summaries, appointment dates, and doctor-prescribed medication plans.
                </p>
                <div class="card border-0 bg-light rounded-4 p-4">
                    <ul class="mb-0 text-secondary ps-3">
                        <li class="mb-2"><strong>Activation &amp; Identity Verification:</strong> Portal access is provisioned using the patient's verified National ID / Passport and registered email address on file.</li>
                        <li class="mb-2"><strong>Credential Confidentiality:</strong> You are responsible for maintaining the confidentiality of your password. Never share your password or one-time activation links with third parties. MediCare staff will never request your password.</li>
                        <li class="mb-2"><strong>Single-Use Tokens:</strong> Activation and password reset links are cryptographically protected, valid for one-time use, and expire automatically after their designated time window.</li>
                        <li class="mb-0"><strong>Unauthorized Access:</strong> If you suspect unauthorized access to your account, immediately reset your password via the portal or contact our records office.</li>
                    </ul>
                </div>
            </div>

            <!-- Section 4: Billing, Insurance & Payments -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">4. Billing, Insurance &amp; Payment Policies</h3>
                <p class="text-secondary leading-relaxed">
                    Transparent, ethical billing is a core commitment of our clinical operations:
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <h6 class="fw-bold text-dark"><i class="bi bi-credit-card text-primary me-2"></i>Payment Channels</h6>
                            <p class="small text-muted mb-0">We accept M-Pesa paybill transactions, major debit/credit cards, and cash payments at our designated cashier desks.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <h6 class="fw-bold text-dark"><i class="bi bi-shield-check text-success me-2"></i>National Health Schemes</h6>
                            <p class="small text-muted mb-0">We accept verified national health insurance scheme cover (SHA/NHIF) for eligible outpatient and inpatient benefits.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <h6 class="fw-bold text-dark"><i class="bi bi-building text-info me-2"></i>Private Underwriters</h6>
                            <p class="small text-muted mb-0">Pre-authorization is required for corporate and private insurance covers prior to non-emergency procedures.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Medical Information Disclaimer -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">5. Health Information Disclaimer</h3>
                <p class="text-secondary leading-relaxed">
                    All articles, clinical announcements, and educational advisories published on this site are intended for general health awareness and public education only. They do not constitute formal medical diagnosis, individualized treatment plans, or a substitute for direct clinical examination by a licensed physician.
                </p>
            </div>

            <!-- Section 6: Amendments & Inquiries -->
            <div class="mb-4">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">6. Policy Updates &amp; Contact</h3>
                <p class="text-secondary leading-relaxed">
                    The Hospital reserves the right to update these terms to align with clinical standards, regulatory updates from the Ministry of Health, or statutory amendments. For legal or policy inquiries, contact:
                </p>
                <p class="text-secondary mb-0">
                    <strong>Hospital Administration &amp; Legal Desk</strong><br>
                    {{ config('hospital.name', 'MediCare Hospital') }}<br>
                    {{ config('hospital.address', 'Upper Hill, Nairobi, Kenya') }}<br>
                    Email: {{ config('hospital.email', 'info@medicare.co.ke') }} | Phone: {{ config('hospital.phone', '+254 20 790 0000') }}
                </p>
            </div>

        </div>
    </section>

@endsection
