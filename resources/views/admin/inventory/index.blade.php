@extends('layouts.admin')

@section('title', 'Medicine Inventory Catalogue')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Medicine Inventory & Formulary</h1>
            <p class="text-muted small mb-0">Pharmaceutical catalogue, current warehouse balances, batch lifecycles, and automated reorder alerts.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventory.movements') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left-right"></i> Stock Movements
            </a>
            @if(Auth::user()->hasRole(['pharmacist', 'super_admin']))
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#newMedicineModal">
                    <i class="bi bi-plus-lg"></i> Add New Medicine
                </button>
            @endif
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

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search by Brand Name, Generic Name, or Code..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="">-- All Therapeutic Categories --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" name="low_stock" id="lowStockCheck" value="1" {{ $lowStockOnly ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label small fw-semibold text-danger" for="lowStockCheck">Low Stock Only</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                    @if($search || $category || $lowStockOnly)
                        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-boxes text-primary"></i> Medication Catalogue & Stock Balances
                <span class="badge bg-primary rounded-pill">{{ $medicines->total() }}</span>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Brand & Generic Name</th>
                        <th>Category</th>
                        <th>Form & Strength</th>
                        <th>Available Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicines as $med)
                        @php
                            $available = $med->available_stock;
                            $isLow = $med->isLowStock();
                        @endphp
                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-dark">{{ $med->code }}</td>
                            <td>
                                <a href="{{ route('admin.inventory.medicines.show', $med->id) }}" class="fw-bold text-decoration-none text-primary">
                                    {{ $med->brand_name }}
                                </a>
                                <div class="text-muted small">{{ $med->generic_name }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $med->category }}</span>
                            </td>
                            <td>
                                <div>{{ ucfirst($med->dosage_form) }}</div>
                                <div class="text-muted extra-small">{{ $med->strength }} ({{ $med->unit_of_measure }})</div>
                            </td>
                            <td>
                                <span class="fw-bold fs-6 {{ $available > $med->reorder_level ? 'text-success' : ($available > 0 ? 'text-warning' : 'text-danger') }}">
                                    {{ number_format($available) }}
                                </span>
                                <span class="text-muted extra-small">units</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ number_format($med->reorder_level) }} units</span>
                            </td>
                            <td>
                                @if($available <= 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-octagon-fill me-1"></i> Out of Stock</span>
                                @elseif($isLow)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-triangle-fill me-1"></i> Low Stock</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i> In Stock</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('admin.inventory.medicines.show', $med->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-eye"></i> View Batches
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <div class="fw-bold fs-6">No medicines match current inventory filters</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($medicines->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $medicines->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: New Medicine Catalogue Registration -->
@if(Auth::user()->hasRole(['pharmacist', 'super_admin']))
<div class="modal fade" id="newMedicineModal" tabindex="-1" aria-labelledby="newMedicineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.inventory.medicines.store') }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="newMedicineModalLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i> Register New Formulary Medicine
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Item Code <span class="badge bg-secondary-subtle text-secondary ms-1">Auto-Generated</span></label>
                            <input type="text" class="form-control form-control-sm bg-light text-muted" value="MED-XXXXXX (Server Assigned)" disabled readonly>
                            <div class="form-text extra-small">Unique product identifier is automatically generated upon saving.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="brand_name" class="form-label fw-bold small">Brand / Trade Name <span class="text-danger">*</span></label>
                            <input type="text" name="brand_name" id="brand_name" class="form-control form-control-sm" placeholder="e.g. Amoxil" required>
                        </div>
                        <div class="col-md-4">
                            <label for="generic_name" class="form-label fw-bold small">Generic / INN Name <span class="text-danger">*</span></label>
                            <input type="text" name="generic_name" id="generic_name" class="form-control form-control-sm" placeholder="e.g. Amoxicillin Trihydrate" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="category" class="form-label fw-bold small">Therapeutic Category <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="category" class="form-control form-control-sm" placeholder="e.g. Antibiotics, Analgesics" required>
                        </div>
                        <div class="col-md-4">
                            <label for="dosage_form" class="form-label fw-bold small">Dosage Form (Formulation) <span class="text-danger">*</span></label>
                            <select name="dosage_form" id="dosage_form" class="form-select form-select-sm" required>
                                <option value="tablet">Tablet</option>
                                <option value="capsule">Capsule</option>
                                <option value="syrup">Syrup</option>
                                <option value="suspension">Suspension</option>
                                <option value="injection">Injection</option>
                                <option value="cream">Cream</option>
                                <option value="ointment">Ointment</option>
                                <option value="drops">Drops</option>
                                <option value="inhaler">Inhaler</option>
                                <option value="suppository">Suppository</option>
                            </select>
                            <div class="form-text extra-small text-muted">Clinical formulation/route administered to the patient.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="strength" class="form-label fw-bold small">Strength <span class="text-danger">*</span></label>
                            <input type="text" name="strength" id="strength" class="form-control form-control-sm" placeholder="e.g. 500mg or 250mg/5ml" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="unit_of_measure" class="form-label fw-bold small">Stock / Dispensing Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit_of_measure" id="unit_of_measure" class="form-control form-control-sm" placeholder="e.g. tablets, capsules, bottles, vials" required>
                            <div class="form-text extra-small text-muted">Warehouse counting & inventory tracking unit (e.g. tablets, bottles, vials).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="reorder_level" class="form-label fw-bold small">Minimum Reorder Threshold <span class="text-danger">*</span></label>
                            <input type="number" name="reorder_level" id="reorder_level" class="form-control form-control-sm" min="0" value="50" required>
                            <div class="form-text extra-small">Automated warning triggered when available stock drops to or below this level.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save to Formulary</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
