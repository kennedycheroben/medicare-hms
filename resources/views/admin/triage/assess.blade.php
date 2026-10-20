@extends('layouts.admin')

@section('title', 'Triage Assessment - ' . $patient->full_name)

@section('content')
<div class="container-fluid p-0" style="max-width: 980px;">
    <div class="mb-4">
        <a href="{{ route('admin.triage.index') }}" class="text-decoration-none text-muted small">&larr; Back to Triage Queue</a>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
            <div>
                <h1 class="h3 fw-bold text-dark mb-0">Clinical Triage Assessment</h1>
                <p class="text-muted small mb-0">Encounter Reference: <code>{{ $visit->visit_number }}</code> | Arrival: {{ $visit->checked_in_at ? $visit->checked_in_at->format('Y-m-d H:i') : '' }}</p>
            </div>
            <div>
                <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                    Source: {{ ucfirst($visit->visit_type) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Patient Banner Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-person-heart fs-3"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="h5 fw-bold text-dark mb-0">{{ $patient->full_name }}</h2>
                        <code class="fw-bold text-primary">{{ $patient->mrn }}</code>
                        <span class="badge bg-light text-dark border">{{ $patient->age }} yrs / {{ ucfirst($patient->gender) }}</span>
                        @if($patient->blood_group)
                            <span class="badge bg-danger-subtle text-danger">Blood: {{ $patient->blood_group }}</span>
                        @endif
                    </div>
                    <div class="small text-muted mt-1">
                        <span><i class="bi bi-telephone me-1"></i> {{ $patient->phone }}</span>
                        <span class="ms-3"><i class="bi bi-card-text me-1"></i> Intake Reason: <strong>{{ $visit->reason_for_visit }}</strong></span>
                    </div>
                </div>
            </div>

            @if($patient->allergies)
                <div class="alert alert-danger border-0 p-2 mt-3 mb-0 small rounded-3 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div><strong>Known Allergies:</strong> {{ $patient->allergies }}</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Triage Form -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('admin.triage.store', $visit->id) }}" method="POST">
                @csrf

                <!-- Section 1: Vital Signs -->
                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">
                    <i class="bi bi-heart-pulse text-danger me-1"></i> 1. Physiological Vital Signs
                </h3>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <label for="systolic_bp" class="form-label fw-semibold small text-dark">Systolic BP (mmHg)</label>
                        <input type="number" class="form-control @error('systolic_bp') is-invalid @enderror" id="systolic_bp" name="systolic_bp" value="{{ old('systolic_bp', $triageRecord->systolic_bp ?? '') }}" min="40" max="300" placeholder="e.g. 120">
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label for="diastolic_bp" class="form-label fw-semibold small text-dark">Diastolic BP (mmHg)</label>
                        <input type="number" class="form-control @error('diastolic_bp') is-invalid @enderror" id="diastolic_bp" name="diastolic_bp" value="{{ old('diastolic_bp', $triageRecord->diastolic_bp ?? '') }}" min="20" max="200" placeholder="e.g. 80">
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label for="pulse_rate" class="form-label fw-semibold small text-dark">Pulse Rate (bpm)</label>
                        <input type="number" class="form-control @error('pulse_rate') is-invalid @enderror" id="pulse_rate" name="pulse_rate" value="{{ old('pulse_rate', $triageRecord->pulse_rate ?? '') }}" min="30" max="250" placeholder="e.g. 72">
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label for="temperature" class="form-label fw-semibold small text-dark">Temperature (°C)</label>
                        <input type="number" step="0.1" class="form-control @error('temperature') is-invalid @enderror" id="temperature" name="temperature" value="{{ old('temperature', $triageRecord->temperature ?? '') }}" min="30.0" max="45.0" placeholder="e.g. 36.8">
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label for="respiratory_rate" class="form-label fw-semibold small text-dark">Respiratory Rate (/min)</label>
                        <input type="number" class="form-control @error('respiratory_rate') is-invalid @enderror" id="respiratory_rate" name="respiratory_rate" value="{{ old('respiratory_rate', $triageRecord->respiratory_rate ?? '') }}" min="8" max="80" placeholder="e.g. 18">
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label for="oxygen_saturation" class="form-label fw-semibold small text-dark">SpO2 Oxygen Sat. (%)</label>
                        <input type="number" class="form-control @error('oxygen_saturation') is-invalid @enderror" id="oxygen_saturation" name="oxygen_saturation" value="{{ old('oxygen_saturation', $triageRecord->oxygen_saturation ?? '') }}" min="50" max="100" placeholder="e.g. 98">
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label for="pain_score" class="form-label fw-semibold small text-dark">Pain Score (0 - 10)</label>
                        <select class="form-select @error('pain_score') is-invalid @enderror" id="pain_score" name="pain_score">
                            <option value="">None / Not Assessed</option>
                            @for($i = 0; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('pain_score', $triageRecord->pain_score ?? '') == "$i" ? 'selected' : '' }}>
                                    {{ $i }} - {{ $i === 0 ? 'No Pain' : ($i <= 3 ? 'Mild' : ($i <= 6 ? 'Moderate' : ($i <= 8 ? 'Severe' : 'Worst Possible'))) }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label for="blood_glucose" class="form-label fw-semibold small text-dark">Random Blood Sugar (mmol/L)</label>
                        <input type="number" step="0.1" class="form-control @error('blood_glucose') is-invalid @enderror" id="blood_glucose" name="blood_glucose" value="{{ old('blood_glucose', $triageRecord->blood_glucose ?? '') }}" placeholder="e.g. 5.5">
                    </div>

                    <div class="col-sm-4">
                        <label for="weight_kg" class="form-label fw-semibold small text-dark">Weight (kg)</label>
                        <input type="number" step="0.1" class="form-control bmi-trigger" id="weight_kg" name="weight_kg" value="{{ old('weight_kg', $triageRecord->weight_kg ?? '') }}" placeholder="e.g. 70.5">
                    </div>

                    <div class="col-sm-4">
                        <label for="height_cm" class="form-label fw-semibold small text-dark">Height (cm)</label>
                        <input type="number" step="0.1" class="form-control bmi-trigger" id="height_cm" name="height_cm" value="{{ old('height_cm', $triageRecord->height_cm ?? '') }}" placeholder="e.g. 175">
                    </div>

                    <div class="col-sm-4">
                        <label class="form-label fw-semibold small text-dark">Calculated BMI (kg/m²)</label>
                        <input type="text" class="form-control bg-light fw-bold" id="bmi_display" readonly value="{{ $triageRecord->bmi ?? '—' }}">
                    </div>
                </div>

                <!-- Section 2: Clinical Acuity Categorization -->
                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">
                    <i class="bi bi-shield-shaded text-primary me-1"></i> 2. Clinical Acuity Classification (Manchester / ESI Scale) <span class="text-danger">*</span>
                </h3>

                <div class="row g-2 mb-4">
                    @php
                        $selectedCat = old('triage_category', $triageRecord->triage_category ?? ($visit->priority === 'emergency' ? 'emergency' : 'standard'));
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-3 p-3 h-100 bg-danger bg-opacity-10 border-danger">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="triage_category" id="cat_emergency" value="emergency" {{ $selectedCat === 'emergency' ? 'checked' : '' }} required>
                                <label class="form-check-label fw-bold text-danger" for="cat_emergency">
                                    Cat 1: Immediate Resuscitation
                                </label>
                            </div>
                            <div class="small text-muted ps-4 mt-1">Airway compromise, severe respiratory distress, unresponsiveness, cardiac arrest risk.</div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-3 p-3 h-100 bg-warning bg-opacity-10 border-warning">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="triage_category" id="cat_very_urgent" value="very_urgent" {{ $selectedCat === 'very_urgent' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-warning-emphasis" for="cat_very_urgent">
                                    Cat 2: Very Urgent (High Risk)
                                </label>
                            </div>
                            <div class="small text-muted ps-4 mt-1">Severe pain, high fever with altered vitals, chest pain, active hemorrhage.</div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-3 p-3 h-100 bg-info bg-opacity-10 border-info">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="triage_category" id="cat_urgent" value="urgent" {{ $selectedCat === 'urgent' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-info-emphasis" for="cat_urgent">
                                    Cat 3: Urgent (Acute Illness)
                                </label>
                            </div>
                            <div class="small text-muted ps-4 mt-1">Moderate acute illness, stable vitals, requires multiple clinical investigations.</div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-success bg-opacity-10 border-success">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="triage_category" id="cat_standard" value="standard" {{ $selectedCat === 'standard' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-success" for="cat_standard">
                                    Cat 4: Standard (Routine Outpatient)
                                </label>
                            </div>
                            <div class="small text-muted ps-4 mt-1">Standard clinical consultation, non-urgent acute symptoms, minor injuries.</div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-secondary bg-opacity-10 border-secondary">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="triage_category" id="cat_non_urgent" value="non_urgent" {{ $selectedCat === 'non_urgent' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-secondary" for="cat_non_urgent">
                                    Cat 5: Non-Urgent (Minor / Refill)
                                </label>
                            </div>
                            <div class="small text-muted ps-4 mt-1">Chronic stable complaints, medication repeat prescription, medical checkup.</div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Clinical Observations & Presenting Complaints -->
                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">
                    <i class="bi bi-file-medical text-primary me-1"></i> 3. Clinical Notes & Referral Destination
                </h3>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="presenting_complaint" class="form-label fw-semibold small text-dark">Presenting Complaints & History of Current Illness <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('presenting_complaint') is-invalid @enderror" id="presenting_complaint" name="presenting_complaint" rows="3" required placeholder="Detailed description of patient symptoms, duration, onset, and clinical triage observations...">{{ old('presenting_complaint', $triageRecord->presenting_complaint ?? $visit->reason_for_visit) }}</textarea>
                        @error('presenting_complaint')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="nursing_notes" class="form-label fw-semibold small text-dark">Nursing Triage Notes / Interventions</label>
                        <textarea class="form-control @error('nursing_notes') is-invalid @enderror" id="nursing_notes" name="nursing_notes" rows="2" placeholder="e.g. Paracetamol administered, wound dressed, patient placed in recovery position...">{{ old('nursing_notes', $triageRecord->nursing_notes ?? '') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="referred_department_id" class="form-label fw-semibold small text-dark">Refer / Handoff to Clinic <span class="text-danger">*</span></label>
                        <select class="form-select @error('referred_department_id') is-invalid @enderror" id="referred_department_id" name="referred_department_id" required>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('referred_department_id', $triageRecord->referred_department_id ?? $visit->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="referred_doctor_id" class="form-label fw-semibold small text-dark">Assign to Attending Physician</label>
                        <select class="form-select @error('referred_doctor_id') is-invalid @enderror" id="referred_doctor_id" name="referred_doctor_id">
                            <option value="">Next Available Duty Clinician</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" {{ old('referred_doctor_id', $triageRecord->referred_doctor_id ?? $visit->doctor_id) == $doc->id ? 'selected' : '' }}>
                                    {{ $doc->name }} ({{ $doc->specialty }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="alert alert-light border small text-muted mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                    <div>
                        <strong>Clinical Governance Notice:</strong> Triage categorization operates under hospital protocol standards adapted from the Manchester / ESI triage systems. Emergency cases will immediately trigger high-priority alerts on clinical consultation boards.
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.triage.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="bi bi-clipboard2-check me-1"></i> Save Triage & Complete Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const weightInput = document.getElementById('weight_kg');
        const heightInput = document.getElementById('height_cm');
        const bmiDisplay = document.getElementById('bmi_display');

        function updateBmi() {
            const w = parseFloat(weightInput.value);
            const h = parseFloat(heightInput.value);
            if (w > 0 && h > 0) {
                const hMeters = h / 100;
                const bmi = (w / (hMeters * hMeters)).toFixed(1);
                bmiDisplay.value = `${bmi} kg/m²`;
            } else {
                bmiDisplay.value = '—';
            }
        }

        if (weightInput && heightInput && bmiDisplay) {
            weightInput.addEventListener('input', updateBmi);
            heightInput.addEventListener('input', updateBmi);
        }
    });
</script>
@endpush
@endsection
