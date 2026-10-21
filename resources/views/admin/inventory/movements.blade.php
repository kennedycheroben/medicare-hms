@extends('layouts.admin')

@section('title', 'Inventory Stock Movements')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Inventory Stock Movements Log</h1>
            <p class="text-muted small mb-0">Immutable chronological audit trail of all warehouse receipts, patient dispensations, and manual adjustments.</p>
        </div>
        <div>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-boxes"></i> Medicine Catalogue
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.inventory.movements') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <select name="medicine_id" class="form-select bg-light">
                        <option value="">-- All Medicines --</option>
                        @foreach($medicines as $m)
                            <option value="{{ $m->id }}" {{ (string)$medicineId === (string)$m->id ? 'selected' : '' }}>
                                {{ $m->brand_name }} ({{ $m->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="movement_type" class="form-select bg-light">
                        <option value="">-- All Movement Types --</option>
                        <option value="initial_stock" {{ $movementType === 'initial_stock' ? 'selected' : '' }}>Initial Stock / Batch Receipts</option>
                        <option value="dispensed" {{ $movementType === 'dispensed' ? 'selected' : '' }}>Prescription Dispensings</option>
                        <option value="adjustment_add" {{ $movementType === 'adjustment_add' ? 'selected' : '' }}>Stock Additions</option>
                        <option value="adjustment_deduct" {{ $movementType === 'adjustment_deduct' ? 'selected' : '' }}>Stock Deductions</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                    @if($medicineId || $movementType)
                        <a href="{{ route('admin.inventory.movements') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Stock Audit Records
                <span class="badge bg-primary rounded-pill">{{ $movements->total() }}</span>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>Medicine</th>
                        <th>Batch #</th>
                        <th>Movement Type</th>
                        <th>Quantity</th>
                        <th>Balance Before &rarr; After</th>
                        <th>Initiated By</th>
                        <th class="pe-4">Reason / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mv)
                        <tr>
                            <td class="ps-4 small text-muted">{{ $mv->created_at->format('M d, Y H:i:s') }}</td>
                            <td>
                                @if($mv->medicine)
                                    <a href="{{ route('admin.inventory.medicines.show', $mv->medicine_id) }}" class="fw-bold text-decoration-none">
                                        {{ $mv->medicine->brand_name }}
                                    </a>
                                    <div class="text-muted extra-small">{{ $mv->medicine->code }}</div>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $mv->batch->batch_number ?? 'General' }}
                                </span>
                            </td>
                            <td>
                                @if($mv->movement_type === 'dispensed')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Dispensed</span>
                                @elseif($mv->movement_type === 'initial_stock')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Stock Receipt</span>
                                @elseif($mv->movement_type === 'adjustment_add')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">Adjustment (+)</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Adjustment (-)</span>
                                @endif
                            </td>
                            <td class="fw-bold fs-6">{{ $mv->quantity }}</td>
                            <td class="small">{{ $mv->quantity_before }} &rarr; <span class="fw-bold text-dark">{{ $mv->quantity_after }}</span></td>
                            <td>{{ $mv->performer->name ?? 'System' }}</td>
                            <td class="pe-4 small text-muted">{{ $mv->reason ?? $mv->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <div class="fw-bold fs-6">No inventory movements recorded yet</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
