@extends('layouts.admin')

@section('title', 'Billing & Cashier')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Billing, Invoicing & Cashier Desk</h1>
        <p class="text-muted small mb-0">Authorized access restricted to Cashiers, Billing Clerks, and Finance Directors.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-receipt fs-3"></i>
            </div>
            <div>
                <h2 class="h5 fw-bold text-dark mb-1">Cashier & Settlement Queue</h2>
                <div class="text-muted small">Generate patient itemized invoices, process cash and M-Pesa payments, and issue official stamp receipts.</div>
            </div>
        </div>
        <div class="alert alert-light border small text-muted mb-0">
            <i class="bi bi-wallet2 me-1"></i> Real-time M-Pesa reconciliation and daily register closing enforced.
        </div>
    </div>
</div>
@endsection
