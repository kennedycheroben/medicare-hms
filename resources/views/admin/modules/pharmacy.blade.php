@extends('layouts.admin')

@section('title', 'Pharmacy & Formulary Dispensation')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Pharmacy Dispensation & Formulary Inventory</h1>
        <p class="text-muted small mb-0">Authorized access restricted to Licensed Pharmacists and Pharmacy Technicians.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-capsule fs-3"></i>
            </div>
            <div>
                <h2 class="h5 fw-bold text-dark mb-1">Prescription Dispensation Queue</h2>
                <div class="text-muted small">Verify doctor prescriptions, manage stock inventory, batch tracking, and dispensation safety audits.</div>
            </div>
        </div>
        <div class="alert alert-light border small text-muted mb-0">
            <i class="bi bi-shield-check me-1"></i> Pharmacy transactions require dual verification for controlled substances.
        </div>
    </div>
</div>
@endsection
