@extends('layouts.admin')

@section('title', 'Pharmacy Dispensing History')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Pharmacy Dispensing Audit Log</h1>
            <p class="text-muted small mb-0">Chronological transaction record of all medication dispensations across clinical encounters.</p>
        </div>
        <div>
            <a href="{{ route('admin.pharmacy.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Dispensary Queue
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.pharmacy.history') }}" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by Dispensation #, Prescription #, or Medicine Name..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Search</button>
                    @if($search)
                        <a href="{{ route('admin.pharmacy.history') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Dispensing Records Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-journal-medical text-primary"></i> Historical Dispensing Logs
                <span class="badge bg-primary rounded-pill">{{ $dispensings->total() }}</span>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Dispensation #</th>
                        <th>Prescription & Patient</th>
                        <th>Medication</th>
                        <th>Batch #</th>
                        <th>Quantity</th>
                        <th>Dispensed By</th>
                        <th>Date & Time</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dispensings as $dsp)
                        <tr>
                            <td class="ps-4">
                                <span class="font-monospace fw-bold text-dark">{{ $dsp->dispensation_number }}</span>
                            </td>
                            <td>
                                @if($dsp->prescription)
                                    <a href="{{ route('admin.prescriptions.show', $dsp->prescription_id) }}" class="fw-semibold text-decoration-none">
                                        {{ $dsp->prescription->prescription_number }}
                                    </a>
                                    <div class="text-muted small">
                                        {{ $dsp->prescription->patient->full_name ?? 'N/A' }} (MRN: {{ $dsp->prescription->patient->mrn ?? 'N/A' }})
                                    </div>
                                @else
                                    <span class="text-muted">Direct Dispensation</span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-dark">{{ $dsp->medicine->brand_name ?? 'N/A' }}</strong>
                                <div class="text-muted extra-small">{{ $dsp->medicine->generic_name ?? '' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $dsp->batch->batch_number ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="fw-bold text-success">{{ $dsp->quantity_dispensed }} units</td>
                            <td>{{ $dsp->dispensedByUser->name ?? 'Staff' }}</td>
                            <td class="small text-muted">{{ $dsp->dispensed_at ? $dsp->dispensed_at->format('M d, Y H:i') : 'N/A' }}</td>
                            <td class="pe-4 text-end">
                                @if($dsp->prescription_id)
                                    <a href="{{ route('admin.prescriptions.show', $dsp->prescription_id) }}" class="btn btn-sm btn-outline-primary">
                                        View RX
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <div class="fw-bold fs-6">No dispensing logs recorded yet</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($dispensings->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $dispensings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
