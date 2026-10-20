@extends('layouts.admin')

@section('title', 'Front-Desk Patient Intake')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Front-Desk Patient Intake & Encounters</h1>
            <p class="text-muted small mb-0">Check in scheduled appointments, enroll walk-in patients, and dispatch to clinical triage queue.</p>
        </div>
        <div>
            <a href="{{ route('admin.patients.create') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-person-plus"></i> New Patient Registration
            </a>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Waiting for Triage</div>
                <div class="fs-4 fw-bold text-warning mt-1">{{ number_format($stats['waiting_triage']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Triaged / Awaiting Doctor</div>
                <div class="fs-4 fw-bold text-info mt-1">{{ number_format($stats['triaged']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">In Clinical Consultation</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($stats['in_consultation']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Total Check-Ins Today</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['total_today']) }}</div>
            </div>
        </div>
    </div>

    <!-- Intake Action Section (Tabs for Walk-in vs Appointment) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3 border-0" id="intakeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-semibold text-dark border-top-0 border-start-0 border-end-0" id="walkin-tab" data-bs-toggle="tab" data-bs-target="#walkin-pane" type="button" role="tab" aria-controls="walkin-pane" aria-selected="true">
                        <i class="bi bi-person-walking me-1 text-primary"></i> 1. Walk-In Patient Check-In
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-semibold text-dark border-top-0 border-start-0 border-end-0" id="appointment-tab" data-bs-toggle="tab" data-bs-target="#appointment-pane" type="button" role="tab" aria-controls="appointment-pane" aria-selected="false">
                        <i class="bi bi-calendar2-check me-1 text-success"></i> 2. Scheduled Appointment Check-In
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="intakeTabsContent">
                
                <!-- Tab 1: Walk-In Check-In -->
                <div class="tab-pane fade show active" id="walkin-pane" role="tabpanel" aria-labelledby="walkin-tab">
                    <!-- Step A: Search Patient -->
                    <form action="{{ route('admin.intake.index') }}" method="GET" class="mb-3">
                        <label class="form-label fw-semibold small text-dark">Search Patient by MRN, Name, or Phone</label>
                        <div class="input-group">
                            <input type="text" name="search_patient" class="form-control" placeholder="Enter MRN (e.g. MRN-2026-...) or Patient Name..." value="{{ request('search_patient') }}">
                            <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search"></i> Search Patient</button>
                        </div>
                    </form>

                    @if($patientSearchResults->isNotEmpty())
                        <div class="alert alert-light border rounded-3 p-3 mb-4">
                            <div class="fw-semibold small text-dark mb-2">Select matching registered patient:</div>
                            <div class="list-group">
                                @foreach($patientSearchResults as $match)
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                                        <div>
                                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">{{ $match->mrn }}</span>
                                            <strong>{{ $match->full_name }}</strong> ({{ $match->age }} yrs, {{ ucfirst($match->gender) }})
                                            <span class="text-muted ms-2"><i class="bi bi-telephone"></i> {{ $match->phone }}</span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary select-patient-btn" 
                                            data-id="{{ $match->id }}" 
                                            data-mrn="{{ $match->mrn }}" 
                                            data-name="{{ $match->full_name }}">
                                            Select for Intake &rarr;
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif(request('search_patient'))
                        <div class="alert alert-warning small mb-4">
                            <i class="bi bi-info-circle me-1"></i> No patient found matching "{{ request('search_patient') }}". 
                            <a href="{{ route('admin.patients.create') }}" class="fw-bold text-dark text-decoration-underline ms-1">Register new patient &rarr;</a>
                        </div>
                    @endif

                    <!-- Step B: Check-in Form -->
                    <form action="{{ route('admin.intake.walk-in') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="walkin_patient_id" class="form-label fw-semibold small text-dark">Selected Patient <span class="text-danger">*</span></label>
                                <input type="hidden" name="patient_id" id="walkin_patient_id" value="{{ old('patient_id', $patientSearchResults->first()->id ?? '') }}" required>
                                <input type="text" class="form-control bg-light" id="walkin_patient_display" readonly 
                                    value="{{ $patientSearchResults->first() ? $patientSearchResults->first()->mrn . ' - ' . $patientSearchResults->first()->full_name : (old('patient_id') ? 'Selected ID #' . old('patient_id') : 'Please search and select a patient above') }}" 
                                    placeholder="Search above to populate patient">
                                @error('patient_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-semibold small text-dark">Intake Priority <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                    <option value="standard" {{ old('priority') === 'standard' ? 'selected' : '' }}>Standard (Routine Queue)</option>
                                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent (Elevated Priority)</option>
                                    <option value="emergency" {{ old('priority') === 'emergency' ? 'selected' : '' }}>Emergency (Immediate Resuscitation Required)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-semibold small text-dark">Destination Department <span class="text-danger">*</span></label>
                                <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                    <option value="">Select Clinic...</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="doctor_id" class="form-label fw-semibold small text-dark">Attending Clinician (Optional)</label>
                                <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id">
                                    <option value="">Duty / On-Call Physician</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}" {{ old('doctor_id') == $doc->id ? 'selected' : '' }}>{{ $doc->name }} ({{ $doc->specialty }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="reason_for_visit" class="form-label fw-semibold small text-dark">Presenting Reason for Visit / Chief Complaint <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('reason_for_visit') is-invalid @enderror" id="reason_for_visit" name="reason_for_visit" value="{{ old('reason_for_visit') }}" required placeholder="e.g. Acute severe headache, fever for 3 days, follow-up consult">
                                @error('reason_for_visit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Check In Patient & Send to Triage
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab 2: Appointment Check-In -->
                <div class="tab-pane fade" id="appointment-pane" role="tabpanel" aria-labelledby="appointment-tab">
                    <form action="{{ route('admin.intake.index') }}" method="GET" class="mb-3">
                        <label class="form-label fw-semibold small text-dark">Lookup Scheduled Appointment Reference Code</label>
                        <div class="input-group">
                            <input type="text" name="search_appointment" class="form-control" placeholder="e.g. MC-20261020-XXXXX" value="{{ request('search_appointment') }}">
                            <button type="submit" class="btn btn-success px-3"><i class="bi bi-search"></i> Verify Booking</button>
                        </div>
                    </form>

                    @if($appointmentSearchResult)
                        <div class="card border-success border-opacity-50 bg-success bg-opacity-10 rounded-3 mb-4 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="badge bg-success">Valid Appointment</span>
                                    <code class="fs-6 fw-bold text-dark ms-2">{{ $appointmentSearchResult->reference_code }}</code>
                                </div>
                                <span class="badge bg-light text-dark border">{{ $appointmentSearchResult->appointment_date ? $appointmentSearchResult->appointment_date->format('Y-m-d') : '' }} | {{ $appointmentSearchResult->appointment_time }}</span>
                            </div>
                            <div class="row g-2 small text-dark">
                                <div class="col-md-4"><strong>Patient from Form:</strong> {{ $appointmentSearchResult->patient_name }}</div>
                                <div class="col-md-4"><strong>Phone:</strong> {{ $appointmentSearchResult->patient_phone }}</div>
                                <div class="col-md-4"><strong>Email:</strong> {{ $appointmentSearchResult->patient_email }}</div>
                                <div class="col-md-6"><strong>Department:</strong> {{ $appointmentSearchResult->department->name ?? 'General' }}</div>
                                <div class="col-md-6"><strong>Doctor:</strong> {{ $appointmentSearchResult->doctor->name ?? 'Assigned' }}</div>
                            </div>
                        </div>

                        <!-- Check in confirmed appointment -->
                        <form action="{{ route('admin.intake.check-in') }}" method="POST">
                            @csrf
                            <input type="hidden" name="appointment_id" value="{{ $appointmentSearchResult->id }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-dark">Link to Registered Patient Record <span class="text-danger">*</span></label>
                                    @php
                                        // Attempt auto-match by phone
                                        $matchedPatient = $appointmentSearchResult->patient ?? \App\Models\Patient::where('phone', $appointmentSearchResult->patient_phone)->first();
                                    @endphp
                                    <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
                                        @if($matchedPatient)
                                            <option value="{{ $matchedPatient->id }}" selected>
                                                [Auto-Matched] {{ $matchedPatient->mrn }} - {{ $matchedPatient->full_name }} ({{ $matchedPatient->phone }})
                                            </option>
                                        @else
                                            <option value="">Select or search patient registry...</option>
                                            @foreach(\App\Models\Patient::latest('id')->limit(20)->get() as $p)
                                                <option value="{{ $p->id }}">{{ $p->mrn }} - {{ $p->full_name }} ({{ $p->phone }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if(!$matchedPatient)
                                        <div class="form-text small text-muted">
                                            Patient not registered yet? <a href="{{ route('admin.patients.create', ['first_name' => $appointmentSearchResult->patient_name, 'phone' => $appointmentSearchResult->patient_phone, 'email' => $appointmentSearchResult->patient_email]) }}" target="_blank">Register patient first &rarr;</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-dark">Intake Priority <span class="text-danger">*</span></label>
                                    <select class="form-select" name="priority" required>
                                        <option value="standard" selected>Standard (Scheduled Slot)</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-dark">Reason / Symptoms</label>
                                    <input type="text" class="form-control" name="reason_for_visit" value="{{ $appointmentSearchResult->symptoms ?: 'Consultation for ' . ($appointmentSearchResult->department->name ?? 'Specialist') }}">
                                </div>

                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success px-4 py-2 fw-semibold">
                                        <i class="bi bi-check2-circle me-1"></i> Reconcile & Check In Appointment
                                    </button>
                                </div>
                            </div>
                        </form>
                    @elseif(request('search_appointment'))
                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-circle me-1"></i> No booking found matching "{{ request('search_appointment') }}".
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <!-- Today's Intake Queue -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h2 class="h5 fw-bold text-dark mb-0">
                <i class="bi bi-list-check text-primary me-2"></i> Today's Patient Check-Ins & Encounters Queue
            </h2>
            <span class="badge bg-light text-secondary border">{{ $todayVisits->total() }} Visits Registered</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Visit #</th>
                        <th>Arrival</th>
                        <th>Patient Identity</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Clinic & Doctor</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayVisits as $visit)
                        <tr class="{{ $visit->priority === 'emergency' ? 'table-danger' : '' }}">
                            <td><code class="fw-bold">{{ $visit->visit_number }}</code></td>
                            <td>{{ $visit->checked_in_at ? $visit->checked_in_at->format('H:i') : '—' }}</td>
                            <td>
                                <a href="{{ route('admin.patients.show', $visit->patient_id) }}" class="fw-semibold text-dark text-decoration-none">
                                    {{ $visit->patient->full_name }}
                                </a>
                                <div><code class="text-muted">{{ $visit->patient->mrn }}</code> | {{ $visit->patient->age }} yrs, {{ ucfirst($visit->patient->gender) }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ ucfirst($visit->visit_type) }}</span></td>
                            <td>
                                @if($visit->priority === 'emergency')
                                    <span class="badge bg-danger text-white">Emergency</span>
                                @elseif($visit->priority === 'urgent')
                                    <span class="badge bg-warning text-dark">Urgent</span>
                                @else
                                    <span class="badge bg-light text-dark border">Standard</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $visit->department->name ?? 'General Clinic' }}</div>
                                @if($visit->doctor)
                                    <div class="text-muted">{{ $visit->doctor->name }}</div>
                                @endif
                            </td>
                            <td>
                                @if($visit->status === 'waiting_triage')
                                    <span class="badge bg-warning-subtle text-warning">Waiting Triage</span>
                                @elseif($visit->status === 'triaged')
                                    <span class="badge bg-info-subtle text-info">Triaged</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">{{ ucfirst($visit->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($visit->status === 'waiting_triage' && Auth::user()->hasRole(['super_admin', 'nurse']))
                                    <a href="{{ route('admin.triage.assess', $visit->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-heart-pulse"></i> Triage
                                    </a>
                                @else
                                    <a href="{{ route('admin.patients.show', $visit->patient_id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No patients checked in today yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($todayVisits->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $todayVisits->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectButtons = document.querySelectorAll('.select-patient-btn');
        const hiddenInput = document.getElementById('walkin_patient_id');
        const displayInput = document.getElementById('walkin_patient_display');

        selectButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const mrn = this.getAttribute('data-mrn');
                const name = this.getAttribute('data-name');

                if (hiddenInput && displayInput) {
                    hiddenInput.value = id;
                    displayInput.value = `${mrn} - ${name}`;
                }
            });
        });
    });
</script>
@endpush
@endsection
