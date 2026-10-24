@extends('layouts.public')

@section('title', 'Patient Portal Entry & Secure Medical Records Gateway')
@section('meta_description', 'Public informational portal gateway for MediCare Hospital patients. Access diagnostic records, appointment summaries, and registration guidance.')

@section('content')

    <x-page-header
        title="Patient Portal Gateway"
        subtitle="Secure Digital Access to Your Diagnostic Results, Clinic Visits, and Health Records"
        :breadcrumbs="[['label' => 'Patient Portal']]"
    />

    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">

            <!-- Portal Feature Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-primary mb-2"><i class="bi bi-file-earmark-medical"></i></div>
                        <h6 class="fw-bold mb-1">Visit History</h6>
                        <small class="text-muted">See your visit history with us.</small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-primary mb-2"><i class="bi bi-calendar-check"></i></div>
                        <h6 class="fw-bold mb-1">Clinic Schedule</h6>
                        <small class="text-muted">See your upcoming appointments.</small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-primary mb-2"><i class="bi bi-prescription2"></i></div>
                        <h6 class="fw-bold mb-1">Prescription History</h6>
                        <small class="text-muted">View the medicines your doctor prescribed.</small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-primary mb-2"><i class="bi bi-shield-lock"></i></div>
                        <h6 class="fw-bold mb-1">Private Access</h6>
                        <small class="text-muted">Only you can see your own records after signing in.</small>
                    </div>
                </div>
            </div>

            <div class="row g-5 justify-content-center">

                <!-- Left Column: Sign-In Gateway Interface -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-primary-subtle text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 65px; height: 65px;">
                                <i class="bi bi-person-lock fs-2"></i>
                            </div>
                            <h4 class="fw-bold mb-1" style="color: var(--heading-color);">Patient Sign In</h4>
                            <p class="text-muted small">Enter your patient credentials to access your account</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success small" role="status">{{ session('status') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>
                        @endif

                        <form method="POST" action="{{ route('portal.login.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="patient_id" class="form-label small fw-bold">Medical Record Number (MRN) or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="patient_id" name="identifier" value="{{ old('identifier') }}" autocomplete="username" placeholder="Your MRN or email address" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="portal_password" class="form-label small fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                                    <input type="password" class="form-control border-start-0" id="portal_password" name="password" autocomplete="current-password" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4 small">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember" value="1">
                                    <label class="form-check-label text-muted" for="remember_me">Remember device</label>
                                </div>
                                <a href="{{ route('portal.forgot') }}" class="text-primary text-decoration-none">Forgot password?</a>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Portal
                            </button>

                            <div class="text-center small text-muted">
                                Need an appointment instead? <a href="{{ route('appointments.create') }}" class="text-primary fw-bold text-decoration-none">Book Online</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Registration & Information Guidance -->
                <div class="col-lg-6">

                    <!-- How to Get Portal Access -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-3" style="color: var(--heading-color);">
                            <i class="bi bi-shield-check text-success me-2"></i>How to Activate Your Portal Account
                        </h5>
                        <p class="text-muted small leading-relaxed mb-3">
                            To ensure total patient confidentiality and verify identity, new patient portal accounts are provisioned during your physical hospital visit or post-admission registration.
                        </p>
                        <div class="d-flex align-items-start mb-3">
                            <span class="badge bg-primary rounded-circle p-2 px-3 me-3 fw-bold">1</span>
                            <div>
                                <h6 class="fw-bold mb-1 fs-6">Visit Hospital Registration</h6>
                                <p class="text-muted small mb-0">Present your national ID or passport to our Patient Registry Desk.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <span class="badge bg-primary rounded-circle p-2 px-3 me-3 fw-bold">2</span>
                            <div>
                                <h6 class="fw-bold mb-1 fs-6">Receive Secure Activation Code</h6>
                                <p class="text-muted small mb-0">A one-time activation link is emailed to the address on your patient record. It works once and expires after 48 hours.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <span class="badge bg-primary rounded-circle p-2 px-3 me-3 fw-bold">3</span>
                            <div>
                                <h6 class="fw-bold mb-1 fs-6">Create Your Secure Password</h6>
                                <p class="text-muted small mb-0">Choose a password of at least 10 characters with letters and numbers to open your account.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Security & Data Protection -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-lock-fill text-primary me-2"></i>Medical Privacy Commitment</h6>
                        <p class="text-muted small mb-3">
                            Your records are only shown to you after you sign in with your own password. The hospital handles personal data under the Kenya Data Protection Act, 2019. Never share your password; staff will never ask for it.
                        </p>
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-headset text-primary fs-4 me-3"></i>
                            <div>
                                <strong>Need Portal Technical Support?</strong>
                                <span class="d-block">Call Help Desk: {{ config('hospital.phone') }} ({{ config('hospital.opd_hours') }})</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </section>

@endsection
