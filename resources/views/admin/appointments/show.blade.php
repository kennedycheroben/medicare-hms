@extends('layouts.admin')

@section('title', 'Appointment ' . $appointment->reference_code)

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> All Appointments
                </a>
                <span class="badge bg-secondary font-monospace">{{ $appointment->reference_code }}</span>
                @if($appointment->status === 'pending')
                    <span class="badge bg-warning-subtle text-warning">Pending Review</span>
                @elseif($appointment->status === 'confirmed')
                    <span class="badge bg-success-subtle text-success">Confirmed</span>
                @elseif($appointment->status === 'rescheduled')
                    <span class="badge bg-primary-subtle text-primary">Rescheduled</span>
                @elseif($appointment->status === 'completed')
                    <span class="badge bg-secondary-subtle text-secondary">Checked In / Completed</span>
                @elseif($appointment->status === 'cancelled')
                    <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">Appointment Review: {{ $appointment->patient_name }}</h1>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap gap-2">
            @if(Auth::user()->hasRole(['super_admin', 'receptionist']))
                @if($appointment->canBeConfirmed())
                    <button type="button" class="btn btn-success d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#confirmModal">
                        <i class="bi bi-check2-circle"></i> Confirm Slot
                    </button>
                @endif

                @if($appointment->canBeRescheduled())
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                        <i class="bi bi-calendar-event"></i> Reschedule
                    </button>
                @endif

                @if($appointment->canBeCancelled())
                    <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-x-circle"></i> Cancel Booking
                    </button>
                @endif

                @if($appointment->canBeCheckedIn() && $appointment->patient_id)
                    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#checkInModal">
                        <i class="bi bi-box-arrow-in-right"></i> Check In to Visit Queue
                    </button>
                @endif
            @endif
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

    <div class="row g-4">
        <!-- Left: Appointment & Clinical Details -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-card-checklist text-primary"></i> Booking Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Patient Legal Name</label>
                            <div class="fw-bold text-dark fs-6">{{ $appointment->patient_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Telephone Contact</label>
                            <div class="fw-semibold text-dark">{{ $appointment->patient_phone }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Email Address</label>
                            <div class="fw-semibold text-dark">{{ $appointment->patient_email ?: 'None provided' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Requested Department</label>
                            <div class="fw-bold text-primary">{{ $appointment->department->name ?? 'Not specified' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Assigned Specialist</label>
                            <div class="fw-semibold text-dark">
                                {{ $appointment->doctor ? 'Dr. ' . $appointment->doctor->name . ' (' . $appointment->doctor->specialty . ')' : 'Any Available Specialist' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Scheduled Date & Time Slot</label>
                            <div class="fw-bold text-dark">
                                <i class="bi bi-calendar3 me-1 text-primary"></i>
                                {{ $appointment->appointment_date ? $appointment->appointment_date->format('l, F j, Y') : 'Unset' }}
                                <span class="badge bg-light text-dark ms-2"><i class="bi bi-clock me-1"></i>{{ $appointment->appointment_time }}</span>
                            </div>
                        </div>

                        @if($appointment->symptoms)
                            <div class="col-12">
                                <label class="form-label text-muted small mb-1">Reported Reason for Consultation / Symptoms</label>
                                <div class="p-3 bg-light rounded-3 text-dark">{{ $appointment->symptoms }}</div>
                            </div>
                        @endif

                        @if($appointment->reschedule_reason)
                            <div class="col-12">
                                <label class="form-label text-warning small mb-1 fw-bold"><i class="bi bi-arrow-repeat me-1"></i> Rescheduling Reason</label>
                                <div class="p-3 bg-warning-subtle text-dark rounded-3">{{ $appointment->reschedule_reason }}</div>
                            </div>
                        @endif

                        @if($appointment->cancellation_reason)
                            <div class="col-12">
                                <label class="form-label text-danger small mb-1 fw-bold"><i class="bi bi-x-octagon me-1"></i> Cancellation Reason</label>
                                <div class="p-3 bg-danger-subtle text-danger-emphasis rounded-3">{{ $appointment->cancellation_reason }}</div>
                            </div>
                        @endif

                        @if($appointment->triage_notes)
                            <div class="col-12">
                                <label class="form-label text-muted small mb-1">Administrative / Clinical Triage Notes</label>
                                <div class="p-3 bg-light rounded-3 text-dark">{{ $appointment->triage_notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Linked Visit Card if checked in -->
            @if($appointment->visit)
                <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-success">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold text-success mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Active Clinical Visit Dispatched
                        </h5>
                        <span class="badge bg-success font-monospace">{{ $appointment->visit->visit_number }}</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Visit Status</span>
                                <span class="badge bg-info-subtle text-info fw-bold">{{ ucfirst(str_replace('_', ' ', $appointment->visit->status)) }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Clinical Priority</span>
                                <span class="badge bg-{{ $appointment->visit->priority === 'emergency' ? 'danger' : ($appointment->visit->priority === 'urgent' ? 'warning' : 'primary') }}">
                                    {{ strtoupper($appointment->visit->priority) }}
                                </span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Checked In At</span>
                                <span class="fw-semibold text-dark">{{ $appointment->visit->checked_in_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Audit Trail & Action History -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i> Action History & Audit Log
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Timestamp</th>
                                    <th>Action</th>
                                    <th>Performed By</th>
                                    <th class="pe-4">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td class="ps-4 small text-muted font-monospace">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td><span class="badge bg-light text-dark font-monospace">{{ $log->action }}</span></td>
                                        <td>{{ $log->performer->name ?? 'System / Anonymous' }}</td>
                                        <td class="pe-4 small text-muted">
                                            @if($log->details)
                                                @foreach($log->details as $k => $v)
                                                    <span class="d-block">{{ $k }}: <strong>{{ is_array($v) ? json_encode($v) : $v }}</strong></span>
                                                @endforeach
                                            @else
                                                &mdash;
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">No specific audit log entries recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Patient Master Index (PMI) Reconciliation -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-primary"></i> Patient Record (PMI)
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($appointment->patient)
                        <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-3">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div>
                                <div class="fw-bold">Reconciled with PMI</div>
                                <div class="small">Linked to registered hospital file.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small d-block">Medical Record Number (MRN)</span>
                            <span class="fw-bold font-monospace text-primary fs-6">{{ $appointment->patient->mrn }}</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small d-block">Registered Name</span>
                            <span class="fw-bold text-dark">{{ $appointment->patient->full_name }}</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small d-block">Phone / Emergency Contact</span>
                            <span class="text-dark">{{ $appointment->patient->phone }}</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small d-block">Blood Group / Allergies</span>
                            <span class="badge bg-danger-subtle text-danger">{{ $appointment->patient->blood_group ?: 'Unknown' }}</span>
                            <span class="text-muted small ms-1">{{ $appointment->patient->allergies ?: 'No allergies recorded' }}</span>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <a href="{{ route('admin.patients.show', $appointment->patient->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-person-vcard me-1"></i> View Full Patient Profile & EHR
                            </a>

                            @if($appointment->canBeCheckedIn() && ! $appointment->visit)
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#checkInModal">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Check In to Visit Queue
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning rounded-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span class="fw-bold">Unreconciled Online Booking</span>
                            </div>
                            <p class="extra-small mb-0">This appointment request is not yet linked to an official Medical Record Number (MRN).</p>
                        </div>

                        @if($potentialPatients->isNotEmpty())
                            <h6 class="fw-bold text-dark small mb-2">Matching Registered Patients:</h6>
                            <div class="list-group mb-3">
                                @foreach($potentialPatients as $p)
                                    <div class="list-group-item list-group-item-action p-2.5">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold small text-dark">{{ $p->full_name }}</span>
                                            <span class="badge bg-light text-dark font-monospace extra-small">{{ $p->mrn }}</span>
                                        </div>
                                        <div class="extra-small text-muted mb-2">
                                            <span><i class="bi bi-telephone"></i> {{ $p->phone }}</span>
                                            @if($p->email)
                                                <span>&bull; {{ $p->email }}</span>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('admin.appointments.reconcile', $appointment->id) }}">
                                            @csrf
                                            <input type="hidden" name="patient_id" value="{{ $p->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-success w-100 py-1 extra-small">
                                                <i class="bi bi-link-45deg me-1"></i> Link to this Patient
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted extra-small mb-3">No existing patients matched the submitted phone number or name.</p>
                        @endif

                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.patients.create', ['first_name' => explode(' ', $appointment->patient_name)[0] ?? '', 'last_name' => explode(' ', $appointment->patient_name)[1] ?? '', 'phone' => $appointment->patient_phone, 'email' => $appointment->patient_email]) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-person-plus me-1"></i> Register New Patient File
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirm Appointment -->
@if($appointment->canBeConfirmed())
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.appointments.confirm', $appointment->id) }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="confirmModalLabel">
                        <i class="bi bi-check2-circle text-success me-2"></i> Confirm Appointment Slot
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">Confirming officially reserves this appointment in the clinical schedule.</p>
                    <div class="mb-3">
                        <label for="dept_id" class="form-label fw-bold small">Department</label>
                        <select name="department_id" id="dept_id" class="form-select form-select-sm">
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $appointment->department_id === $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="doc_id" class="form-label fw-bold small">Assigned Specialist</label>
                        <select name="doctor_id" id="doc_id" class="form-select form-select-sm">
                            <option value="">-- Any Available Specialist --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" {{ $appointment->doctor_id === $doc->id ? 'selected' : '' }}>Dr. {{ $doc->name }} ({{ $doc->specialty }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="triage_notes" class="form-label fw-bold small">Clinical / Administrative Notes (Optional)</label>
                        <textarea name="triage_notes" id="triage_notes" class="form-control form-control-sm" rows="3" placeholder="e.g. Verified with patient via phone; patient requested Dr. Jane.">{{ $appointment->triage_notes }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal: Reschedule Appointment -->
@if($appointment->canBeRescheduled())
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.appointments.reschedule', $appointment->id) }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="rescheduleModalLabel">
                        <i class="bi bi-calendar-event text-primary me-2"></i> Reschedule Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="reschedule_date" class="form-label fw-bold small">New Appointment Date <span class="text-danger">*</span></label>
                        <input type="date" name="appointment_date" id="reschedule_date" class="form-control form-control-sm" min="{{ date('Y-m-d') }}" value="{{ $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="reschedule_time" class="form-label fw-bold small">New Time Slot <span class="text-danger">*</span></label>
                        <select name="appointment_time" id="reschedule_time" class="form-select form-select-sm" required>
                            <option value="09:00 AM - 10:00 AM">09:00 AM - 10:00 AM</option>
                            <option value="10:00 AM - 11:00 AM">10:00 AM - 11:00 AM</option>
                            <option value="11:00 AM - 12:00 PM">11:00 AM - 12:00 PM</option>
                            <option value="02:00 PM - 03:00 PM">02:00 PM - 03:00 PM</option>
                            <option value="03:00 PM - 04:00 PM">03:00 PM - 04:00 PM</option>
                            <option value="04:00 PM - 05:00 PM">04:00 PM - 05:00 PM</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reschedule_reason" class="form-label fw-bold small">Reason for Rescheduling <span class="text-danger">*</span></label>
                        <textarea name="reschedule_reason" id="reschedule_reason" class="form-control form-control-sm" rows="3" placeholder="e.g. Patient requested afternoon slot due to transport delay" required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Rescheduled Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal: Cancel Appointment -->
@if($appointment->canBeCancelled())
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.appointments.cancel', $appointment->id) }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-danger" id="cancelModalLabel">
                        <i class="bi bi-x-circle text-danger me-2"></i> Cancel Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">Cancelling will release the reserved clinical slot. A mandatory reason is required for administrative audit.</p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label fw-bold small">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" id="cancellation_reason" class="form-control form-control-sm" rows="3" placeholder="e.g. Patient called to cancel due to resolution of symptoms" required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal: Check In to Visit Queue -->
@if($appointment->canBeCheckedIn() && $appointment->patient_id)
<div class="modal fade" id="checkInModal" tabindex="-1" aria-labelledby="checkInModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.appointments.check-in', $appointment->id) }}">
                @csrf
                <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="checkInModalLabel">
                        <i class="bi bi-box-arrow-in-right text-primary me-2"></i> Check In Patient (Generate Visit)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info rounded-3 p-3 mb-3">
                        <div class="fw-bold small">{{ $appointment->patient->full_name }} ({{ $appointment->patient->mrn }})</div>
                        <div class="extra-small">Department: {{ $appointment->department->name ?? 'Outpatient' }} &bull; {{ $appointment->doctor ? 'Dr. ' . $appointment->doctor->name : 'Specialist Assigned' }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="checkin_priority" class="form-label fw-bold small">Initial Acuity Prioritization <span class="text-danger">*</span></label>
                        <select name="priority" id="checkin_priority" class="form-select form-select-sm" required>
                            <option value="standard" selected>Standard (Routine Consultation)</option>
                            <option value="urgent">Urgent (Acute Pain / High Fever)</option>
                            <option value="emergency">Emergency (Severe Distress / Trauma)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="checkin_reason" class="form-label fw-bold small">Reason for Visit / Chief Complaint</label>
                        <input type="text" name="reason_for_visit" id="checkin_reason" class="form-control form-control-sm" value="{{ $appointment->symptoms ?: 'Scheduled Outpatient Consultation' }}">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Check In & Dispatch to Triage</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
