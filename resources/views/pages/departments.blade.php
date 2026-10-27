@extends('layouts.public')

@section('title', 'Clinical Departments & Medical Specialties')
@section('meta_description', 'Discover specialized clinical departments at MediCare Hospital including Cardiology, Neurology, Pediatrics, Orthopedics, Oncology, and Emergency Trauma Care.')

@section('content')

    <x-page-header
        title="Clinical Departments"
        subtitle="Multidisciplinary Medical Specialties Equipped with Advanced Diagnostic and Therapeutic Technologies"
        :breadcrumbs="[['label' => 'Departments']]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Departments Notice:</strong> The clinical department listings and medical specialties shown are demonstration configurations. Active hospital services are customized by the deploying healthcare facility.
            </div>
        </div>
    @endif

    <!-- Spotlight Section: 24/7 Emergency & Acute Care -->
    <section class="py-5 bg-white border-bottom">
        <div class="container" data-aos="fade-up">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #17325b 0%, #0d1e38 100%); color: #fff;">
                <div class="row g-0 align-items-center">
                    <div class="col-lg-7 p-4 p-lg-5">
                        <span class="badge bg-danger text-uppercase fw-bold mb-3 px-3 py-2">
                            <i class="bi bi-broadcast me-1"></i>24/7 Acute Readiness
                        </span>
                        <h2 class="display-6 fw-bold mb-3 text-white">Emergency & Critical Care Center</h2>
                        <p class="text-light opacity-75 mb-4 leading-relaxed">
                            Our emergency and acute triage wing is operational 24 hours a day. Staffed by certified acute care clinicians and supported by on-call surgical specialists, our team prioritizes rapid clinical assessment for acute emergencies.
                        </p>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center text-light">
                                    <i class="bi bi-stopwatch text-info fs-3 me-2"></i>
                                    <div>
                                        <span class="fw-bold d-block">Rapid Assessment</span>
                                        <small class="opacity-75">Triage Protocol</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center text-light">
                                    <i class="bi bi-shield-check text-info fs-3 me-2"></i>
                                    <div>
                                        <span class="fw-bold d-block">Clinical Readiness</span>
                                        <small class="opacity-75">Resuscitation & Trauma Triage</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(config('hospital.is_demo'))
                            <div class="alert alert-warning py-2 px-3 mb-3 d-inline-block small text-dark border-warning">
                                <i class="bi bi-shield-exclamation text-warning me-1"></i><strong>Emergency Notice:</strong> In real emergencies, call <strong>999 / 112</strong> (National Dispatch in Kenya).
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('appointments.create') }}" class="btn btn-primary btn-lg px-4">
                                    <i class="bi bi-calendar2-check me-2"></i>Simulate Department Booking
                                </a>
                                <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg px-4">
                                    <i class="bi bi-info-circle me-2"></i>Demo Contacts
                                </a>
                            </div>
                        @else
                            <div class="d-flex flex-wrap gap-3">
                                <a href="tel:{{ config('hospital.emergency_phone') }}" class="btn btn-danger btn-lg px-4">
                                    <i class="bi bi-telephone-fill me-2"></i>Emergency Hotline: {{ config('hospital.emergency_phone') }}
                                </a>
                                <a href="{{ route('appointments.create') }}" class="btn btn-outline-light btn-lg px-4">
                                    Non-Emergency Booking
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-5 text-center p-4">
                        <img src="{{ asset('assets/img/health/emergency-3.webp') }}" alt="Emergency Care" class="img-fluid rounded-3 shadow">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Departments Grid Section -->
    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">

            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Specialized Care</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Browse Our Departments</h2>
                <p class="text-muted">Dedicated centers of excellence offering personalized inpatient and outpatient clinical treatment.</p>
            </div>

            <div class="row g-4">
                @forelse ($departments as $dept)
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 75 }}">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden d-flex flex-column bg-white">
                            @if ($dept->image)
                                <div class="position-relative overflow-hidden" style="height: 200px;">
                                    <img src="{{ asset($dept->image) }}" alt="{{ $dept->name }}" class="w-100 h-100" style="object-fit: cover;" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-100 h-100 bg-light d-flex align-items-center justify-content-center text-muted\'><i class=\'bi bi-hospital fs-1 text-primary opacity-50\'></i></div>';">
                                    <span class="badge bg-primary position-absolute top-0 end-0 m-3 px-3 py-2">
                                        {{ $dept->doctors_count }} {{ Str::plural('Doctor', $dept->doctors_count) }}
                                    </span>
                                </div>
                            @else
                                <div class="position-relative overflow-hidden bg-primary-subtle d-flex align-items-center justify-content-center" style="height: 140px;">
                                    <i class="bi {{ $dept->icon ?? 'bi-hospital' }} fs-1 text-primary opacity-50"></i>
                                    <span class="badge bg-primary position-absolute top-0 end-0 m-3 px-3 py-2">
                                        {{ $dept->doctors_count }} {{ Str::plural('Doctor', $dept->doctors_count) }}
                                    </span>
                                </div>
                            @endif
                            <div class="card-body p-4 flex-grow-1 d-flex flex-column">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3 fs-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="bi {{ $dept->icon ?? 'bi-hospital' }}"></i>
                                    </div>
                                    <div>
                                        <h4 class="h5 fw-bold mb-0">
                                            <a href="{{ route('departments.show', $dept->slug) }}" class="text-decoration-none text-dark">
                                                {{ $dept->name }}
                                            </a>
                                        </h4>
                                        <small class="text-muted">{{ $dept->head_of_department ?? 'Clinical Directorate' }}</small>
                                    </div>
                                </div>

                                <p class="text-muted small mb-3">
                                    {{ $dept->summary }}
                                </p>

                                @if (!empty($dept->services) && is_array($dept->services))
                                    <div class="mb-3">
                                        <span class="d-block small fw-bold text-dark mb-1">Key Services:</span>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach (array_slice($dept->services, 0, 3) as $srv)
                                                <span class="badge bg-light text-muted border" style="font-size: 0.75rem;">{{ $srv }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-auto pt-3 border-top d-flex gap-2">
                                    <a href="{{ route('departments.show', $dept->slug) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        View Details
                                    </a>
                                    <a href="{{ route('appointments.create', ['dept' => $dept->id]) }}" class="btn btn-sm btn-primary flex-grow-1">
                                        Book Visit
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-hospital fs-1 text-muted d-block mb-2"></i>
                        <h5 class="text-muted">No clinical departments are currently active.</h5>
                    </div>
                @endforelse
            </div>

        </div>
    </section>

    <!-- Appointment Banner -->
    <section class="py-5 bg-white border-top">
        <div class="container text-center py-3">
            <h3 class="fw-bold mb-2" style="color: var(--heading-color);">Cannot Find the Department You Need?</h3>
            <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">
                Our patient coordination desk is on hand to help determine the most appropriate specialist for your symptoms.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="{{ route('contact') }}" class="btn btn-outline-primary px-4">Contact Coordination Desk</a>
                <a href="{{ route('appointments.create') }}" class="btn btn-primary px-4">Request An Appointment</a>
            </div>
        </div>
    </section>

@endsection
