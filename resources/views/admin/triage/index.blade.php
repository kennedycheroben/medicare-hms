@extends('layouts.admin')

@section('title', 'Nursing Triage Queue')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Nursing Triage & Clinical Prioritization</h1>
            <p class="text-muted small mb-0">Record patient vital signs, evaluate clinical acuity, and direct to appropriate physician consultation queues.</p>
        </div>
        <div class="alert alert-light border small text-muted mb-0 py-2 px-3">
            <i class="bi bi-shield-check text-success me-1"></i> Protocol: Standard 5-Tier Emergency Severity & Triage Index
        </div>
    </div>

    <!-- Triage Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="stat-card border-warning border-opacity-50">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Waiting for Triage</div>
                        <div class="fs-4 fw-bold text-warning mt-1">{{ number_format($stats['waiting']) }}</div>
                    </div>
                    <span class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="stat-card border-danger border-opacity-50">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Emergency Cases</div>
                        <div class="fs-4 fw-bold text-danger mt-1">{{ number_format($stats['emergency']) }}</div>
                    </div>
                    <span class="p-3 rounded-circle bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-exclamation-octagon fs-4"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="stat-card border-success border-opacity-50">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Triaged Today</div>
                        <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['triaged_today']) }}</div>
                    </div>
                    <span class="p-3 rounded-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-clipboard2-check fs-4"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.triage.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <option value="emergency" {{ ($filters['priority'] ?? '') === 'emergency' ? 'selected' : '' }}>Emergency Priority</option>
                        <option value="urgent" {{ ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' }}>Urgent Priority</option>
                        <option value="standard" {{ ($filters['priority'] ?? '') === 'standard' ? 'selected' : '' }}>Standard Priority</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <select name="department_id" class="form-select">
                        <option value="">All Destination Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter Queue</button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.triage.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Triage Waiting Queue Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h2 class="h5 fw-bold text-dark mb-0">
                <i class="bi bi-heart-pulse text-primary me-2"></i> Active Triage Queue
            </h2>
            <span class="badge bg-light text-secondary border">{{ $visits->total() }} Active Cases</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Visit #</th>
                        <th>Arrival</th>
                        <th>Patient Identity</th>
                        <th>Priority</th>
                        <th>Presenting Complaint</th>
                        <th>Status & Vitals</th>
                        <th>Clinic & Doctor</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                        <tr class="{{ $visit->priority === 'emergency' ? 'table-danger' : ($visit->priority === 'urgent' ? 'table-warning' : '') }}">
                            <td><code class="fw-bold">{{ $visit->visit_number }}</code></td>
                            <td>{{ $visit->checked_in_at ? $visit->checked_in_at->format('H:i') : '—' }}</td>
                            <td>
                                <a href="{{ route('admin.patients.show', $visit->patient_id) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $visit->patient->full_name }}
                                </a>
                                <div>
                                    <code class="text-muted">{{ $visit->patient->mrn }}</code> | 
                                    {{ $visit->patient->age }} yrs, {{ ucfirst($visit->patient->gender) }}
                                </div>
                                @if($visit->patient->allergies)
                                    <div class="badge bg-danger-subtle text-danger mt-1">
                                        <i class="bi bi-exclamation-triangle"></i> Allergies: {{ $visit->patient->allergies }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($visit->priority === 'emergency')
                                    <span class="badge bg-danger text-white fs-6">EMERGENCY</span>
                                @elseif($visit->priority === 'urgent')
                                    <span class="badge bg-warning text-dark">URGENT</span>
                                @else
                                    <span class="badge bg-light text-dark border">Standard</span>
                                @endif
                            </td>
                            <td style="max-width: 250px;">
                                <div class="text-dark fw-medium">{{ $visit->reason_for_visit }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Source: {{ ucfirst($visit->visit_type) }}</div>
                            </td>
                            <td>
                                @if($visit->status === 'waiting_triage')
                                    <span class="badge bg-warning-subtle text-warning">Waiting Assessment</span>
                                @elseif($visit->triageRecord)
                                    <span class="badge {{ $visit->triageRecord->badge_class }} mb-1 d-inline-block">
                                        {{ $visit->triageRecord->category_label }}
                                    </span>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        BP: <strong>{{ $visit->triageRecord->systolic_bp }}/{{ $visit->triageRecord->diastolic_bp }}</strong> | 
                                        HR: <strong>{{ $visit->triageRecord->pulse_rate }}</strong> | 
                                        SpO2: <strong>{{ $visit->triageRecord->oxygen_saturation }}%</strong> |
                                        T: <strong>{{ $visit->triageRecord->temperature }}°C</strong>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $visit->department->name ?? 'General Outpatient' }}</div>
                                @if($visit->doctor)
                                    <div class="text-muted">{{ $visit->doctor->name }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @php
                                    $canAssess = Auth::user() && Auth::user()->hasRole(['super_admin', 'nurse']);
                                @endphp
                                @if($canAssess)
                                    <a href="{{ route('admin.triage.assess', $visit->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-clipboard2-pulse"></i> {{ $visit->status === 'waiting_triage' ? 'Assess Vitals' : 'Edit Assessment' }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.patients.show', $visit->patient_id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-check fs-2 d-block mb-2 text-secondary"></i>
                                Triage queue is currently clear. No patients awaiting vital assessment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $visits->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
