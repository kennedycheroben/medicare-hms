@extends('layouts.public')

@section('title', 'Hospital Services & Clinical Facilities')
@section('meta_description', 'Comprehensive hospital services at MediCare Hospital including 24/7 trauma triage, diagnostic imaging, surgical suites, intensive care, and specialized clinics.')

@section('content')

    <x-page-header
        title="Hospital Services & Clinical Facilities"
        subtitle="Advanced Medical Infrastructure, Continuous Emergency Readiness, and Comprehensive Patient Care"
        :breadcrumbs="[['label' => 'Services']]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark shadow-sm">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Services Notice:</strong> Clinical capabilities and facilities described on this page illustrate hospital operational workflows in MediCare HMS. In an actual medical emergency, please call national emergency dispatch (<strong>999 / 112</strong> in Kenya) or visit the nearest physical hospital.
            </div>
        </div>
    @endif

    <!-- Main Services Grid -->
    <section class="py-5">
        <div class="container" data-aos="fade-up">

            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Care Spectrum</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Complete Clinical Capabilities</h2>
                <p class="text-muted">Delivering seamless patient journeys from preventative screening through tertiary surgical interventions.</p>
            </div>

            <div class="row g-4">

                <!-- Service 1 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-danger-subtle text-danger fs-3 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                <i class="bi bi-shield-plus"></i>
                            </div>
                            <div>
                                <h4 class="h5 fw-bold mb-0">24/7 Trauma & Emergency</h4>
                                <small class="text-danger fw-semibold">{{ config('hospital.is_demo') ? 'Simulated Emergency Service' : 'Level 1 Facility' }}</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            {{ config('hospital.is_demo') ? 'Simulated acute triage workflows, demonstration resuscitation bays, and emergency admission pathways within the HMS.' : 'Full-time emergency physicians, dedicated resuscitation bays, and rapid vascular access for acute myocardial infarction and stroke protocols.' }}
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Dedicated ambulance dispatch</li>
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Immediate blood bank transfusion access</li>
                            <li><i class="bi bi-check2 text-primary me-2"></i>Direct-to-theatre trauma pathways</li>
                        </ul>
                    </div>
                </div>

                <!-- Service 2 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-primary-subtle text-primary fs-3 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                <i class="bi bi-cpu"></i>
                            </div>
                            <div>
                                <h4 class="h5 fw-bold mb-0">Diagnostic Imaging & Radiology</h4>
                                <small class="text-primary fw-semibold">High-Precision Scans</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            State-of-the-art non-invasive medical imaging allowing radiologists and consulting physicians to detect subtle anomalies with pinpoint accuracy.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>3.0 Tesla Silent MRI Scanner</li>
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>128-Slice Low-Dose Dual Energy CT</li>
                            <li><i class="bi bi-check2 text-primary me-2"></i>Digital Fluoroscopy & 4D Ultrasound</li>
                        </ul>
                    </div>
                </div>

                <!-- Service 3 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-success-subtle text-success fs-3 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                <i class="bi bi-activity"></i>
                            </div>
                            <div>
                                <h4 class="h5 fw-bold mb-0">Surgical Suites & Day Surgery</h4>
                                <small class="text-success fw-semibold">Minimally Invasive</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            Ultra-sterile laminar-airflow operating theatres equipped with advanced laparoscopic video towers, intraoperative monitoring, and anesthesia safety stations.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Laparoscopic abdominal surgery</li>
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Orthopedic joint arthroplasty</li>
                            <li><i class="bi bi-check2 text-primary me-2"></i>Rapid-recovery day-surgery unit</li>
                        </ul>
                    </div>
                </div>

                <!-- Service 4 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-warning-subtle text-warning fs-3 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                <i class="bi bi-heart-pulse"></i>
                            </div>
                            <div>
                                <h4 class="h5 fw-bold mb-0">Intensive Care Units (ICU)</h4>
                                <small class="text-warning fw-semibold">Continuous Telemetry</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            High-acuity medical and surgical intensive care beds offering 1:1 dedicated nursing, invasive hemodynamics, and mechanical ventilation support.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Adult Intensive Care Unit (ICU)</li>
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Pediatric Intensive Care Unit (PICU)</li>
                            <li><i class="bi bi-check2 text-primary me-2"></i>Level III Neonatal ICU (NICU)</li>
                        </ul>
                    </div>
                </div>

                <!-- Service 5 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-info-subtle text-info fs-3 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                <i class="bi bi-capsule"></i>
                            </div>
                            <div>
                                <h4 class="h5 fw-bold mb-0">Pathology & Clinical Lab</h4>
                                <small class="text-info fw-semibold">{{ config('hospital.is_demo') ? 'Diagnostic Laboratory' : 'ISO 15189 Standards' }}</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            Comprehensive diagnostic laboratory processing clinical chemistry, hematology, microbiology, histopathology, and molecular viral load diagnostics.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Fast-turnaround routine blood panels</li>
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Biopsy & oncological cytology</li>
                            <li><i class="bi bi-check2 text-primary me-2"></i>Immunoassay and infectious disease screening</li>
                        </ul>
                    </div>
                </div>

                <!-- Service 6 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-secondary-subtle text-dark fs-3 me-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                <i class="bi bi-bandaid"></i>
                            </div>
                            <div>
                                <h4 class="h5 fw-bold mb-0">Physical Therapy & Rehab</h4>
                                <small class="text-secondary fw-semibold">Functional Recovery</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            Rehabilitation center assisting patients recovering from strokes, joint replacements, sports injuries, and neuro-motor impairment.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Post-surgical mobility restoration</li>
                            <li class="mb-1"><i class="bi bi-check2 text-primary me-2"></i>Cardiopulmonary exercise training</li>
                            <li><i class="bi bi-check2 text-primary me-2"></i>Occupational and ergonomics therapy</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Facilities Gallery Section -->
    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Hospital Infrastructure</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Tour Our Medical Facilities</h2>
                <p class="text-muted">A look inside our purpose-built clinical suites designed for hygiene, security, and healing.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                        <img src="{{ asset('assets/img/health/emergency-3.webp') }}" alt="Emergency Care Bay" class="w-100" style="height: 180px; object-fit: cover;">
                        <div class="p-3 bg-white">
                            <h6 class="fw-bold mb-1">Emergency Triage Bay</h6>
                            <small class="text-muted">Equipped for rapid diagnostic evaluation</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                        <img src="{{ asset('assets/img/health/facilities-6.webp') }}" alt="Radiology Suite" class="w-100" style="height: 180px; object-fit: cover;">
                        <div class="p-3 bg-white">
                            <h6 class="fw-bold mb-1">Diagnostic Imaging Suite</h6>
                            <small class="text-muted">High-Tesla MRI & low-dose CT systems</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                        <img src="{{ asset('assets/img/health/surgery-2.webp') }}" alt="Operating Theatres" class="w-100" style="height: 180px; object-fit: cover;">
                        <div class="p-3 bg-white">
                            <h6 class="fw-bold mb-1">Surgical Operating Suites</h6>
                            <small class="text-muted">HEPA laminar airflow operating rooms</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                        <img src="{{ asset('assets/img/health/laboratory-3.webp') }}" alt="Pathology Laboratory" class="w-100" style="height: 180px; object-fit: cover;">
                        <div class="p-3 bg-white">
                            <h6 class="fw-bold mb-1">Automated Pathology Lab</h6>
                            <small class="text-muted">Continuous 24/7 diagnostic analysis</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Insurance & Billing Information -->
    <section class="py-5 bg-white border-top">
        <div class="container" data-aos="fade-up">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="text-primary text-uppercase fw-bold small">Seamless Health Coverage</span>
                    <h3 class="display-6 fw-bold mt-2 mb-3" style="color: var(--heading-color);">Insurance & Direct Cashless Billing</h3>
                    <p class="text-muted leading-relaxed mb-4">
                        We collaborate with premier local and international medical insurance companies, health maintenance organizations (HMOs), and corporate medical schemes to deliver hassle-free cashless healthcare.
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Direct Pre-Authorization</h6>
                                <p class="small text-muted mb-0">Our dedicated liaison officers process insurance approvals directly at admission.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold mb-1"><i class="bi bi-wallet2 text-success me-2"></i>Flexible Payment Options</h6>
                                <p class="small text-muted mb-0">Accepting major credit cards, debit cards, mobile money, and bank transfers.</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('patient-info') }}#insurance" class="btn btn-outline-primary">
                        Read Detailed Insurance Guide <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4 rounded-4 bg-light text-center">
                        <i class="bi bi-file-earmark-medical fs-1 text-primary mb-3"></i>
                        <h4 class="fw-bold mb-2" style="color: var(--heading-color);">Ready to Schedule Care?</h4>
                        <p class="text-muted small mb-4">Choose your preferred department and book an in-person or follow-up consultation with our specialists.</p>
                        <a href="{{ route('appointments.create') }}" class="btn btn-primary btn-lg w-100 fw-bold mb-2">Book Appointment</a>
                        <a href="{{ route('contact') }}" class="btn btn-outline-secondary w-100">Contact Hospital Reception</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
