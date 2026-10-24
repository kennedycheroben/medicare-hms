@extends('layouts.public')

@section('title', 'Privacy Policy & Data Protection')
@section('meta_description', 'Privacy Policy and patient health data protection practices at ' . config('hospital.name', 'MediCare Hospital') . ' under the Kenya Data Protection Act, 2019.')

@section('content')

    <x-page-header
        title="Privacy Policy & Data Protection"
        subtitle="Patient Rights, Health Record Confidentiality & Data Protection Standards"
        :breadcrumbs="[['label' => 'Privacy Policy']]"
    />

    <section class="py-5 bg-white">
        <div class="container" style="max-width: 900px;">

            <!-- Legal Notice / Confirmation Banner -->
            <div class="alert alert-info border-0 rounded-4 p-4 mb-5 shadow-sm">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-shield-check text-primary fs-3"></i>
                    <div>
                        <h5 class="fw-bold mb-1">Institutional Privacy Commitment</h5>
                        <p class="mb-0 text-muted small">
                            This document describes the data handling principles implemented within {{ config('hospital.name', 'MediCare Hospital') }} digital platforms and clinical workflows in compliance with the <strong>Kenya Data Protection Act, 2019 (KDPA)</strong> and Ministry of Health clinical confidentiality guidelines.
                            <em>Note: Institutional registration identifiers and formal Data Protection Officer designations are maintained by hospital administration.</em>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 1: Overview -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">1. Scope &amp; Data Controller</h3>
                <p class="text-secondary leading-relaxed">
                    {{ config('hospital.name', 'MediCare Hospital') }} ("we", "our", or "the Hospital"), situated in {{ config('hospital.address', 'Upper Hill, Nairobi, Kenya') }}, operates as the <strong>Data Controller</strong> for personal health information collected during physical hospital attendance, public appointment booking, and through our secure Patient Portal.
                </p>
            </div>

            <!-- Section 2: Personal & Health Data We Collect -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">2. Personal &amp; Health Data Collected</h3>
                <p class="text-secondary leading-relaxed">
                    To deliver safe, reliable healthcare services, we collect and process the following categories of data:
                </p>
                <div class="card border-0 bg-light rounded-4 p-4 mb-3">
                    <ul class="mb-0 text-secondary ps-3">
                        <li class="mb-2"><strong>Patient Demographics:</strong> Full legal name, National ID / Passport number, Date of birth, Biological sex, Residential address, and verified Kenyan contact phone number.</li>
                        <li class="mb-2"><strong>Emergency Contacts:</strong> Kin or guardian contact details to ensure emergency notifications when medically required.</li>
                        <li class="mb-2"><strong>Clinical Records:</strong> Medical Record Number (MRN), triage vital signs (blood pressure, temperature, heart rate, oxygen saturation, BMI), attending physician notes, diagnoses, and medical allergies.</li>
                        <li class="mb-2"><strong>Pharmacy &amp; Prescriptions:</strong> Prescribed medications, dosage instructions, dispensing records, and batch verification numbers.</li>
                        <li class="mb-0"><strong>Technical &amp; Audit Logs:</strong> Access timestamps, role-based user actions, IP addresses for security telemetry, and patient portal authentication events.</li>
                    </ul>
                </div>
            </div>

            <!-- Section 3: Lawful Basis for Processing -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">3. Lawful Basis for Processing</h3>
                <p class="text-secondary leading-relaxed">
                    Under Section 30 and Section 44 of the Kenya Data Protection Act, 2019, health data constitutes sensitive personal data. We process your data exclusively under:
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <h6 class="fw-bold text-dark"><i class="bi bi-heart-pulse text-danger me-2"></i>Medical Diagnosis &amp; Treatment</h6>
                            <p class="small text-muted mb-0">Processing is necessary for preventive medicine, medical diagnosis, and the provision of clinical health services by registered medical practitioners.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <h6 class="fw-bold text-dark"><i class="bi bi-shield-lock text-primary me-2"></i>Vital Interests &amp; Emergency</h6>
                            <p class="small text-muted mb-0">Processing essential to safeguard the patient's life or physical integrity in acute, trauma, or emergency medical scenarios.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-check text-success me-2"></i>Legal Compliance</h6>
                            <p class="small text-muted mb-0">Adherence to public health notification obligations, regulatory inspection standards, and pharmaceutical dispensing reporting.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <h6 class="fw-bold text-dark"><i class="bi bi-hand-thumbs-up text-info me-2"></i>Explicit Consent</h6>
                            <p class="small text-muted mb-0">Provided when you book an outpatient appointment or request self-service digital access through the Patient Portal.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Data Security & System Isolation -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">4. Security Safeguards &amp; Access Controls</h3>
                <p class="text-secondary leading-relaxed">
                    We enforce strict role-based access controls (RBAC) across our hospital system:
                </p>
                <ul class="text-secondary ps-3">
                    <li class="mb-2"><strong>Role Segregation:</strong> Receptionists can access intake demographics but not doctor clinical notes; pharmacists see only active prescriptions; clinicians see assigned patient records.</li>
                    <li class="mb-2"><strong>Session Isolation:</strong> Patient Portal authentication runs on an isolated session guard completely separated from clinical staff administrative accounts.</li>
                    <li class="mb-2"><strong>Password Security &amp; Token Hashing:</strong> One-time portal activation links use cryptographically random tokens stored only as SHA-256 hashes, with single-use revocation and mandatory password rotation.</li>
                    <li class="mb-2"><strong>Audit Trails:</strong> High-risk operations (logins, prescription finalization, medication dispensing, stock movements) produce immutable audit logs with actor tracking.</li>
                </ul>
            </div>

            <!-- Section 5: Data Subject Rights (KDPA 2019) -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">5. Your Rights as a Data Subject</h3>
                <p class="text-secondary leading-relaxed">
                    Under the Kenya Data Protection Act, 2019, patients hold distinct rights regarding their personal data:
                </p>
                <div class="card border-0 bg-light rounded-4 p-4">
                    <ul class="mb-0 text-secondary ps-3">
                        <li class="mb-2"><strong>Right to Access:</strong> You can view your consultation summaries, active prescriptions, and appointments via the Patient Portal or request copies from the Hospital Records Office.</li>
                        <li class="mb-2"><strong>Right to Rectification:</strong> You may request the correction of inaccurate demographic, contact, or next-of-kin information.</li>
                        <li class="mb-2"><strong>Right to Object &amp; Restriction:</strong> You may object to non-clinical communications and request restricted processing where permitted by clinical regulations.</li>
                        <li class="mb-0"><strong>Regulatory Recourse:</strong> If you believe your data has been handled inconsistently with Kenyan law, you have the right to lodge a complaint with the <em>Office of the Data Protection Commissioner (ODPC) of Kenya</em> (<a href="https://www.odpc.go.ke" target="_blank" rel="noopener noreferrer">odpc.go.ke</a>).</li>
                    </ul>
                </div>
            </div>

            <!-- Section 6: Data Retention & Disclosure -->
            <div class="mb-5">
                <h3 class="fw-bold mb-3" style="color: var(--heading-color);">6. Retention &amp; Disclosure</h3>
                <p class="text-secondary leading-relaxed">
                    Medical records are retained in compliance with Kenyan health guidelines governing clinical archiving. We never sell, rent, or trade patient personal data. Information is only disclosed to accredited diagnostic laboratories, attending referral specialists, or authorized public health authorities as legally mandated.
                </p>
            </div>

            <!-- Section 7: Contact Us -->
            <div class="card border-0 rounded-4 p-4 shadow-sm bg-primary text-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Questions Regarding Patient Privacy?</h5>
                        <p class="mb-0 small text-white-50">Reach our Hospital Patient Registry &amp; Information Governance Desk.</p>
                    </div>
                    <div class="text-md-end">
                        <span class="d-block fw-bold"><i class="bi bi-telephone-fill me-2"></i>{{ config('hospital.phone', '+254 20 790 0000') }}</span>
                        <span class="d-block small text-white-50"><i class="bi bi-envelope-fill me-2"></i>{{ config('hospital.email', 'privacy@medicare.co.ke') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

@endsection
