@extends('layouts.admin')

@section('title', 'Clinical Consultation - ' . $consultation->patient->full_name)

@section('content')
<div class="container-fluid p-0">
    <!-- Top Action Breadcrumb -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.consultations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Queue
            </a>
            <div>
                <h1 class="h4 fw-bold text-dark mb-0">Clinical Consultation Workspace</h1>
                <span class="text-muted small">Visit #{{ $consultation->visit->visit_number ?? 'N/A' }} &bull; Encounter Date: {{ $consultation->created_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($consultation->isSigned())
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                    <i class="bi bi-lock-fill me-1"></i> Finalized & Signed
                </span>
            @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 fs-6">
                    <i class="bi bi-pencil-fill me-1"></i> Draft Record
                </span>
            @endif
            <a href="{{ route('admin.patients.show', $consultation->patient_id) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-vcard"></i> Full Patient Profile
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
            <strong>Please resolve the following before proceeding:</strong>
            <ul class="mb-0 ps-3 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Patient Demographics & Clinical Alert Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 52px; height: 52px; min-width: 52px;">
                            {{ strtoupper(substr($consultation->patient->first_name, 0, 1) . substr($consultation->patient->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-1">{{ $consultation->patient->full_name }}</h4>
                            <div class="text-muted small d-flex flex-wrap gap-2 align-items-center">
                                <span>MRN: <strong class="font-monospace text-dark">{{ $consultation->patient->mrn }}</strong></span>
                                &bull;
                                <span>DOB: {{ $consultation->patient->date_of_birth ? $consultation->patient->date_of_birth->format('M d, Y') : 'N/A' }} ({{ $consultation->patient->age ?? 'N/A' }} yrs)</span>
                                &bull;
                                <span>Gender: <strong>{{ ucfirst($consultation->patient->gender) }}</strong></span>
                                &bull;
                                <span>Blood Group: <strong class="text-danger">{{ $consultation->patient->blood_group ?? 'Unknown' }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    @if(!empty($consultation->patient->allergies))
                        <div class="alert alert-danger mb-0 p-2 border-danger-subtle d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                            <div class="small">
                                <strong class="d-block text-danger">Documented Patient Allergies:</strong>
                                <span class="fw-medium text-dark">{{ $consultation->patient->allergies }}</span>
                                <div class="text-muted extra-small mt-1" style="font-size: 0.75rem;">
                                    * Notice: Visual indicator of documented allergies. Always perform thorough clinical verification before prescribing.
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light mb-0 p-2 border d-flex align-items-center gap-2 text-muted small">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <span>No adverse drug or environmental allergies documented in patient file.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Nursing Triage Vitals & Visit Context -->
        <div class="col-lg-4">
            <!-- Triage Vitals Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-activity text-danger"></i> Triage Vital Signs
                    </h5>
                    @if($consultation->visit->triageRecord)
                        <span class="badge bg-light text-secondary border small">
                            {{ $consultation->visit->triageRecord->triaged_at ? $consultation->visit->triageRecord->triaged_at->format('H:i') : '' }}
                        </span>
                    @endif
                </div>
                <div class="card-body p-3">
                    @if($consultation->visit->triageRecord)
                        @php $triage = $consultation->visit->triageRecord; @endphp
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Blood Pressure</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->blood_pressure ?? '—' }} <small class="text-muted font-normal">mmHg</small></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Pulse / Heart Rate</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->pulse_rate ?? '—' }} <small class="text-muted">bpm</small></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Temperature</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->temperature ?? '—' }} <small class="text-muted">&deg;C</small></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Oxygen Sat (SpO2)</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->oxygen_saturation ? $triage->oxygen_saturation.'%' : '—' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Respiratory Rate</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->respiratory_rate ?? '—' }} <small class="text-muted">/min</small></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Pain Score (0-10)</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->pain_score !== null ? $triage->pain_score.'/10' : '—' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Weight</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->weight_kg ?? '—' }} <small class="text-muted">kg</small></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-muted extra-small">Height / BMI</div>
                                    <div class="fw-bold fs-6 text-dark">{{ $triage->height_cm ? $triage->height_cm.' cm' : '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="text-muted small fw-bold">Nurse Chief Complaint:</label>
                            <p class="small text-dark mb-0 bg-light p-2 rounded-2 border">
                                {{ $triage->presenting_complaint ?? 'None recorded at triage' }}
                            </p>
                        </div>

                        @if(!empty($triage->nursing_notes))
                            <div class="mb-2">
                                <label class="text-muted small fw-bold">Nursing Observations:</label>
                                <p class="small text-muted mb-0 bg-light p-2 rounded-2 border">
                                    {{ $triage->nursing_notes }}
                                </p>
                            </div>
                        @endif

                        <div class="text-muted extra-small mt-2 pt-2 border-top">
                            Recorded by: {{ $triage->nurse->name ?? 'Triage Nurse' }} &bull; {{ $triage->created_at->format('M d, Y H:i') }}
                        </div>
                    @else
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-clipboard-x fs-2 d-block mb-1 text-secondary"></i>
                            No nursing triage recorded for this visit.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Attending Clinician & Sign-off Details -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-primary"></i> Clinician Information
                    </h5>
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Documenting Clinician:</span>
                        <span class="fw-semibold text-dark">{{ $consultation->clinician->name ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Attending Doctor Profile:</span>
                        <span class="fw-semibold text-dark">{{ $consultation->doctor->name ?? 'Not assigned' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Department:</span>
                        <span class="fw-semibold text-dark">{{ $consultation->visit->department->name ?? 'Outpatient' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Record Status:</span>
                        @if($consultation->isSigned())
                            <span class="badge bg-success"><i class="bi bi-lock-fill"></i> Signed</span>
                        @else
                            <span class="badge bg-warning text-dark">Draft Note</span>
                        @endif
                    </div>
                    @if($consultation->isSigned())
                        <div class="alert alert-success mt-3 mb-0 p-2 small">
                            <i class="bi bi-check2-all me-1"></i> Finalized & Signed on {{ $consultation->signed_at->format('M d, Y H:i') }} by {{ $consultation->signedByUser->name ?? 'Clinician' }}.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Clinical Note Form (Draft) OR Locked Record (Signed) + Prescriptions -->
        <div class="col-lg-8">
            @if($consultation->isDraft())
                <!-- Draft Clinical Documentation Form -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-medical text-primary"></i> Clinical Assessment & Plan
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form id="consultationForm" method="POST" action="{{ route('admin.consultations.update', $consultation->id) }}">
                            @csrf
                            <input type="hidden" name="_method" id="consultationFormMethod" value="PUT">

                            <div class="mb-3">
                                <label for="chief_complaint" class="form-label fw-bold text-dark">
                                    Chief Complaint <span class="text-danger">*</span>
                                </label>
                                <textarea name="chief_complaint" id="chief_complaint" class="form-control" rows="2" placeholder="Primary presenting complaint described in patient's words...">{{ old('chief_complaint', $consultation->chief_complaint) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="history_present_illness" class="form-label fw-bold text-dark">
                                    History of Present Illness (HPI) <span class="text-danger">*</span>
                                </label>
                                <textarea name="history_present_illness" id="history_present_illness" class="form-control" rows="3" placeholder="Chronological narrative of onset, duration, severity, characteristics, and modifying factors...">{{ old('history_present_illness', $consultation->history_present_illness) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="medical_history" class="form-label fw-bold text-dark">
                                    Past Medical / Surgical / Family History
                                </label>
                                <textarea name="medical_history" id="medical_history" class="form-control" rows="2" placeholder="Chronic conditions, previous admissions, surgical history, family history...">{{ old('medical_history', $consultation->medical_history) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="examination_findings" class="form-label fw-bold text-dark">
                                    Physical Examination Findings <span class="text-danger">*</span>
                                </label>
                                <textarea name="examination_findings" id="examination_findings" class="form-control" rows="3" placeholder="General appearance, systemic examination findings (CVS, RS, PA, CNS)...">{{ old('examination_findings', $consultation->examination_findings) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="assessment" class="form-label fw-bold text-dark">
                                    Clinical Assessment & Impression <span class="text-danger">*</span>
                                </label>
                                <textarea name="assessment" id="assessment" class="form-control" rows="2" placeholder="Clinician's synthesis, clinical reasoning, and differential diagnostic evaluation...">{{ old('assessment', $consultation->assessment) }}</textarea>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="diagnosis_type" class="form-label fw-bold text-dark">
                                        Diagnosis Type <span class="text-danger">*</span>
                                    </label>
                                    <select name="diagnosis_type" id="diagnosis_type" class="form-select">
                                        <option value="provisional" {{ old('diagnosis_type', $consultation->diagnosis_type) === 'provisional' ? 'selected' : '' }}>Provisional</option>
                                        <option value="confirmed" {{ old('diagnosis_type', $consultation->diagnosis_type) === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                        <option value="differential" {{ old('diagnosis_type', $consultation->diagnosis_type) === 'differential' ? 'selected' : '' }}>Differential</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="primary_diagnosis" class="form-label fw-bold text-dark">
                                        Primary Diagnosis <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="primary_diagnosis" id="primary_diagnosis" class="form-control" placeholder="e.g. Acute Bacterial Tonsillitis" value="{{ old('primary_diagnosis', $consultation->primary_diagnosis) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="icd11_code" class="form-label fw-bold text-dark">
                                        ICD-11 Code <small class="text-muted">(Optional)</small>
                                    </label>
                                    <input type="text" name="icd11_code" id="icd11_code" class="form-control" placeholder="e.g. CA03" value="{{ old('icd11_code', $consultation->icd11_code) }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="secondary_diagnosis" class="form-label fw-bold text-dark">
                                    Secondary / Comorbid Diagnoses
                                </label>
                                <input type="text" name="secondary_diagnosis" id="secondary_diagnosis" class="form-control" placeholder="e.g. Essential Hypertension (well controlled)" value="{{ old('secondary_diagnosis', $consultation->secondary_diagnosis) }}">
                            </div>

                            <div class="mb-3">
                                <label for="treatment_plan" class="form-label fw-bold text-dark">
                                    Treatment Plan & Clinical Management <span class="text-danger">*</span>
                                </label>
                                <textarea name="treatment_plan" id="treatment_plan" class="form-control" rows="3" placeholder="Therapeutic plan, non-pharmacological advice, nursing orders...">{{ old('treatment_plan', $consultation->treatment_plan) }}</textarea>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label for="follow_up_instructions" class="form-label fw-bold text-dark">
                                        Follow-up / Discharge Instructions
                                    </label>
                                    <input type="text" name="follow_up_instructions" id="follow_up_instructions" class="form-control" placeholder="e.g. Return if fever persists over 48 hours or severe headache develops" value="{{ old('follow_up_instructions', $consultation->follow_up_instructions) }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="follow_up_date" class="form-label fw-bold text-dark">
                                        Follow-up Review Date
                                    </label>
                                    <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" min="{{ date('Y-m-d') }}" value="{{ old('follow_up_date', $consultation->follow_up_date ? $consultation->follow_up_date->format('Y-m-d') : '') }}">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-3 border-top">
                                <button type="submit" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-save"></i> Save Draft Notes
                                </button>
                                <button type="button" class="btn btn-success d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#signModal">
                                    <i class="bi bi-pen-fill"></i> Finalize & Sign Off
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sign Consultation Modal -->
                <div class="modal fade" id="signModal" tabindex="-1" aria-labelledby="signModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-bottom">
                                <h5 class="modal-title fw-bold text-dark" id="signModalLabel">
                                    <i class="bi bi-lock-fill text-success me-1"></i> Finalize & Sign Clinical Encounter
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning small mb-3">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    <strong>Important Legal & Clinical Notice:</strong> Once finalized and signed, this clinical record becomes a permanent medical legal document. It cannot be edited directly; corrections or updates will require an auditable clinical addendum.
                                </div>
                                <p class="text-muted small mb-2">
                                    Signing this record certifies that you have reviewed the clinical findings, documented diagnoses, and treatment plan. The visit status will be marked as completed.
                                </p>
                            </div>
                            <div class="modal-footer border-top">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success" id="confirmSignButton" onclick="const f = document.getElementById('consultationForm'); const m = document.getElementById('consultationFormMethod'); if (m) { m.remove(); } f.action='{{ route('admin.consultations.sign', $consultation->id) }}'; f.submit();">
                                    Confirm & Sign Off
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Signed Permanent Clinical Record View -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock-fill"></i> Finalized Medical Consultation Record
                        </h5>
                        <span class="badge bg-white text-success fw-bold font-monospace">SIGNED</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-success border-0 bg-success-subtle p-3 rounded-3 mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                <div>
                                    <div class="fw-bold text-success">Clinical Encounter Officially Finalized</div>
                                    <div class="small text-muted">
                                        Digitally signed by <strong>{{ $consultation->signedByUser->name ?? $consultation->clinician->name }}</strong> on {{ $consultation->signed_at->format('F d, Y \a\t H:i:s') }}.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold text-dark mb-1">Chief Complaint:</h6>
                            <p class="text-dark bg-light p-2 rounded-2 border mb-0">{{ $consultation->chief_complaint }}</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold text-dark mb-1">History of Present Illness:</h6>
                            <p class="text-dark bg-light p-2 rounded-2 border mb-0" style="white-space: pre-line;">{{ $consultation->history_present_illness }}</p>
                        </div>

                        @if($consultation->medical_history)
                            <div class="mb-3">
                                <h6 class="fw-bold text-dark mb-1">Past Medical History:</h6>
                                <p class="text-dark bg-light p-2 rounded-2 border mb-0" style="white-space: pre-line;">{{ $consultation->medical_history }}</p>
                            </div>
                        @endif

                        <div class="mb-3">
                            <h6 class="fw-bold text-dark mb-1">Physical Examination Findings:</h6>
                            <p class="text-dark bg-light p-2 rounded-2 border mb-0" style="white-space: pre-line;">{{ $consultation->examination_findings }}</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold text-dark mb-1">Clinical Assessment:</h6>
                            <p class="text-dark bg-light p-2 rounded-2 border mb-0" style="white-space: pre-line;">{{ $consultation->assessment }}</p>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-dark mb-1">Primary Diagnosis:</h6>
                                <div class="p-2 bg-light rounded-2 border">
                                    <span class="fw-bold text-primary">{{ $consultation->primary_diagnosis }}</span>
                                    <span class="badge bg-secondary ms-1">{{ ucfirst($consultation->diagnosis_type) }}</span>
                                    @if($consultation->icd11_code)
                                        <span class="badge bg-light text-dark border ms-1 font-monospace">ICD-11: {{ $consultation->icd11_code }}</span>
                                    @endif
                                </div>
                            </div>
                            @if($consultation->secondary_diagnosis)
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-dark mb-1">Secondary Diagnosis:</h6>
                                    <div class="p-2 bg-light rounded-2 border text-dark">{{ $consultation->secondary_diagnosis }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold text-dark mb-1">Treatment Plan:</h6>
                            <p class="text-dark bg-light p-2 rounded-2 border mb-0" style="white-space: pre-line;">{{ $consultation->treatment_plan }}</p>
                        </div>

                        @if($consultation->follow_up_instructions || $consultation->follow_up_date)
                            <div class="row g-3 mb-3">
                                @if($consultation->follow_up_instructions)
                                    <div class="col-md-8">
                                        <h6 class="fw-bold text-dark mb-1">Follow-up Instructions:</h6>
                                        <div class="p-2 bg-light rounded-2 border text-dark">{{ $consultation->follow_up_instructions }}</div>
                                    </div>
                                @endif
                                @if($consultation->follow_up_date)
                                    <div class="col-md-4">
                                        <h6 class="fw-bold text-dark mb-1">Review Date:</h6>
                                        <div class="p-2 bg-light rounded-2 border text-dark fw-bold">
                                            <i class="bi bi-calendar-event me-1 text-primary"></i> {{ $consultation->follow_up_date->format('M d, Y') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Addenda Section -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-journal-plus text-primary"></i> Post-Signature Clinical Addenda
                                    <span class="badge bg-secondary rounded-pill">{{ $consultation->addenda->count() }}</span>
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" data-bs-toggle="collapse" data-bs-target="#addendaFormCollapse">
                                    <i class="bi bi-plus-lg"></i> Append Addendum
                                </button>
                            </div>

                            <!-- Collapse Addendum Form -->
                            <div class="collapse mb-4" id="addendaFormCollapse">
                                <div class="card border rounded-3 bg-light p-3">
                                    <form method="POST" action="{{ route('admin.consultations.addenda.store', $consultation->id) }}">
                                        @csrf
                                        <h6 class="fw-bold text-dark mb-2">Append New Clinical Addendum</h6>
                                        <div class="mb-3">
                                            <label for="reason_for_amendment" class="form-label fw-bold small">Reason for Addendum / Amendment <span class="text-danger">*</span></label>
                                            <input type="text" name="reason_for_amendment" id="reason_for_amendment" class="form-control form-control-sm" placeholder="e.g. Additional laboratory results reviewed, clarification of dose instructions" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="addendum_notes" class="form-label fw-bold small">Addendum Clinical Notes <span class="text-danger">*</span></label>
                                            <textarea name="addendum_notes" id="addendum_notes" rows="3" class="form-control form-control-sm" placeholder="Detailed notes..." required></textarea>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="collapse" data-bs-target="#addendaFormCollapse">Cancel</button>
                                            <button type="submit" class="btn btn-sm btn-primary">Save Addendum</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @forelse($consultation->addenda as $addendum)
                                <div class="border rounded-3 p-3 bg-light mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark small"><i class="bi bi-pen me-1"></i> {{ $addendum->author->name ?? 'Clinician' }}</span>
                                        <span class="text-muted extra-small">{{ $addendum->created_at->format('M d, Y H:i:s') }}</span>
                                    </div>
                                    <div class="text-muted small mb-2 fst-italic">Reason: {{ $addendum->reason_for_amendment }}</div>
                                    <p class="text-dark small mb-0" style="white-space: pre-line;">{{ $addendum->addendum_notes }}</p>
                                </div>
                            @empty
                                <div class="text-muted small py-2">No clinical addenda recorded for this encounter.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <!-- Prescriptions Section for this Encounter -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-capsule text-primary"></i> Prescriptions & Medication Orders
                        <span class="badge bg-primary rounded-pill">{{ $consultation->prescriptions->count() }}</span>
                    </h5>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#newPrescriptionModal">
                        <i class="bi bi-plus-lg"></i> Issue Prescription
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">RX Number</th>
                                <th>Prescribed Items</th>
                                <th>Date Issued</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consultation->prescriptions as $rx)
                                <tr>
                                    <td class="ps-4">
                                        <span class="font-monospace fw-bold text-dark">{{ $rx->prescription_number }}</span>
                                        <div class="text-muted extra-small">By: {{ $rx->prescriber->name ?? 'Prescriber' }}</div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @foreach($rx->items as $item)
                                                <div>&bull; <strong class="text-dark">{{ $item->medication_name }}</strong> ({{ $item->quantity_prescribed }} units) &mdash; <span class="text-muted">{{ $item->dosage }}, {{ $item->frequency }}</span></div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="small">{{ $rx->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        @if($rx->status === 'dispensed')
                                            <span class="badge bg-success"><i class="bi bi-check-all"></i> Fully Dispensed</span>
                                        @elseif($rx->status === 'partially_dispensed')
                                            <span class="badge bg-info text-dark">Partially Dispensed</span>
                                        @elseif($rx->status === 'finalized')
                                            <span class="badge bg-primary">Queued at Pharmacy</span>
                                        @elseif($rx->status === 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('admin.prescriptions.show', $rx->id) }}" class="btn btn-sm btn-outline-primary">
                                            View RX
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">
                                        <i class="bi bi-prescription2 fs-2 d-block mb-1 text-secondary"></i>
                                        No prescriptions issued during this consultation yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Prescription Creation -->
<div class="modal fade" id="newPrescriptionModal" tabindex="-1" aria-labelledby="newPrescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.prescriptions.store', $consultation->id) }}" id="prescriptionCreateForm">
                @csrf
                <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark" id="newPrescriptionModalLabel">
                        <i class="bi bi-prescription2 text-primary me-2"></i> Issue Electronic Prescription
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @if(!empty($consultation->patient->allergies))
                        <div class="alert alert-danger py-2 px-3 small mb-3">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i>
                            <strong>Allergy Alert:</strong> Patient has documented allergies: <span class="fw-bold">{{ $consultation->patient->allergies }}</span>. Confirm safety before prescribing.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="clinical_notes" class="form-label fw-bold small">Prescription Clinical Notes / Instructions</label>
                        <input type="text" name="clinical_notes" id="clinical_notes" class="form-control form-control-sm" placeholder="e.g. Post-tonsillectomy pain control, take with meals">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0 small">Medication Line Items <span class="text-danger">*</span></h6>
                        <button type="button" class="btn btn-xs btn-outline-primary btn-sm" id="addItemRowBtn">
                            <i class="bi bi-plus-circle"></i> Add Medication
                        </button>
                    </div>

                    <div id="prescriptionItemsContainer">
                        <!-- Initial Line Item Row -->
                        <div class="prescription-item-row p-3 border rounded-3 bg-light mb-3" data-row-index="0">
                            <div class="row g-2 mb-2">
                                <div class="col-md-7">
                                    <label class="form-label extra-small text-muted fw-bold">Select Medicine</label>
                                    <select name="items[0][medicine_id]" class="form-select form-select-sm" required>
                                        <option value="">-- Choose Catalogue Medicine --</option>
                                        @foreach($medicines as $med)
                                            <option value="{{ $med->id }}">{{ $med->brand_name }} ({{ $med->generic_name }}) - {{ $med->strength }} [{{ ucfirst($med->dosage_form) }}]</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label extra-small text-muted fw-bold">Route</label>
                                    <select name="items[0][route]" class="form-select form-select-sm" required>
                                        <option value="oral" selected>Oral</option>
                                        <option value="intravenous">Intravenous (IV)</option>
                                        <option value="intramuscular">Intramuscular (IM)</option>
                                        <option value="topical">Topical</option>
                                        <option value="inhalation">Inhalation</option>
                                        <option value="ophthalmic">Ophthalmic</option>
                                        <option value="rectal">Rectal</option>
                                        <option value="sublingual">Sublingual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label extra-small text-muted fw-bold">Dosage</label>
                                    <input type="text" name="items[0][dosage]" class="form-control form-control-sm" placeholder="e.g. 500mg or 10ml" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label extra-small text-muted fw-bold">Frequency</label>
                                    <input type="text" name="items[0][frequency]" class="form-control form-control-sm" placeholder="e.g. TDS (3 times daily)" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label extra-small text-muted fw-bold">Duration</label>
                                    <input type="text" name="items[0][duration]" class="form-control form-control-sm" placeholder="e.g. 5 days" required>
                                </div>
                            </div>
                            <div class="row g-2 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label extra-small text-muted fw-bold">Quantity Prescribed</label>
                                    <input type="number" name="items[0][quantity_prescribed]" class="form-control form-control-sm" min="1" max="10000" placeholder="Total units" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label extra-small text-muted fw-bold">Specific Instructions</label>
                                    <input type="text" name="items[0][instructions]" class="form-control form-control-sm" placeholder="e.g. Take after breakfast and dinner">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action_type" value="draft" class="btn btn-outline-primary">
                            Save as Draft
                        </button>
                        <button type="submit" name="action_type" value="finalize" class="btn btn-success">
                            <i class="bi bi-send-fill me-1"></i> Finalize & Transmit to Pharmacy
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    const medicinesOptions = `@foreach($medicines as $med)<option value="{{ $med->id }}">{{ addslashes($med->brand_name) }} ({{ addslashes($med->generic_name) }}) - {{ $med->strength }} [{{ ucfirst($med->dosage_form) }}]</option>@endforeach`;

    document.getElementById('addItemRowBtn')?.addEventListener('click', function() {
        const container = document.getElementById('prescriptionItemsContainer');
        const row = document.createElement('div');
        row.className = 'prescription-item-row p-3 border rounded-3 bg-light mb-3 position-relative';
        row.dataset.rowIndex = rowIndex;
        row.innerHTML = `
            <button type="button" class="btn btn-sm btn-link text-danger position-absolute top-0 end-0 m-2 p-0 remove-row-btn" title="Remove Item">
                <i class="bi bi-x-circle-fill fs-5"></i>
            </button>
            <div class="row g-2 mb-2 pe-4">
                <div class="col-md-7">
                    <label class="form-label extra-small text-muted fw-bold">Select Medicine</label>
                    <select name="items[${rowIndex}][medicine_id]" class="form-select form-select-sm" required>
                        <option value="">-- Choose Catalogue Medicine --</option>
                        ${medicinesOptions}
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label extra-small text-muted fw-bold">Route</label>
                    <select name="items[${rowIndex}][route]" class="form-select form-select-sm" required>
                        <option value="oral" selected>Oral</option>
                        <option value="intravenous">Intravenous (IV)</option>
                        <option value="intramuscular">Intramuscular (IM)</option>
                        <option value="topical">Topical</option>
                        <option value="inhalation">Inhalation</option>
                        <option value="ophthalmic">Ophthalmic</option>
                        <option value="rectal">Rectal</option>
                        <option value="sublingual">Sublingual</option>
                    </select>
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label extra-small text-muted fw-bold">Dosage</label>
                    <input type="text" name="items[${rowIndex}][dosage]" class="form-control form-control-sm" placeholder="e.g. 500mg" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label extra-small text-muted fw-bold">Frequency</label>
                    <input type="text" name="items[${rowIndex}][frequency]" class="form-control form-control-sm" placeholder="e.g. BD (twice daily)" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label extra-small text-muted fw-bold">Duration</label>
                    <input type="text" name="items[${rowIndex}][duration]" class="form-control form-control-sm" placeholder="e.g. 5 days" required>
                </div>
            </div>
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <label class="form-label extra-small text-muted fw-bold">Quantity Prescribed</label>
                    <input type="number" name="items[${rowIndex}][quantity_prescribed]" class="form-control form-control-sm" min="1" max="10000" placeholder="Total units" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label extra-small text-muted fw-bold">Specific Instructions</label>
                    <input type="text" name="items[${rowIndex}][instructions]" class="form-control form-control-sm" placeholder="e.g. Take with food">
                </div>
            </div>
        `;
        container.appendChild(row);
        rowIndex++;
    });

    document.getElementById('prescriptionItemsContainer')?.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row-btn')) {
            const row = e.target.closest('.prescription-item-row');
            if (document.querySelectorAll('.prescription-item-row').length > 1) {
                row.remove();
            } else {
                alert('A prescription must contain at least one medication line item.');
            }
        }
    });
});
</script>
@endsection
