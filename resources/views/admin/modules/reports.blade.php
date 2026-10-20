@extends('layouts.admin')

@section('title', 'Executive Reports')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Executive Reports & Hospital Census</h1>
        <p class="text-muted small mb-0">High-level institutional analytics for Medical Directors and Hospital Leadership.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
                <h2 class="h5 fw-bold text-dark">Clinical Workload</h2>
                <p class="text-muted small mb-0">Departmental patient load, outpatient volumes, and appointment turnaround.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-door-open fs-4"></i>
                </div>
                <h2 class="h5 fw-bold text-dark">Bed Capacity & Census</h2>
                <p class="text-muted small mb-0">Inpatient bed occupancy rates, ward allocations, and discharge metrics.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning mx-auto mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <h2 class="h5 fw-bold text-dark">Financial Settlement</h2>
                <p class="text-muted small mb-0">Billing collections, insurance pre-authorizations, and daily cash reconciliation.</p>
            </div>
        </div>
    </div>
</div>
@endsection
