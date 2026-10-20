@extends('layouts.public')

@section('title', 'About Us & Medical Excellence')
@section('meta_description', 'Learn about MediCare Hospital history, mission, international accreditations, executive medical leadership, and commitment to compassionate patient care.')

@section('content')

    <x-page-header
        title="About MediCare Hospital"
        subtitle="Dedicated to Clinical Excellence, Advanced Medical Technology, and Compassionate Patient-Centered Care"
        :breadcrumbs="[['label' => 'About Us']]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Institutional Profile Notice:</strong> The institutional history, executive governance roster, and capacity figures below are sample demonstration placeholders provided for software evaluation. Deploying institutions configure their actual verified credentials, leadership, and facilities.
            </div>
        </div>
    @endif

    <!-- Mission & Overview Section -->
    <section class="py-5">
        <div class="container" data-aos="fade-up">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <span class="text-primary text-uppercase fw-bold small">Our Clinical Mission</span>
                    <h2 class="display-6 fw-bold mt-2 mb-4" style="color: var(--heading-color);">Delivering Healthcare with Empathy and Rigor</h2>
                    <p class="text-muted leading-relaxed">
                        {{ config('hospital.name', 'MediCare Hospital') }} operates as a patient-centered healthcare institution committed to delivering accessible, state-of-the-art medical care while preserving the dignity and comfort of every patient.
                    </p>
                    <p class="text-muted leading-relaxed">
                        Our clinical governance model brings together multidisciplinary clinicians, dedicated nursing staff, and modern diagnostics to support patient wellness across specialized inpatient and outpatient services.
                    </p>

                    <div class="row g-3 mt-2">
                        <div class="col-6">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="h2 fw-bold text-primary mb-1">{{ $doctorCount }}</h3>
                                <p class="small text-muted mb-0">Active Medical Specialists</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="h2 fw-bold text-primary mb-1">{{ $departmentCount }}</h3>
                                <p class="small text-muted mb-0">Clinical Departments</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="h2 fw-bold text-primary mb-1">Inpatient</h3>
                                <p class="small text-muted mb-0">Wards & Intensive Care</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="h2 fw-bold text-primary mb-1">24/7</h3>
                                <p class="small text-muted mb-0">Emergency Care Coverage</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="position-relative">
                        <img src="{{ asset('assets/img/health/facilities-6.webp') }}" alt="Hospital Facilities" class="img-fluid rounded shadow-sm">
                        <div class="card p-3 border-0 shadow position-absolute bottom-0 start-0 m-3 bg-white" style="max-width: 340px;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-check text-success fs-1 me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-0">Institutional Standards</h6>
                                    <span class="small text-muted">{{ config('hospital.accreditation_notice') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values Section -->
    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Our Principles</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Core Values That Guide Every Decision</h2>
                <p class="text-muted">The clinical pillars supporting our patient experience and hospital ethics.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-heart-pulse-fill"></i></div>
                        <h5 class="fw-bold mb-2">Patient First</h5>
                        <p class="text-muted small">Every diagnosis, treatment pathway, and interaction is designed around the physical and emotional wellbeing of our patients.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-award-fill"></i></div>
                        <h5 class="fw-bold mb-2">Clinical Rigor</h5>
                        <p class="text-muted small">We uphold the highest medical standards, continuous peer review, and evidence-based protocols to maximize positive outcomes.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-shield-lock-fill"></i></div>
                        <h5 class="fw-bold mb-2">Integrity & Confidentiality</h5>
                        <p class="text-muted small">Complete transparency with patients, strict medical ethics, and unwavering data privacy protections under health regulations.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-lightbulb-fill"></i></div>
                        <h5 class="fw-bold mb-2">Continuous Innovation</h5>
                        <p class="text-muted small">Investing in next-generation diagnostics, minimally invasive surgery, and ongoing clinical education for all staff.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hospital Leadership Section -->
    <section class="py-5">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Executive Governance</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Medical Leadership & Administration</h2>
                <p class="text-muted">Experienced clinicians overseeing quality assurance, research, and patient safety.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm text-center p-4 h-100">
                        <img src="{{ asset('assets/img/health/staff-1.webp') }}" alt="Chief Executive Officer" class="rounded-circle mx-auto mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <h5 class="fw-bold mb-1">Dr. Sarah Jenkins, MD, MBA</h5>
                        <span class="text-primary small mb-3 d-block">Chief Executive Officer</span>
                        <p class="text-muted small">Over 22 years of hospital administration and healthcare leadership experience, spearheading regional clinical expansion.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm text-center p-4 h-100">
                        <img src="{{ asset('assets/img/health/staff-3.webp') }}" alt="Chief Medical Officer" class="rounded-circle mx-auto mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <h5 class="fw-bold mb-1">Dr. Marcus Vance, MD, FACC</h5>
                        <span class="text-primary small mb-3 d-block">Chief Medical Officer</span>
                        <p class="text-muted small">Board-certified cardiologist leading clinical governance, surgical protocol quality, and physician credentialing.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm text-center p-4 h-100">
                        <img src="{{ asset('assets/img/health/staff-7.webp') }}" alt="Director of Nursing" class="rounded-circle mx-auto mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <h5 class="fw-bold mb-1">Elena Rostova, MSN, RN</h5>
                        <span class="text-primary small mb-3 d-block">Director of Nursing Services</span>
                        <p class="text-muted small">Champion of compassionate nursing care, patient advocacy, and specialized emergency resuscitation training.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-light text-center border-top">
        <div class="container py-3">
            <h3 class="fw-bold mb-3" style="color: var(--heading-color);">Experience World-Class Healthcare Today</h3>
            <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">
                Our clinicians are ready to assist with consultations, second opinions, and preventative health screenings.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="{{ route('appointments.create') }}" class="btn btn-primary px-4 py-2">Book an Appointment</a>
                <a href="{{ route('contact') }}" class="btn btn-outline-secondary px-4 py-2">Contact Our Coordination Team</a>
            </div>
        </div>
    </section>

@endsection
