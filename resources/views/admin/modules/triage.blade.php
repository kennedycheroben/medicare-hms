@extends('layouts.admin')

@section('title', 'Triage & Nursing Vitals')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Triage, Vital Signs & Patient Intake</h1>
        <p class="text-muted small mb-0">Authorized access restricted to Nursing Officers, Receptionists, and Clinical Administrators.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success">
                <i class="bi bi-heart-pulse fs-3"></i>
            </div>
            <div>
                <h2 class="h5 fw-bold text-dark mb-1">Nursing Triage Station</h2>
                <div class="text-muted small">Record blood pressure, temperature, pulse rate, oxygen saturation, and assign consultation queues.</div>
            </div>
        </div>
        <div class="alert alert-light border small text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i> Patient registration, priority tagging (Emergency / Urgent / Routine), and initial vitals recording.
        </div>
    </div>
</div>
@endsection
