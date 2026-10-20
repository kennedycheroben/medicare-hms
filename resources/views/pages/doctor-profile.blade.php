@extends('layouts.public')

@section('title', $doctor->name . ' - ' . $doctor->specialty)
@section('meta_description', Str::limit(strip_tags($doctor->bio), 150))

@section('content')

    <x-page-header
        :title="$doctor->name"
        :subtitle="$doctor->specialty . ' | ' . ($doctor->department->name ?? 'Clinical Department')"
        :breadcrumbs="[
            ['label' => 'Doctors', 'url' => route('doctors')],
            ['label' => $doctor->name]
        ]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Profile Notice:</strong> This physician profile, credentials, and consultation hours schedule are sample demonstration data for software evaluation.
            </div>
        </div>
    @endif

    <section class="py-5">
        <div class="container" data-aos="fade-up">
            <div class="row g-5">

                <!-- Left Column: Doctor Profile & Credentials -->
                <div class="col-lg-8">

                    <!-- Profile Header Card -->
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
                        <div class="d-flex flex-column flex-sm-row gap-4 align-items-center align-items-sm-start">
                            <img src="{{ asset($doctor->image ?? 'assets/img/health/staff-1.webp') }}" alt="{{ $doctor->name }}" class="rounded-4 img-fluid shadow" style="width: 170px; height: 200px; object-fit: cover;">
                            <div class="flex-grow-1 text-center text-sm-start">
                                @if ($doctor->department)
                                    <a href="{{ route('departments.show', $doctor->department->slug) }}" class="badge bg-primary-subtle text-primary border text-decoration-none mb-2">
                                        <i class="bi bi-hospital me-1"></i>{{ $doctor->department->name }}
                                    </a>
                                @endif
                                <h2 class="h3 fw-bold mb-1" style="color: var(--heading-color);">{{ $doctor->name }}</h2>
                                <p class="text-muted fw-medium mb-2">{{ $doctor->specialty }}</p>

                                <div class="d-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-3 mb-3 small">
                                    <span class="text-warning fw-bold">
                                        <i class="bi bi-star-fill"></i> {{ number_format($doctor->rating, 1) }}
                                        <span class="text-muted fw-normal">({{ $doctor->review_count }} patient reviews)</span>
                                    </span>
                                    <span class="text-muted">&bull;</span>
                                    <span class="text-muted"><i class="bi bi-briefcase me-1 text-primary"></i>{{ $doctor->experience_years }} Years Clinical Practice</span>
                                </div>

                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start">
                                    <a href="{{ route('appointments.create', ['dept' => $doctor->department_id, 'doctor' => $doctor->id]) }}" class="btn btn-primary px-4">
                                        <i class="bi bi-calendar2-check me-2"></i>Book Consultation
                                    </a>
                                    <a href="{{ route('contact') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-chat-dots me-1"></i>Leave an Inquiry
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biography & Clinical Focus -->
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
                        <h3 class="h4 fw-bold mb-3" style="color: var(--heading-color);">Clinical Background & Biography</h3>
                        <p class="text-muted leading-relaxed mb-4">
                            {{ $doctor->bio }}
                        </p>

                        <h4 class="h5 fw-bold mb-3" style="color: var(--heading-color);">Education & Board Certifications</h4>
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-award text-primary fs-3 me-3"></i>
                                <div>
                                    <span class="fw-bold d-block text-dark">{{ $doctor->qualifications }}</span>
                                    <small class="text-muted">Accredited by the Medical Practitioners & Dentists Board</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3 border rounded bg-white">
                                    <span class="text-muted small d-block mb-1"><i class="bi bi-translate text-primary me-1"></i>Spoken Languages</span>
                                    <strong class="text-dark">{{ $doctor->languages ?? 'English, Swahili' }}</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 border rounded bg-white">
                                    <span class="text-muted small d-block mb-1"><i class="bi bi-geo-alt text-primary me-1"></i>Consultation Location</span>
                                    <strong class="text-dark">{{ $doctor->room_number ?? 'Consultation Suite 3B' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consultation Schedule Card -->
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
                        <h3 class="h4 fw-bold mb-3" style="color: var(--heading-color);">Regular Consultation Schedule</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Consultation Days</th>
                                        <th>Working Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-medium">{{ $doctor->consultation_days }}</td>
                                        <td>{{ $doctor->consultation_hours }}</td>
                                        <td>
                                            @if ($doctor->is_available)
                                                <span class="badge bg-success-subtle text-success border border-success">Accepting Patients</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border">Unavailable</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            *Schedule is subject to emergency theatre duties. Confirmed appointments receive priority queuing.
                        </small>
                    </div>

                    <!-- Related Doctors in Department -->
                    @if ($relatedDoctors->isNotEmpty())
                        <div class="mt-5">
                            <h3 class="h4 fw-bold mb-3" style="color: var(--heading-color);">Colleagues in {{ $doctor->department->name ?? 'Department' }}</h3>
                            <div class="row g-3">
                                @foreach ($relatedDoctors as $colleague)
                                    <div class="col-md-4">
                                        <div class="card h-100 border-0 shadow-sm p-3 text-center bg-white">
                                            <img src="{{ asset($colleague->image ?? 'assets/img/health/staff-3.webp') }}" alt="{{ $colleague->name }}" class="rounded-circle mx-auto mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                            <h6 class="fw-bold mb-1">
                                                <a href="{{ route('doctors.show', $colleague->id) }}" class="text-decoration-none text-dark">
                                                    {{ $colleague->name }}
                                                </a>
                                            </h6>
                                            <small class="text-muted d-block mb-2">{{ $colleague->specialty }}</small>
                                            <a href="{{ route('doctors.show', $colleague->id) }}" class="btn btn-sm btn-outline-primary mt-auto">View Profile</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Right Column: Fast Appointment Box -->
                <div class="col-lg-4">

                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-white" style="background: linear-gradient(135deg, #17325b 0%, #0d1e38 100%);">
                        <span class="badge bg-info text-dark fw-bold mb-2 align-self-start">Direct Reservation</span>
                        <h4 class="fw-bold mb-2 text-white">Book with {{ $doctor->name }}</h4>
                        <p class="small text-light opacity-75 mb-3">
                            Submit your visit request to reserve a consultation slot. Our clinical coordinator will confirm your exact time.
                        </p>
                        <a href="{{ route('appointments.create', ['dept' => $doctor->department_id, 'doctor' => $doctor->id]) }}" class="btn btn-primary btn-lg w-100 fw-bold mb-2">
                            Reserve Consultation
                        </a>
                        <small class="text-light opacity-50 d-block text-center" style="font-size: 0.75rem;">No payment required online</small>
                    </div>

                    <!-- Department Emergency Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-3" style="color: var(--heading-color);">Department Inquiries</h5>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <span class="text-muted d-block">Department Name:</span>
                                <strong>{{ $doctor->department->name ?? 'Clinical Staff' }}</strong>
                            </li>
                            <li class="mb-2">
                                <span class="text-muted d-block">Extension:</span>
                                <strong>{{ $doctor->department->phone_extension ?? 'Direct Switchboard' }}</strong>
                            </li>
                            <li>
                                <span class="text-muted d-block">24/7 Department Emergency:</span>
                                <strong class="text-danger">{{ $doctor->department->emergency_contact ?? '+1 (555) 482-7390' }}</strong>
                            </li>
                        </ul>
                    </div>

                    <!-- Patient Assistance Notice -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle-fill text-primary fs-4 me-2"></i>
                            <div>
                                <h6 class="fw-bold mb-1">First-Time Patient?</h6>
                                <p class="small text-muted mb-0">Please arrive 15 minutes prior to your scheduled consultation time to complete registration and initial nurse triage.</p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

@endsection
