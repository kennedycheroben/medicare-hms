@extends('layouts.public')

@section('title', $department->name . ' - Clinical Department')
@section('meta_description', Str::limit(strip_tags($department->summary), 150))

@section('content')

    <x-page-header
        :title="$department->name"
        :subtitle="$department->summary"
        :breadcrumbs="[
            ['label' => 'Departments', 'url' => route('departments')],
            ['label' => $department->name]
        ]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark shadow-sm">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Department Notice:</strong> Departmental details, staffing rosters, and telephone contacts shown are sample demonstration data for software evaluation.
            </div>
        </div>
    @endif

    <section class="py-5">
        <div class="container" data-aos="fade-up">
            <div class="row g-5">

                <!-- Left Column: Department Description, Services, & Specialists -->
                <div class="col-lg-8">

                    @if ($department->image)
                        <div class="rounded-3 overflow-hidden shadow-sm mb-4" style="max-height: 400px;">
                            <img src="{{ asset($department->image) }}" alt="{{ $department->name }}" class="w-100 h-100" style="object-fit: cover;" onerror="this.onerror=null; this.parentElement.style.display='none';">
                        </div>
                    @endif

                    <h2 class="h3 fw-bold mb-3" style="color: var(--heading-color);">Department Overview</h2>
                    <p class="text-muted leading-relaxed mb-4">
                        {{ $department->description }}
                    </p>

                    <!-- Clinical Services Checklist -->
                    @if (!empty($department->services) && is_array($department->services))
                        <div class="my-5 p-4 rounded-3 border bg-light">
                            <h3 class="h4 fw-bold mb-3" style="color: var(--heading-color);">
                                <i class="bi bi-clipboard2-check text-primary me-2"></i>Specialized Medical Services
                            </h3>
                            <div class="row g-3">
                                @foreach ($department->services as $service)
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check2-circle text-primary fs-5 me-2 flex-shrink-0"></i>
                                            <span class="text-dark small fw-medium">{{ $service }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Department Medical Specialists -->
                    <div class="mt-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3 class="h4 fw-bold mb-0" style="color: var(--heading-color);">Department Specialists</h3>
                                <small class="text-muted">Physicians and surgeons currently available for consultation</small>
                            </div>
                            <span class="badge bg-primary px-3 py-2">
                                {{ $department->doctors->count() }} {{ Str::plural('Doctor', $department->doctors->count()) }}
                            </span>
                        </div>

                        <div class="row g-4">
                            @forelse ($department->doctors as $doctor)
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm p-3 rounded-3 d-flex flex-row gap-3 bg-white">
                                        <img src="{{ $doctor->imageUrl() }}" alt="{{ $doctor->name }}" class="rounded flex-shrink-0" style="width: 90px; height: 110px; object-fit: cover;" onerror="this.onerror=null; this.src='/assets/img/health/staff-1.webp';">
                                        <div class="d-flex flex-column justify-content-between flex-grow-1">
                                            <div>
                                                <h5 class="fw-bold mb-1 fs-6">
                                                    <a href="{{ route('doctors.show', $doctor->id) }}" class="text-decoration-none text-dark">
                                                        {{ $doctor->name }}
                                                    </a>
                                                </h5>
                                                <small class="text-primary d-block mb-1">{{ $doctor->specialty }}</small>
                                                <span class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-door-open me-1"></i>{{ $doctor->room_number ?? 'Consultation Suite' }}</span>
                                            </div>
                                            <div class="mt-2">
                                                <a href="{{ route('appointments.create', ['dept' => $department->id, 'doctor' => $doctor->id]) }}" class="btn btn-sm btn-primary w-100">
                                                    Book Consult
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-light border text-center py-3">
                                        <p class="mb-0 text-muted">Specialist listings for this department are being updated. Call our reception desk for inquiries.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

                <!-- Right Column: Sidebar & Actions -->
                <div class="col-lg-4">

                    <!-- Quick Appointment Card -->
                    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #fff;">
                        <h4 class="fw-bold mb-2 text-white">Need an Appointment?</h4>
                        <p class="small text-white-50 mb-3">Schedule a consultation with specialists in {{ $department->name }}.</p>
                        <a href="{{ route('appointments.create', ['dept' => $department->id]) }}" class="btn btn-light fw-bold text-primary w-100 mb-2">
                            <i class="bi bi-calendar-check me-1"></i>Book Consultation
                        </a>
                        <small class="d-block text-center text-white-50" style="font-size: 0.75rem;">{{ config('hospital.is_demo') ? 'Simulated appointment workflow' : 'Fast online confirmation response' }}</small>
                    </div>

                    <!-- Department Contact Card -->
                    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-3" style="color: var(--heading-color);">Department Directory</h5>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-person-badge text-primary me-2 fs-5"></i>
                                <div>
                                    <strong>Head of Department:</strong>
                                    <span class="d-block text-muted">{{ $department->head_of_department ?? 'Senior Medical Consultant' }}</span>
                                </div>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-telephone text-primary me-2 fs-5"></i>
                                <div>
                                    <strong>Phone Extension:</strong>
                                    <span class="d-block text-muted">{{ $department->phone_extension ?? 'Direct Dial via Switchboard' }}</span>
                                </div>
                            </li>
                            <li class="mb-0 d-flex align-items-center">
                                <i class="bi bi-shield-exclamation text-danger me-2 fs-5"></i>
                                <div>
                                    <strong>Emergency Guidance:</strong>
                                    @if(config('hospital.is_demo'))
                                        <span class="d-block text-warning fw-bold">Call 999 / 112 (National Dispatch)</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Simulated department profile</small>
                                    @else
                                        <span class="d-block text-danger fw-bold">{{ $department->emergency_contact ?? config('hospital.emergency_phone') }}</span>
                                    @endif
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Other Departments Widget -->
                    @if ($otherDepartments->isNotEmpty())
                        <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                            <h5 class="fw-bold mb-3" style="color: var(--heading-color);">Other Clinical Specialties</h5>
                            <div class="list-group list-group-flush">
                                @foreach ($otherDepartments as $other)
                                    <a href="{{ route('departments.show', $other->slug) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi {{ $other->icon ?? 'bi-hospital' }} text-primary me-2"></i>
                                            <span class="small fw-medium">{{ $other->name }}</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted small"></i>
                                    </a>
                                @endforeach
                            </div>
                            <a href="{{ route('departments') }}" class="btn btn-sm btn-outline-secondary w-100 mt-3">
                                View All Departments
                            </a>
                        </div>
                    @endif

                </div>

            </div>
        </div>
    </section>

@endsection
