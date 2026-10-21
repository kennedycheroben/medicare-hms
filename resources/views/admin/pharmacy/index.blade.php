@extends('layouts.admin')

@section('title', 'Pharmacy Dispensary Queue')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Pharmacy Dispensary Queue</h1>
            <p class="text-muted small mb-0">Review finalized prescriptions, allocate stock from non-expired batches, and log medication dispensations.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pharmacy.history') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-clock-history"></i> Dispensing History
            </a>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-boxes"></i> Medicine Inventory
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
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Operational Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Pending Prescriptions</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($pendingCount) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Dispensed Today</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($dispensedTodayCount) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Low Stock Medicines</div>
                <div class="fs-4 fw-bold {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }} mt-1">
                    {{ number_format($lowStockCount) }}
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Expired Batches in Stock</div>
                <div class="fs-4 fw-bold {{ $expiredBatchesCount > 0 ? 'text-danger' : 'text-muted' }} mt-1">
                    {{ number_format($expiredBatchesCount) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.pharmacy.index') }}" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by RX Number, Patient Name, or MRN..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Queue (Finalized & Partial)</option>
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="finalized" {{ $statusFilter === 'finalized' ? 'selected' : '' }}>Finalized (Unopened)</option>
                        <option value="partially_dispensed" {{ $statusFilter === 'partially_dispensed' ? 'selected' : '' }}>Partially Dispensed</option>
                        <option value="dispensed" {{ $statusFilter === 'dispensed' ? 'selected' : '' }}>Fully Dispensed</option>
                        <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                    @if($search || $statusFilter !== 'active')
                        <a href="{{ route('admin.pharmacy.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Prescription Queue Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-prescription2 text-primary"></i> Prescription Orders
                <span class="badge bg-primary rounded-pill">{{ $prescriptions->total() }}</span>
            </h5>
            <span class="text-muted small">First In, First Out by transmission date</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">RX Number</th>
                        <th>Patient Name & MRN</th>
                        <th>Prescribing Clinician</th>
                        <th>Items Summary</th>
                        <th>Transmission Time</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescriptions as $rx)
                        <tr>
                            <td class="ps-4">
                                <span class="font-monospace fw-bold text-dark">{{ $rx->prescription_number }}</span>
                                <div class="text-muted extra-small">Visit: {{ $rx->visit->visit_number ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.patients.show', $rx->patient_id) }}" class="fw-bold text-decoration-none text-primary">
                                    {{ $rx->patient->full_name }}
                                </a>
                                <div class="text-muted small font-monospace">MRN: {{ $rx->patient->mrn }}</div>
                                @if(!empty($rx->patient->allergies))
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small mt-1">
                                        <i class="bi bi-shield-exclamation"></i> Allergies
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-dark fw-medium">{{ $rx->prescriber->name ?? 'Attending Clinician' }}</span>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="fw-semibold text-dark">{{ $rx->items->count() }} line item(s)</span>
                                    <div class="text-muted text-truncate" style="max-width: 250px;">
                                        {{ $rx->items->pluck('medication_name')->implode(', ') }}
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                <span class="text-dark">{{ $rx->finalized_at ? $rx->finalized_at->format('M d, H:i') : $rx->created_at->format('M d, H:i') }}</span>
                                <div class="text-muted extra-small">{{ ($rx->finalized_at ?? $rx->created_at)->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($rx->status === 'dispensed')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-all"></i> Dispensed</span>
                                @elseif($rx->status === 'partially_dispensed')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">Partial</span>
                                @elseif($rx->status === 'finalized')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Pending Dispense</span>
                                @elseif($rx->status === 'cancelled')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cancelled</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('admin.pharmacy.prescriptions.show', $rx->id) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-capsule"></i> Dispense
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <div class="fw-bold fs-6">No prescriptions currently in this queue view</div>
                                <p class="small mb-0">Doctor prescriptions will appear here immediately upon sign-off.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($prescriptions->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $prescriptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
