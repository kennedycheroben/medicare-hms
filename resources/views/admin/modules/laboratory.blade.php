@extends('layouts.admin')

@section('title', 'Laboratory Diagnostics')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Diagnostic Laboratory & Specimen Tracking</h1>
        <p class="text-muted small mb-0">Authorized access restricted to Certified Laboratory Technicians and Pathologists.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-eyedropper fs-3"></i>
            </div>
            <div>
                <h2 class="h5 fw-bold text-dark mb-1">Lab Specimen Accessioning</h2>
                <div class="text-muted small">Accession incoming blood/tissue samples, log equipment calibration, and upload authorized diagnostic test results.</div>
            </div>
        </div>
        <div class="alert alert-light border small text-muted mb-0">
            <i class="bi bi-clipboard2-check me-1"></i> Quality assurance checks and pathologist approvals are required prior to clinical release.
        </div>
    </div>
</div>
@endsection
