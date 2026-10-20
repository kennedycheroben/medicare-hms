@extends('layouts.public')

@section('title', 'Specialist Directory & Vetted Doctors')
@section('meta_description', 'Search and filter board-certified physicians, surgeons, and medical consultants at MediCare Hospital. Book consultations online.')

@section('content')

    <x-page-header
        title="Find a Doctor"
        subtitle="Search Our Vetted Directory of Board-Certified Specialists and Healthcare Practitioners"
        :breadcrumbs="[['label' => 'Doctors']]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Roster Notice:</strong> Physician profiles, credentials, and schedules below are sample demonstration records for software evaluation. Active clinician rosters are maintained and verified by the hospital administration.
            </div>
        </div>
    @endif

    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">

            <!-- Filter Toolbar -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <form action="{{ route('doctors') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="search" class="form-label small fw-bold text-dark">Search Doctor or Specialty</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" id="search" class="form-control border-start-0" placeholder="e.g. Dr. Vance, Cardiology, Surgery..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="department" class="form-label small fw-bold text-dark">Filter by Department</label>
                        <select name="department" id="department" class="form-select">
                            <option value="">All Departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->slug }}" {{ request('department') == $dept->slug ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        @if (request()->hasAny(['search', 'department']))
                            <a href="{{ route('doctors') }}" class="btn btn-outline-secondary" title="Reset Filters">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Results Meta & Status -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="text-muted small mb-0">
                    Showing <strong class="text-dark">{{ $doctors->firstItem() ?? 0 }}</strong> to <strong class="text-dark">{{ $doctors->lastItem() ?? 0 }}</strong> of <strong class="text-dark">{{ $doctors->total() }}</strong> medical specialists
                </p>
                @if (request('search') || request('department'))
                    <span class="badge bg-light text-dark border">
                        Filtered Active
                    </span>
                @endif
            </div>

            <!-- Doctor Grid -->
            <div class="row g-4">
                @forelse ($doctors as $doctor)
                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 75 }}">
                        <article class="clinician-row bg-white p-4 rounded-3 shadow-sm border d-flex flex-column flex-sm-row gap-3 h-100">
                            <div class="row-media flex-shrink-0 text-center">
                                <img src="{{ asset($doctor->image ?? 'assets/img/health/staff-1.webp') }}" alt="{{ $doctor->name }}" class="rounded-3 img-fluid shadow-sm" style="width: 140px; height: 165px; object-fit: cover;">
                            </div>
                            <div class="row-body flex-grow-1 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-info-subtle text-info border mb-1">
                                                {{ $doctor->department->name ?? 'General Medicine' }}
                                            </span>
                                            <h4 class="h5 fw-bold mb-1">
                                                <a href="{{ route('doctors.show', $doctor->id) }}" class="text-decoration-none text-dark">
                                                    {{ $doctor->name }}
                                                </a>
                                            </h4>
                                            <p class="text-muted small mb-2">{{ $doctor->specialty }}</p>
                                        </div>
                                        <div class="text-end small">
                                            <span class="text-warning fw-bold"><i class="bi bi-star-fill"></i> {{ number_format($doctor->rating, 1) }}</span>
                                            <span class="text-muted d-block" style="font-size: 0.75rem;">({{ $doctor->review_count }} reviews)</span>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled small text-muted mb-3">
                                        <li class="mb-1"><i class="bi bi-award text-primary me-2"></i>{{ $doctor->qualifications }}</li>
                                        <li class="mb-1"><i class="bi bi-clock-history text-primary me-2"></i>{{ $doctor->consultation_days }} ({{ $doctor->consultation_hours }})</li>
                                        <li><i class="bi bi-geo-alt text-primary me-2"></i>{{ $doctor->room_number ?? 'Consultation Suite' }}</li>
                                    </ul>
                                </div>

                                <div class="d-flex gap-2 pt-2 border-top">
                                    <a href="{{ route('doctors.show', $doctor->id) }}" class="btn btn-sm btn-outline-secondary flex-grow-1">
                                        View Profile
                                    </a>
                                    <a href="{{ route('appointments.create', ['dept' => $doctor->department_id, 'doctor' => $doctor->id]) }}" class="btn btn-sm btn-primary flex-grow-1">
                                        <i class="bi bi-calendar-plus me-1"></i>Book Visit
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card border-0 shadow-sm p-5 text-center bg-white">
                            <i class="bi bi-person-slash fs-1 text-muted mb-3"></i>
                            <h4 class="fw-bold text-dark">No specialists found matching your search</h4>
                            <p class="text-muted mb-4">Try relaxing your search terms or clearing the department filter.</p>
                            <div>
                                <a href="{{ route('doctors') }}" class="btn btn-primary px-4">Reset All Filters</a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if ($doctors->hasPages())
                <div class="mt-5 d-flex justify-content-center">
                    {{ $doctors->links('pagination::bootstrap-5') }}
                </div>
            @endif

        </div>
    </section>

@endsection
