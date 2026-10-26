@extends('layouts.admin')

@section('title', 'Clinical Consultations & EHR')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Doctor Consultation Desk & Electronic Health Records</h1>
        <p class="text-muted small mb-0">Authorized access restricted to Clinical Doctors and Medical Supervisors.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-circle bg-teal bg-opacity-10 text-teal" style="background-color: #ccfbf1; color: #0d9488;">
                <i class="bi bi-person-lines-fill fs-3"></i>
            </div>
            <div>
                <h2 class="h5 fw-bold text-dark mb-1">Patient Consultation Queue</h2>
                <div class="text-muted small">Access assigned appointments, triage notes, SOAP histories, and lab orders.</div>
            </div>
        </div>
        <div class="alert alert-secondary border-0 mb-0">
            <i class="bi bi-shield-lock me-1"></i> Under the Data Protection Act and Hospital Governance, clinical access to electronic health records is restricted to attending physicians and subject to permanent audit logging.
        </div>
    </div>
</div>
@endsection
