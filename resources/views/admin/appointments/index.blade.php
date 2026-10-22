@extends('layouts.admin')

@section('title', 'Appointment Management & Booking Queue')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Appointment Management</h1>
            <p class="text-muted small mb-0">Review public online appointment requests, verify clinical availability, reconcile patient records, and dispatch to intake.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.intake.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-box-arrow-in-right"></i> Front-Desk Intake Station
            </a>
            <a href="{{ route('appointments.create') }}" target="_blank" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> Public Booking Portal
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pending Review</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5">Needs Action</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['pending']) }}</div>
                <div class="extra-small text-muted mt-1">Unprocessed online booking requests</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Confirmed Slots</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2.5">Confirmed</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['confirmed']) }}</div>
                <div class="extra-small text-muted mt-1">Ready for patient arrival and check-in</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Today's Schedule</span>
                    <span class="badge bg-info-subtle text-info rounded-pill px-2.5">Today</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['today']) }}</div>
                <div class="extra-small text-muted mt-1">Appointments booked for {{ today()->format('M d, Y') }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pending Reconciliation</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5">Unlinked</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['unreconciled']) }}</div>
                <div class="extra-small text-muted mt-1">Bookings without linked PMI record</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.appointments.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search Reference, Name, Phone, Email..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="">-- All Statuses --</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="rescheduled" {{ $status === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="">-- All Departments --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="date" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="">-- Any Date --</option>
                        <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today Only</option>
                        <option value="upcoming" {{ $dateFilter === 'upcoming' ? 'selected' : '' }}>Today & Upcoming</option>
                        <option value="past" {{ $dateFilter === 'past' ? 'selected' : '' }}>Past Dates</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    @if($search || $status || $departmentId || $dateFilter)
                        <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Appointment Requests Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check text-primary"></i> Incoming Appointments
                <span class="badge bg-primary rounded-pill">{{ $appointments->total() }}</span>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Reference</th>
                        <th>Patient Details</th>
                        <th>Department & Clinician</th>
                        <th>Scheduled Date & Time</th>
                        <th>PMI Reconciliation</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $apt)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-primary font-monospace">{{ $apt->reference_code }}</span>
                                <div class="extra-small text-muted">{{ $apt->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $apt->patient_name }}</div>
                                <div class="small text-muted d-flex align-items-center gap-2">
                                    <span><i class="bi bi-telephone me-1"></i>{{ $apt->patient_phone }}</span>
                                    @if($apt->patient_email)
                                        <span>&bull; {{ $apt->patient_email }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $apt->department->name ?? 'Unassigned' }}</div>
                                <div class="small text-muted">
                                    {{ $apt->doctor ? 'Dr. ' . $apt->doctor->name : 'Any Available Specialist' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ $apt->appointment_date ? $apt->appointment_date->format('D, M d, Y') : 'Date Unset' }}
                                    @if($apt->appointment_date && $apt->appointment_date->isToday())
                                        <span class="badge bg-info-subtle text-info ms-1">Today</span>
                                    @endif
                                </div>
                                <div class="small text-muted"><i class="bi bi-clock me-1"></i>{{ $apt->appointment_time }}</div>
                            </td>
                            <td>
                                @if($apt->patient)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i> {{ $apt->patient->mrn }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="bi bi-exclamation-circle me-1"></i> Unreconciled
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($apt->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning">Pending Review</span>
                                @elseif($apt->status === 'confirmed')
                                    <span class="badge bg-success-subtle text-success">Confirmed</span>
                                @elseif($apt->status === 'rescheduled')
                                    <span class="badge bg-primary-subtle text-primary">Rescheduled</span>
                                @elseif($apt->status === 'completed')
                                    <span class="badge bg-secondary-subtle text-secondary">Checked In / Completed</span>
                                @elseif($apt->status === 'cancelled')
                                    <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ ucfirst($apt->status) }}</span>
                                @endif

                                @if($apt->visit)
                                    <div class="extra-small text-muted mt-1">Visit: {{ $apt->visit->visit_number }}</div>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('admin.appointments.show', $apt->id) }}" class="btn btn-sm btn-outline-primary">
                                    Review <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                No appointment booking records found matching current criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
