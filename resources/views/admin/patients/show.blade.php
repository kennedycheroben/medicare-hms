@extends('layouts.admin')

@section('title', 'Patient Profile - ' . $patient->mrn)

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('admin.patients.index') }}" class="text-decoration-none text-muted small">&larr; Back to Patient Directory</a>
            <div class="d-flex align-items-center gap-2 mt-1">
                <h1 class="h3 fw-bold text-dark mb-0">{{ $patient->full_name }}</h1>
                <code class="fs-5 fw-bold text-primary px-2 py-1 bg-primary bg-opacity-10 rounded">{{ $patient->mrn }}</code>
                @if($patient->is_active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active Record</span>
                @else
                    <span class="badge bg-secondary">Archived</span>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-printer"></i> Print ID Card
            </button>
            <a href="{{ route('admin.intake.index', ['search_patient' => $patient->mrn]) }}" class="btn btn-success d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-box-arrow-in-right"></i> Check-in for Visit
            </a>
            @if(Auth::user() && Auth::user()->hasRole(['super_admin', 'receptionist']))
                <a href="{{ route('admin.patients.edit', $patient->id) }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-pencil"></i> Edit Profile
                </a>
            @endif
        </div>
    </div>

    <!-- Allergies Warning Banner if present -->
    @if($patient->allergies)
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 rounded-4 mb-4">
            <i class="bi bi-exclamation-octagon-fill fs-2"></i>
            <div>
                <div class="fw-bold">Known Patient Allergies & Adverse Reactions:</div>
                <div class="fs-6">{{ $patient->allergies }}</div>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Demographics Column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h2 class="h6 fw-bold text-dark mb-0">
                        <i class="bi bi-person-badge text-primary me-2"></i> Demographic Identity
                    </h2>
                </div>
                <div class="card-body p-4">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Medical Record Number:</span>
                            <code class="fw-bold text-dark">{{ $patient->mrn }}</code>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Age / Date of Birth:</span>
                            <span class="fw-semibold text-dark">{{ $patient->age ? $patient->age . ' years' : 'N/A' }} ({{ $patient->date_of_birth ? $patient->date_of_birth->format('Y-m-d') : 'N/A' }})</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Biological Sex:</span>
                            <span class="fw-semibold text-dark text-capitalize">{{ $patient->gender }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Blood Group:</span>
                            <span class="badge bg-danger-subtle text-danger fs-6">{{ $patient->blood_group ?: 'Not recorded' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">National ID / Passport:</span>
                            <span class="fw-semibold text-dark">{{ $patient->national_id ?: '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Primary Phone:</span>
                            <code class="text-dark">{{ $patient->phone }}</code>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Email:</span>
                            <span class="text-dark">{{ $patient->email ?: '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Residential City:</span>
                            <span class="text-dark">{{ $patient->address ? $patient->address . ', ' : '' }}{{ $patient->city }}</span>
                        </li>
                    </ul>

                    <div class="mt-4 pt-3 border-top">
                        <div class="fw-semibold small text-dark mb-2"><i class="bi bi-telephone-plus me-1 text-danger"></i> Emergency Next of Kin</div>
                        <div class="bg-light p-3 rounded-3 small">
                            <div class="fw-bold text-dark">{{ $patient->emergency_contact_name ?: 'Not recorded' }}</div>
                            @if($patient->emergency_contact_relationship)
                                <div class="text-muted">Relationship: {{ $patient->emergency_contact_relationship }}</div>
                            @endif
                            @if($patient->emergency_contact_phone)
                                <div class="mt-1"><code>{{ $patient->emergency_contact_phone }}</code></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clinical Encounter & Visit History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i> Clinical Encounters & Visits History
                    </h2>
                    <span class="badge bg-light text-secondary border">{{ $patient->visits->count() }} Encounters</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Visit Number</th>
                                <th>Check-In Date</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Triage Summary</th>
                                <th>Attending Clinic</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($patient->visits as $visit)
                                <tr>
                                    <td><code class="fw-bold">{{ $visit->visit_number }}</code></td>
                                    <td>{{ $visit->checked_in_at ? $visit->checked_in_at->format('Y-m-d H:i') : '—' }}</td>
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
                                        @if($visit->status === 'waiting_triage')
                                            <span class="badge bg-warning-subtle text-warning">Waiting Triage</span>
                                        @elseif($visit->status === 'triaged')
                                            <span class="badge bg-info-subtle text-info">Triaged</span>
                                        @elseif($visit->status === 'in_consultation')
                                            <span class="badge bg-primary-subtle text-primary">In Consultation</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">{{ ucfirst($visit->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($visit->triageRecord)
                                            <div class="fw-semibold">{{ $visit->triageRecord->category_label }}</div>
                                            <div class="text-muted">
                                                BP: {{ $visit->triageRecord->systolic_bp }}/{{ $visit->triageRecord->diastolic_bp }} | 
                                                Temp: {{ $visit->triageRecord->temperature }}°C | 
                                                SpO2: {{ $visit->triageRecord->oxygen_saturation }}%
                                            </div>
                                        @else
                                            <span class="text-muted">Awaiting Triage</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $visit->department->name ?? 'General' }}</div>
                                        @if($visit->doctor)
                                            <div class="text-muted">{{ $visit->doctor->name }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No hospital visits recorded for this patient yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scheduled Appointments -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-bold text-dark mb-0">
                        <i class="bi bi-calendar2-check text-primary me-2"></i> Linked Appointments
                    </h2>
                    <span class="badge bg-light text-secondary border">{{ $patient->appointments->count() }} Records</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Booking Reference</th>
                                <th>Appointment Date</th>
                                <th>Time Slot</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($patient->appointments as $apt)
                                <tr>
                                    <td><code class="fw-bold">{{ $apt->reference_code }}</code></td>
                                    <td>{{ $apt->appointment_date ? $apt->appointment_date->format('Y-m-d') : '—' }}</td>
                                    <td>{{ $apt->appointment_time }}</td>
                                    <td>{{ $apt->department->name ?? '—' }}</td>
                                    <td>{{ $apt->doctor->name ?? 'Attending Clinician' }}</td>
                                    <td>
                                        @if($apt->status === 'confirmed')
                                            <span class="badge bg-success-subtle text-success">Confirmed</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">{{ ucfirst($apt->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No scheduled appointments linked.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Clinical Consultations & EHR History -->
            @if(Auth::user()->hasRole(['doctor', 'hospital_director', 'super_admin']))
                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h2 class="h6 fw-bold text-dark mb-0">
                            <i class="bi bi-file-earmark-medical text-primary me-2"></i> Clinical Consultations & EHR Records
                        </h2>
                        <span class="badge bg-light text-secondary border">{{ $patient->consultations->count() }} Encounters</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Primary Diagnosis</th>
                                    <th>Clinician</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->consultations as $consult)
                                    <tr>
                                        <td>{{ $consult->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $consult->primary_diagnosis ?? 'In Progress' }}</span>
                                            @if($consult->diagnosis_type)
                                                <span class="badge bg-light text-secondary border ms-1">{{ ucfirst($consult->diagnosis_type) }}</span>
                                            @endif
                                            @if($consult->icd11_code)
                                                <span class="badge bg-light text-dark border ms-1 font-monospace">{{ $consult->icd11_code }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $consult->clinician->name ?? 'Attending Clinician' }}</td>
                                        <td>
                                            @if($consult->isSigned())
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-lock-fill"></i> Signed</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(Auth::user()->hasRole('doctor'))
                                                <a href="{{ route('admin.consultations.show', $consult->id) }}" class="btn btn-sm btn-outline-primary">
                                                    View Notes
                                                </a>
                                            @else
                                                <span class="text-muted small">Locked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No clinical consultation records documented yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Prescriptions History -->
                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h2 class="h6 fw-bold text-dark mb-0">
                            <i class="bi bi-capsule text-primary me-2"></i> Medication Prescriptions & Fulfillment
                        </h2>
                        <span class="badge bg-light text-secondary border">{{ $patient->prescriptions->count() }} Orders</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Prescription #</th>
                                    <th>Date</th>
                                    <th>Medications</th>
                                    <th>Prescriber</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->prescriptions as $rx)
                                    <tr>
                                        <td><code class="fw-bold">{{ $rx->prescription_number }}</code></td>
                                        <td>{{ $rx->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            @foreach($rx->items as $item)
                                                <div>&bull; {{ $item->medication_name }} ({{ $item->quantity_prescribed }} units)</div>
                                            @endforeach
                                        </td>
                                        <td>{{ $rx->prescriber->name ?? 'Doctor' }}</td>
                                        <td>
                                            @if($rx->status === 'dispensed')
                                                <span class="badge bg-success-subtle text-success">Dispensed</span>
                                            @elseif($rx->status === 'partially_dispensed')
                                                <span class="badge bg-info-subtle text-info">Partial</span>
                                            @elseif($rx->status === 'finalized')
                                                <span class="badge bg-primary-subtle text-primary">At Pharmacy</span>
                                            @elseif($rx->status === 'cancelled')
                                                <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.prescriptions.show', $rx->id) }}" class="btn btn-sm btn-outline-primary">
                                                View Order
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No prescriptions issued for this patient yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
