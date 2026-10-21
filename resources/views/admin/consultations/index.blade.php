@extends('layouts.admin')

@section('title', 'Doctor Consultation Queue')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Doctor Consultation Queue & EHR</h1>
            <p class="text-muted small mb-0">Review triaged patients awaiting consultation, record clinical assessments, and manage encounter workflows.</p>
        </div>
        <div>
            <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-person-search"></i> Patient Master Index
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

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.consultations.index') }}" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by Patient Name, MRN, or Visit #..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter Queue</button>
                    @if($search)
                        <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Active Consultation Queue Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-person-lines-fill text-primary"></i> Awaiting Doctor Consultation
                <span class="badge bg-primary rounded-pill">{{ $queue->total() }}</span>
            </h5>
            <span class="text-muted small">Sorted by clinical priority and triage timestamp</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Priority</th>
                        <th>Visit Number</th>
                        <th>Patient Name & MRN</th>
                        <th>Vitals & Triage Summary</th>
                        <th>Department</th>
                        <th>Assigned Doctor</th>
                        <th>Wait Time</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($queue as $visit)
                        <tr>
                            <td class="ps-4">
                                @if($visit->priority === 'emergency')
                                    <span class="badge bg-danger px-2 py-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Emergency</span>
                                @elseif($visit->priority === 'urgent')
                                    <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Urgent</span>
                                @else
                                    <span class="badge bg-secondary px-2 py-1">Standard</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $visit->visit_number }}</span>
                                <div class="text-muted small text-capitalize">{{ str_replace('_', ' ', $visit->visit_type) }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.patients.show', $visit->patient_id) }}" class="fw-bold text-decoration-none text-primary">
                                    {{ $visit->patient->full_name }}
                                </a>
                                <div class="text-muted small">MRN: <span class="font-monospace">{{ $visit->patient->mrn }}</span> &bull; {{ ucfirst($visit->patient->gender) }}, {{ $visit->patient->age ?? 'N/A' }} yrs</div>
                                @if(!empty($visit->patient->allergies))
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1 small">
                                        <i class="bi bi-shield-exclamation"></i> Allergies Noted
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($visit->triageRecord)
                                    <div class="small">
                                        <span class="text-muted">BP:</span> <span class="fw-semibold">{{ $visit->triageRecord->blood_pressure ?? 'N/A' }}</span> &bull;
                                        <span class="text-muted">HR:</span> <span class="fw-semibold">{{ $visit->triageRecord->pulse_rate ?? 'N/A' }}</span> &bull;
                                        <span class="text-muted">SpO2:</span> <span class="fw-semibold">{{ $visit->triageRecord->oxygen_saturation ? $visit->triageRecord->oxygen_saturation.'%' : 'N/A' }}</span>
                                    </div>
                                    <div class="text-muted small text-truncate" style="max-width: 250px;" title="{{ $visit->triageRecord->nursing_notes }}">
                                        {{ $visit->triageRecord->presenting_complaint ?? $visit->reason_for_visit ?? 'No chief complaint' }}
                                    </div>
                                @else
                                    <span class="badge bg-secondary">Awaiting Vitals</span>
                                @endif
                            </td>
                            <td>{{ $visit->department->name ?? 'General Outpatient' }}</td>
                            <td>
                                @if($visit->doctor)
                                    <span class="text-dark fw-medium">{{ $visit->doctor->name }}</span>
                                @else
                                    <span class="text-muted font-italic">Unassigned (First Available)</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted small">{{ $visit->checked_in_at ? $visit->checked_in_at->diffForHumans() : 'N/A' }}</span>
                            </td>
                            <td class="pe-4 text-end">
                                @if($visit->consultation)
                                    <a href="{{ route('admin.consultations.show', $visit->consultation->id) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-box-arrow-in-right"></i> Resume
                                    </a>
                                @else
                                    <form method="POST" action="{{ route('admin.consultations.start', $visit->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-play-circle"></i> Start Consult
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <div class="fw-bold fs-6">No patients currently waiting in consultation queue</div>
                                <p class="small mb-0">Patients triaged by nursing staff will automatically appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($queue->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $queue->links() }}
            </div>
        @endif
    </div>

    <!-- Recent Completed Consultations by Attending Doctor -->
    @if($recentConsultations->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-secondary"></i> My Recent Consultations
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Date & Time</th>
                            <th>Patient Name & MRN</th>
                            <th>Visit #</th>
                            <th>Primary Diagnosis</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentConsultations as $consult)
                            <tr>
                                <td class="ps-4 small">{{ $consult->updated_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <span class="fw-bold">{{ $consult->patient->full_name }}</span>
                                    <div class="text-muted small font-monospace">{{ $consult->patient->mrn }}</div>
                                </td>
                                <td>{{ $consult->visit->visit_number ?? 'N/A' }}</td>
                                <td>
                                    <span class="fw-medium text-dark">{{ $consult->primary_diagnosis ?? 'In progress' }}</span>
                                    @if($consult->diagnosis_type)
                                        <span class="badge bg-light text-secondary border ms-1 small">{{ ucfirst($consult->diagnosis_type) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($consult->isSigned())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-lock-fill me-1"></i> Signed</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('admin.consultations.show', $consult->id) }}" class="btn btn-sm btn-outline-primary">
                                        View Record
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
