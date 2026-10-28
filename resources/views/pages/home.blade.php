@extends('layouts.public')

@section('title', config('hospital.is_demo') ? 'Healthcare Management Demonstration System' : 'Accredited Healthcare & Medical Excellence')
@section('meta_description', config('hospital.is_demo') ? 'Interactive demonstration of the MediCare Hospital Management System (HMS), showcasing clinical triage, appointments, and hospital operations.' : 'MediCare Hospital is a leading healthcare facility offering clinical response, specialized medical departments, and skilled doctors.')

@section('content')

    @if(config('hospital.is_demo'))
        <div class="container pt-5 mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark shadow-sm">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Software Demonstration Notice:</strong> This platform is a demonstration of the MediCare Hospital Management System (HMS) for software evaluation. Medical provider profiles, clinical departments, appointments, and contact numbers are sample demonstration records.
            </div>
        </div>
    @endif

    <!-- Hero Section -->
    <section id="hero" class="hero section position-relative">

        <div class="hero-slider swiper init-swiper">
            <script type="application/json" class="swiper-config">
                {
                    "loop": true,
                    "speed": 800,
                    "effect": "fade",
                    "fadeEffect": {
                        "crossFade": true
                    },
                    "autoplay": {
                        "delay": 6000
                    },
                    "slidesPerView": 1
                }
            </script>
            @php
                $heroSlides = [
                    asset('assets/img/health/showcase-1.webp'),
                    asset('assets/img/health/showcase-7.webp'),
                    asset('assets/img/health/showcase-11.webp'),
                ];
            @endphp
            <div class="swiper-wrapper">
                @foreach ($heroSlides as $slide)
                    <div class="swiper-slide" style="background-image: url('{{ $slide }}');"></div>
                @endforeach
            </div>
            <div class="slide-overlay"></div>
        </div>

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row align-items-center g-5">

                <div class="col-lg-7" data-aos="fade-up" data-aos-delay="150">
                    <div class="hero-content">
                        <span class="hero-badge">
                            <span class="badge-dot"></span> {{ config('hospital.is_demo') ? 'HMS Demonstration Platform' : 'Accredited Medical Center' }}
                        </span>

                        <h1 class="hero-headline">Exceptional Healthcare, Centered on Every Patient</h1>
                        <p class="hero-subtext">{{ config('hospital.is_demo') ? 'An enterprise healthcare platform demonstrating patient scheduling, clinical triage, digital health records, and pharmacy management with simulated data.' : 'Delivering advanced diagnostics, specialized clinical departments, and compassionate medical care. Our dedicated specialists provide patient-first medical support from admission through recovery.' }}</p>

                        <div class="action-row">
                            <a href="{{ route('appointments.create') }}" class="btn btn-solid">Schedule a Visit</a>
                            <a href="{{ route('departments') }}" class="btn btn-outline">
                                <i class="bi bi-hospital"></i>
                                <span>Clinical Departments</span>
                            </a>
                        </div>

                        <ul class="meta-row">
                            <li>
                                <a href="{{ route('appointments.create') }}" class="meta-link">
                                    <i class="bi bi-calendar-check text-info"></i>
                                    <span>Online Booking Request</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('doctors') }}" class="meta-link">
                                    <i class="bi bi-person-badge text-info"></i>
                                    <span>Specialist Directory</span>
                                </a>
                            </li>
                            <li>
                                @if(config('hospital.is_demo'))
                                    <a href="{{ route('guide') }}" class="meta-link">
                                        <i class="bi bi-info-circle text-info"></i>
                                        <span>System Guide &amp; Demo</span>
                                    </a>
                                @else
                                    <a href="tel:{{ config('hospital.emergency_phone') }}" class="meta-link">
                                        <i class="bi bi-telephone-inbound text-danger"></i>
                                        <span>Emergency Line</span>
                                    </a>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-5" data-aos="fade-up" data-aos-delay="250">
                    <div class="info-stack">

                        @if(config('hospital.is_demo'))
                            <div class="info-card emergency-card" style="border-left: 4px solid #ffc107;">
                                <div class="card-icon text-warning">
                                    <i class="bi bi-shield-exclamation"></i>
                                </div>
                                <div class="card-meta">
                                    <span class="card-label text-warning fw-bold">Emergency Dispatch</span>
                                    <span class="card-value fs-6">Call 999 / 112 (National)</span>
                                </div>
                                <a href="{{ route('contact') }}" class="card-action" aria-label="Emergency guidance and contacts">
                                    <i class="bi bi-info-circle"></i>
                                </a>
                            </div>
                        @else
                            <div class="info-card emergency-card">
                                <div class="card-icon">
                                    <i class="bi bi-telephone-fill"></i>
                                </div>
                                <div class="card-meta">
                                    <span class="card-label">Emergency Desk</span>
                                    <span class="card-value">{{ config('hospital.emergency_phone') }}</span>
                                </div>
                                <a href="tel:{{ config('hospital.emergency_phone') }}" class="card-action" aria-label="Call emergency hotline">
                                    <i class="bi bi-arrow-up-right"></i>
                                </a>
                            </div>
                        @endif

                        <div class="info-card stats-card">
                            <div class="stat-cell">
                                <span class="stat-number">{{ $departments->count() }}+</span>
                                <span class="stat-label">Clinical Departments</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-cell">
                                <span class="stat-number">{{ config('hospital.is_demo') ? 'Demo' : '24/7' }}</span>
                                <span class="stat-label">{{ config('hospital.is_demo') ? 'Simulated Workflow' : 'Emergency Coverage' }}</span>
                            </div>
                        </div>

                        <div class="info-card review-card">
                            <div class="review-header">
                                <span class="fw-bold text-primary"><i class="bi bi-shield-check me-1"></i>{{ config('hospital.is_demo') ? 'Software Demonstration' : 'Clinical Governance' }}</span>
                                <span class="badge bg-light text-secondary border">{{ config('hospital.is_demo') ? 'Simulated Records' : 'Patient-First Care' }}</span>
                            </div>
                            <p class="review-quote">"Compassionate nursing, multidisciplinary physician collaboration, and state-of-the-art diagnostics supporting inpatient and outpatient care."</p>
                            <div class="review-author">
                                <div class="author-info">
                                    <span class="author-name">{{ config('hospital.name', 'MediCare Hospital') }}</span>
                                    <span class="author-role">{{ config('hospital.tagline', 'Healthcare Management System') }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Metrics Bar -->
            <div class="metrics-bar" data-aos="fade-up" data-aos-delay="300">
                <div class="metric-item">
                    <div class="metric-icon">
                        <i class="bi bi-hospital"></i>
                    </div>
                    <div class="metric-text">
                        <span class="metric-number">{{ $departments->count() }}</span>
                        <span class="metric-label">Clinical Departments</span>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="metric-text">
                        <span class="metric-number">{{ $doctors->count() }}+</span>
                        <span class="metric-label">Active Specialists</span>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="metric-text">
                        <span class="metric-number">24/7</span>
                        <span class="metric-label">Emergency Coverage</span>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="metric-text">
                        <span class="metric-number">Verified</span>
                        <span class="metric-label">Hospital Standards</span>
                    </div>
                </div>
            </div>

        </div>

    </section>

    <!-- Home About Section -->
    <section id="home-about" class="home-about section py-5">
        <div class="container" data-aos="fade-up">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="position-relative">
                        <img src="{{ asset('assets/img/health/consultation-3.webp') }}" alt="Medical Consultation" class="img-fluid rounded shadow-sm">
                        <div class="position-absolute bottom-0 end-0 bg-primary text-white p-3 rounded m-3 shadow d-none d-sm-block">
                            <span class="h4 fw-bold mb-0 d-block">{{ config('hospital.is_demo') ? 'Level 1 Demo' : 'Level 1' }}</span>
                            <span class="small">{{ config('hospital.is_demo') ? 'Trauma Simulation Facility' : 'Accredited Trauma Facility' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <span class="text-primary text-uppercase fw-bold small">About MediCare Hospital</span>
                    <h2 class="display-6 fw-bold mt-2 mb-4" style="color: var(--heading-color);">Modern Clinical Excellence Meets Compassionate Care</h2>
                    <p class="text-muted leading-relaxed">
                        MediCare Hospital is a modern tertiary healthcare facility committed to elevating patient care across East Africa. We blend state-of-the-art medical technology with empathetic patient support to ensure every individual receives the right care at the right time.
                    </p>
                    <div class="row g-3 my-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-info fs-4 me-2"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">State-of-the-Art Labs</h6>
                                    <p class="small text-muted mb-0">Automated diagnostic and molecular pathology units.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-info fs-4 me-2"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">24/7 Emergency Wing</h6>
                                    <p class="small text-muted mb-0">Rapid triage bays and dedicated surgical suites.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-info fs-4 me-2"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Top Medical Specialists</h6>
                                    <p class="small text-muted mb-0">Internationally certified physicians and surgeons.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-info fs-4 me-2"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Direct Insurance Billing</h6>
                                    <p class="small text-muted mb-0">Seamless coverage with major regional & global insurers.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('about') }}" class="btn btn-outline-primary px-4 py-2 me-2">Discover Our Story</a>
                        <a href="{{ route('appointments.create') }}" class="btn btn-primary px-4 py-2">Book Consultation</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Departments Section -->
    <section id="featured-departments" class="featured-departments section light-background py-5">
        <div class="container" data-aos="fade-up">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
                <div>
                    <span class="text-primary text-uppercase fw-bold small">Specialized Medicine</span>
                    <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Clinical Departments</h2>
                    <p class="text-muted mb-0">Multidisciplinary centers of excellence equipped for precision care.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('departments') }}" class="btn btn-outline-primary">
                        View All Departments <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="row g-4">
                @forelse ($departments as $department)
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 100 }}">
                        <article class="shadcn-card h-100 d-flex flex-column bg-white rounded shadow-sm border p-4">
                            <header class="card-head d-flex justify-content-between align-items-center mb-3">
                                <span class="card-icon fs-2 text-primary"><i class="bi {{ $department->icon ?? 'bi-hospital' }}"></i></span>
                                <span class="badge bg-light text-primary border">Specialty</span>
                            </header>
                            <div class="card-body p-0 flex-grow-1">
                                <h4 class="card-heading fw-bold mb-2">
                                    <a href="{{ route('departments.show', $department->slug) }}" class="text-decoration-none text-dark">
                                        {{ $department->name }}
                                    </a>
                                </h4>
                                <p class="card-text text-muted small mb-3">
                                    {{ Str::limit($department->summary, 120) }}
                                </p>
                            </div>
                            <footer class="card-foot pt-3 mt-auto border-top d-flex justify-content-between align-items-center">
                                <div class="foot-stat small text-muted">
                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                    <span>{{ $department->head_of_department ?? 'Senior Consultant' }}</span>
                                </div>
                                <a href="{{ route('departments.show', $department->slug) }}" class="btn btn-sm btn-outline-primary" aria-label="Details for {{ $department->name }}">
                                    Explore <i class="bi bi-arrow-right"></i>
                                </a>
                            </footer>
                        </article>
                    </div>
                @empty
                    <div class="col-12 text-center py-4">
                        <p class="text-muted">No clinical departments currently available.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </section>

    <!-- Featured Services Section -->
    <section id="featured-services" class="featured-services section py-5">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Comprehensive Healthcare</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Hospital Services & Facilities</h2>
                <p class="text-muted">Designed for maximum patient comfort, rapid diagnosis, and recovery.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-shield-plus"></i></div>
                        <h5 class="fw-bold mb-2">24/7 Emergency Triage</h5>
                        <p class="text-muted small">Instant resuscitation, trauma response teams, and round-the-clock emergency surgical availability.</p>
                        <a href="{{ route('services') }}" class="mt-auto text-primary text-decoration-none small fw-bold">Read details &rarr;</a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-cpu"></i></div>
                        <h5 class="fw-bold mb-2">Advanced Diagnostics</h5>
                        <p class="text-muted small">High-resolution 3T MRI, 128-slice CT scans, digital mammography, and fully automated hematology laboratories.</p>
                        <a href="{{ route('services') }}" class="mt-auto text-primary text-decoration-none small fw-bold">Read details &rarr;</a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-heart-pulse"></i></div>
                        <h5 class="fw-bold mb-2">Intensive Care Units</h5>
                        <p class="text-muted small">Specialized Adult ICU, Pediatric ICU, and Level III Neonatal ICU with 1:1 dedicated nursing protocols.</p>
                        <a href="{{ route('services') }}" class="mt-auto text-primary text-decoration-none small fw-bold">Read details &rarr;</a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="card h-100 border-0 shadow-sm p-4 text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-capsule"></i></div>
                        <h5 class="fw-bold mb-2">24/7 Pharmacy Service</h5>
                        <p class="text-muted small">Full-service inpatient and outpatient pharmaceutical dispensary with certified clinical pharmacists.</p>
                        <a href="{{ route('services') }}" class="mt-auto text-primary text-decoration-none small fw-bold">Read details &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section id="find-a-doctor" class="find-a-doctor section light-background py-5">
        <div class="container" data-aos="fade-up">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
                <div>
                    <span class="text-primary text-uppercase fw-bold small">Clinical Specialists</span>
                    <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Meet Our Doctors</h2>
                    <p class="text-muted mb-0">{{ config('hospital.is_demo') ? 'Sample specialist profiles illustrating doctor scheduling, credentials, and department assignment.' : 'Experienced leaders across primary, acute, and specialized medicine.' }}</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('doctors') }}" class="btn btn-outline-primary">
                        View Directory <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="row g-4">
                @forelse ($doctors as $doctor)
                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 100 }}">
                        <article class="clinician-row bg-white p-3 rounded shadow-sm border d-flex flex-column flex-sm-row gap-3">
                            <div class="row-media flex-shrink-0 text-center">
                                <img src="{{ $doctor->imageUrl() }}" alt="{{ $doctor->name }}" class="rounded img-fluid" style="width: 140px; height: 160px; object-fit: cover;" onerror="this.onerror=null; this.src='/assets/img/health/staff-1.webp';">
                            </div>
                            <div class="row-body flex-grow-1 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-info-subtle text-info border mb-1">{{ $doctor->department->name ?? 'Specialist' }}</span>
                                            <h4 class="h5 fw-bold mb-1">
                                                <a href="{{ route('doctors.show', $doctor->id) }}" class="text-decoration-none text-dark">
                                                    {{ $doctor->name }}
                                                </a>
                                            </h4>
                                            <p class="text-muted small mb-2">{{ $doctor->specialty }}</p>
                                        </div>
                                        <div class="text-end small">
                                            <span class="text-warning"><i class="bi bi-star-fill"></i> {{ number_format($doctor->rating, 1) }}</span>
                                            <span class="text-muted d-block" style="font-size: 0.75rem;">({{ $doctor->review_count }} reviews)</span>
                                        </div>
                                    </div>
                                    <ul class="list-inline small text-muted mb-2">
                                        <li class="list-inline-item"><i class="bi bi-award me-1 text-primary"></i>{{ $doctor->qualifications }}</li>
                                        <li class="list-inline-item"><i class="bi bi-briefcase me-1 text-primary"></i>{{ $doctor->experience_years }} yrs exp.</li>
                                    </ul>
                                </div>
                                <div class="d-flex gap-2 pt-2 border-top mt-2">
                                    <a href="{{ route('doctors.show', $doctor->id) }}" class="btn btn-sm btn-outline-secondary flex-grow-1">Profile</a>
                                    <a href="{{ route('appointments.create', ['dept' => $doctor->department_id, 'doctor' => $doctor->id]) }}" class="btn btn-sm btn-primary flex-grow-1">Book Visit</a>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12 text-center py-4">
                        <p class="text-muted">No doctors currently listed.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </section>

    <!-- Call to Action Banner -->
    <section class="py-5 bg-primary text-white position-relative">
        <div class="container text-center py-4" data-aos="zoom-in">
            @if(config('hospital.is_demo'))
                <h2 class="display-6 fw-bold mb-3">Explore MediCare HMS in Action</h2>
                <p class="lead mb-4 mx-auto" style="max-width: 750px;">
                    Test patient appointment scheduling, simulated consultation queues, and dispensary workflows using demo data. For real medical emergencies, please dial national dispatch (<strong>999 / 112</strong> in Kenya).
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('appointments.create') }}" class="btn btn-light btn-lg text-primary fw-bold px-4">
                        <i class="bi bi-calendar2-check me-2"></i>Simulate Appointment Booking
                    </a>
                    <a href="{{ route('guide') }}" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-journal-text me-2"></i>Read User Workbook
                    </a>
                </div>
            @else
                <h2 class="display-6 fw-bold mb-3">Need Immediate Clinical Assistance?</h2>
                <p class="lead mb-4 mx-auto" style="max-width: 750px;">
                    Our medical triage officers are ready 24/7. Whether booking a routine consultation or requesting immediate emergency ambulance dispatch, we are here for you.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('appointments.create') }}" class="btn btn-light btn-lg text-primary fw-bold px-4">
                        <i class="bi bi-calendar2-check me-2"></i>Book an Appointment Online
                    </a>
                    <a href="tel:{{ config('hospital.emergency_phone') }}" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-telephone-outbound me-2"></i>Call Emergency: {{ config('hospital.emergency_phone') }}
                    </a>
                </div>
            @endif
        </div>
    </section>

@endsection
