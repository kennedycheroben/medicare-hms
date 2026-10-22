@extends('layouts.admin')

@section('title', $medicine->brand_name . ' - Inventory & Batches')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Inventory Catalogue
            </a>
            <div>
                <h1 class="h4 fw-bold text-dark mb-0">{{ $medicine->brand_name }} ({{ $medicine->generic_name }})</h1>
                <span class="text-muted small">Catalogue Code: <strong class="font-monospace text-dark">{{ $medicine->code }}</strong> &bull; Category: {{ $medicine->category }}</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if(Auth::user()->hasRole(['pharmacist', 'super_admin']))
                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#otcSaleModal">
                    <i class="bi bi-cart-check me-1"></i> OTC Dispense
                </button>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#receiveBatchModal">
                    <i class="bi bi-box-seam me-1"></i> Receive Stock Batch
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

    <!-- Medicine Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Available Non-Expired Stock</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($medicine->available_stock) }} <small class="fs-6 font-normal text-muted">{{ $medicine->unit_of_measure }}</small></div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Reorder Warning Threshold</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($medicine->reorder_level) }} <small class="fs-6 font-normal text-muted">{{ $medicine->unit_of_measure }}</small></div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Form & Strength</div>
                <div class="fs-5 fw-bold text-dark mt-1">{{ ucfirst($medicine->dosage_form) }} &bull; {{ $medicine->strength }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Stock Status</div>
                <div class="fs-5 fw-bold mt-1">
                    @if($medicine->available_stock <= 0)
                        <span class="text-danger"><i class="bi bi-x-octagon-fill me-1"></i> Out of Stock</span>
                    @elseif($medicine->isLowStock())
                        <span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Low Stock</span>
                    @else
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Adequate</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Batches Inventory Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-layers text-primary"></i> Stock Batches (FEFO Dispensing Sequence)
                <span class="badge bg-primary rounded-pill">{{ $medicine->batches->count() }}</span>
            </h5>
            <span class="text-muted small">Sorted by expiry date</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Batch Number</th>
                        <th>Expiry Date</th>
                        <th>Initial Received Qty</th>
                        <th>Current In-Stock Qty</th>
                        <th>Purchase Cost</th>
                        <th>Dispensing Price</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicine->batches as $batch)
                        @php
                            $isExpired = $batch->isExpired();
                            $isDepleted = $batch->isDepleted();
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <span class="font-monospace fw-bold text-dark fs-6">{{ $batch->batch_number }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold {{ $isExpired ? 'text-danger' : 'text-dark' }}">
                                    {{ $batch->expiry_date->format('M d, Y') }}
                                </div>
                                <div class="extra-small text-muted">
                                    {{ $isExpired ? 'Expired ' . $batch->expiry_date->diffForHumans() : 'Expires ' . $batch->expiry_date->diffForHumans() }}
                                </div>
                            </td>
                            <td class="fw-semibold text-muted">{{ number_format($batch->quantity_initial) }}</td>
                            <td>
                                <span class="fw-bold fs-6 {{ $isDepleted ? 'text-muted' : ($isExpired ? 'text-danger' : 'text-success') }}">
                                    {{ number_format($batch->quantity_current) }}
                                </span>
                            </td>
                            <td>KES {{ number_format($batch->unit_purchase_cost, 2) }}</td>
                            <td>KES {{ number_format($batch->unit_dispensing_price, 2) }}</td>
                            <td>
                                @if($isExpired)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-clock-history me-1"></i> Expired</span>
                                @elseif($isDepleted)
                                    <span class="badge bg-secondary-subtle text-secondary border">Depleted</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check me-1"></i> Active</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                @if(Auth::user()->hasRole(['pharmacist', 'super_admin']))
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <button class="dropdown-item small" type="button" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $batch->id }}">
                                                    <i class="bi bi-sliders text-primary me-2"></i> Manual Adjustment
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item small" type="button" data-bs-toggle="modal" data-bs-target="#internalUseModal{{ $batch->id }}">
                                                    <i class="bi bi-hospital text-info me-2"></i> Internal Hospital Issue
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item small" type="button" data-bs-toggle="modal" data-bs-target="#wastageModal{{ $batch->id }}">
                                                    <i class="bi bi-trash text-danger me-2"></i> Wastage / Disposal
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item small" type="button" data-bs-toggle="modal" data-bs-target="#returnModal{{ $batch->id }}">
                                                    <i class="bi bi-arrow-return-left text-success me-2"></i> Return Stock to Batch
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Modal: Adjust Stock Batch -->
                                    <div class="modal fade" id="adjustModal{{ $batch->id }}" tabindex="-1" aria-labelledby="adjustModalLabel{{ $batch->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form method="POST" action="{{ route('admin.inventory.batches.adjust', $batch->id) }}">
                                                    @csrf
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold text-dark" id="adjustModalLabel{{ $batch->id }}">
                                                            <i class="bi bi-sliders text-primary me-2"></i> Adjust Stock - Batch #{{ $batch->batch_number }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <div class="small text-muted">Current Batch Stock Level:</div>
                                                            <div class="fw-bold fs-5 text-dark">{{ $batch->quantity_current }} {{ $medicine->unit_of_measure }}</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Adjustment Direction <span class="text-danger">*</span></label>
                                                            <select name="adjustment_type" class="form-select form-select-sm" required>
                                                                <option value="adjustment_add">Addition (Reconciliation gain, donation, found stock)</option>
                                                                <option value="adjustment_deduct">Deduction (Correction, shrinkage, physical variance)</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="adjQty{{ $batch->id }}" class="form-label fw-bold small">Quantity to Adjust <span class="text-danger">*</span></label>
                                                            <input type="number" name="quantity" id="adjQty{{ $batch->id }}" class="form-control form-control-sm" min="1" max="100000" placeholder="Number of units" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="adjReason{{ $batch->id }}" class="form-label fw-bold small">Mandatory Audit Rationale <span class="text-danger">*</span></label>
                                                            <textarea name="reason" id="adjReason{{ $batch->id }}" rows="3" class="form-control form-control-sm" placeholder="Detailed explanation for inventory reconciliation..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Apply Stock Adjustment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal: Internal Hospital Use -->
                                    <div class="modal fade" id="internalUseModal{{ $batch->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form method="POST" action="{{ route('admin.inventory.batches.internal-use', $batch->id) }}">
                                                    @csrf
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold text-dark">
                                                            <i class="bi bi-hospital text-info me-2"></i> Internal Hospital Issue - Batch #{{ $batch->batch_number }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <div class="small text-muted">Available Stock in Batch:</div>
                                                            <div class="fw-bold fs-5 text-dark">{{ $batch->quantity_current }} {{ $medicine->unit_of_measure }}</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Receiving Department / Ward <span class="text-danger">*</span></label>
                                                            <input type="text" name="department" class="form-control form-control-sm" placeholder="e.g. Emergency Room, ICU, Maternity Ward" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Quantity to Issue <span class="text-danger">*</span></label>
                                                            <input type="number" name="quantity" class="form-control form-control-sm" min="1" max="{{ $batch->quantity_current }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Clinical / Operational Justification <span class="text-danger">*</span></label>
                                                            <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="Purpose of internal department requisitions..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-info text-white">Issue for Internal Use</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal: Wastage / Disposal -->
                                    <div class="modal fade" id="wastageModal{{ $batch->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form method="POST" action="{{ route('admin.inventory.batches.wastage', $batch->id) }}">
                                                    @csrf
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold text-dark">
                                                            <i class="bi bi-trash text-danger me-2"></i> Log Wastage / Disposal - Batch #{{ $batch->batch_number }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <div class="small text-muted">Available Stock in Batch:</div>
                                                            <div class="fw-bold fs-5 text-dark">{{ $batch->quantity_current }} {{ $medicine->unit_of_measure }}</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Quantity to Write Off <span class="text-danger">*</span></label>
                                                            <input type="number" name="quantity" class="form-control form-control-sm" min="1" max="{{ $batch->quantity_current }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Reason for Disposal / Wastage <span class="text-danger">*</span></label>
                                                            <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="Broken ampoule, cold-chain excursion, expired stock disposal..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Disposal / Write-Off</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal: Return Stock -->
                                    <div class="modal fade" id="returnModal{{ $batch->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form method="POST" action="{{ route('admin.inventory.batches.return', $batch->id) }}">
                                                    @csrf
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold text-dark">
                                                            <i class="bi bi-arrow-return-left text-success me-2"></i> Return Stock to Batch #{{ $batch->batch_number }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <div class="small text-muted">Current Batch Stock:</div>
                                                            <div class="fw-bold fs-5 text-dark">{{ $batch->quantity_current }} {{ $medicine->unit_of_measure }}</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Quantity to Return <span class="text-danger">*</span></label>
                                                            <input type="number" name="quantity" class="form-control form-control-sm" min="1" max="10000" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Return Origin / Reference Type</label>
                                                            <input type="text" name="reference_type" class="form-control form-control-sm" placeholder="e.g. Ward Return, Discharge Reversal, Unopened Return">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Document / Transaction Reference</label>
                                                            <input type="text" name="reference_id" class="form-control form-control-sm" placeholder="e.g. DISP-0012, REC-8493">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small">Mandatory Reason for Return <span class="text-danger">*</span></label>
                                                            <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="Reason for inventory return..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Restore Stock</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                                No stock batches recorded for this medicine yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Stock Movements for this Medicine -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left-right text-secondary"></i> Historical Stock Movements
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>Batch #</th>
                        <th>Movement Type</th>
                        <th>Quantity</th>
                        <th>Balance (Before &rarr; After)</th>
                        <th>Recorded By</th>
                        <th class="pe-4">Reason / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicine->stockMovements as $mv)
                        <tr>
                            <td class="ps-4 small text-muted">{{ $mv->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <span class="font-monospace fw-semibold">{{ $mv->batch->batch_number ?? 'General' }}</span>
                            </td>
                            <td>
                                @if($mv->movement_type === 'dispensed')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Dispensed</span>
                                @elseif($mv->movement_type === 'initial_stock')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Intake</span>
                                @elseif($mv->movement_type === 'adjustment_add')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">+ Adjustment</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">- Adjustment</span>
                                @endif
                            </td>
                            <td class="fw-bold">{{ $mv->quantity }}</td>
                            <td class="small">{{ $mv->quantity_before }} &rarr; <span class="fw-bold text-dark">{{ $mv->quantity_after }}</span></td>
                            <td>{{ $mv->performer->name ?? 'System' }}</td>
                            <td class="pe-4 small text-muted">{{ $mv->reason ?? $mv->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted small">No movements recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Receive New Batch -->
@if(Auth::user()->hasRole(['pharmacist', 'super_admin']))
<div class="modal fade" id="receiveBatchModal" tabindex="-1" aria-labelledby="receiveBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.inventory.batches.store', $medicine->id) }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="receiveBatchModalLabel">
                        <i class="bi bi-box-seam text-primary me-2"></i> Receive New Stock Batch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="batch_number" class="form-label fw-bold small">Batch / Lot Number <span class="text-danger">*</span></label>
                        <input type="text" name="batch_number" id="batch_number" class="form-control form-control-sm text-uppercase font-monospace" placeholder="e.g. BTH-2026-001" required>
                    </div>

                    <div class="mb-3">
                        <label for="expiry_date" class="form-label fw-bold small">Expiry Date <span class="text-danger">*</span></label>
                        <input type="date" name="expiry_date" id="expiry_date" class="form-control form-control-sm" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                        <div class="form-text extra-small">Must be a future calendar date.</div>
                    </div>

                    <div class="mb-3">
                        <label for="quantity_initial" class="form-label fw-bold small">Initial Received Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity_initial" id="quantity_initial" class="form-control form-control-sm" min="1" max="1000000" placeholder="Number of units (e.g. 500)" required>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label for="unit_purchase_cost" class="form-label fw-bold small">Unit Cost (KES)</label>
                            <input type="number" step="0.01" name="unit_purchase_cost" id="unit_purchase_cost" class="form-control form-control-sm" min="0" placeholder="0.00">
                        </div>
                        <div class="col-6">
                            <label for="unit_dispensing_price" class="form-label fw-bold small">Dispensing Price (KES)</label>
                            <input type="number" step="0.01" name="unit_dispensing_price" id="unit_dispensing_price" class="form-control form-control-sm" min="0" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Receive into Warehouse</button>
                </div>
            </form>
        </div>
</div>

<!-- Modal: Over-the-Counter (OTC) Direct Sale -->
<div class="modal fade" id="otcSaleModal" tabindex="-1" aria-labelledby="otcSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.inventory.medicines.otc-sale', $medicine->id) }}">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="otcSaleModalLabel">
                        <i class="bi bi-cart-check text-primary me-2"></i> Over-the-Counter (OTC) Sale
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 p-2.5 small mb-3 rounded-3">
                        <i class="bi bi-info-circle me-1"></i> Stock will be automatically allocated from active, non-expired batches according to <strong>FEFO</strong>.
                    </div>
                    <div class="mb-3">
                        <div class="small text-muted">Available Non-Expired Stock:</div>
                        <div class="fw-bold fs-5 text-primary">{{ number_format($medicine->available_stock) }} {{ $medicine->unit_of_measure }}</div>
                    </div>
                    <div class="mb-3">
                        <label for="otcCustomerName" class="form-label fw-bold small">Customer / Patient Name <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" id="otcCustomerName" class="form-control form-control-sm" placeholder="e.g. Walk-in Customer / Mary Wanjiku" required>
                    </div>
                    <div class="mb-3">
                        <label for="otcQuantity" class="form-label fw-bold small">Quantity to Sell <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="otcQuantity" class="form-control form-control-sm" min="1" max="{{ $medicine->available_stock }}" placeholder="Number of units" required>
                    </div>
                    <div class="mb-3">
                        <label for="otcPaymentRef" class="form-label fw-bold small">Payment Reference (e.g. M-Pesa / Receipt #)</label>
                        <input type="text" name="payment_reference" id="otcPaymentRef" class="form-control form-control-sm" placeholder="e.g. QA849201KZ or Cash Receipt #102">
                    </div>
                    <div class="mb-3">
                        <label for="otcNotes" class="form-label fw-bold small">Pharmacist Notes</label>
                        <textarea name="notes" id="otcNotes" rows="2" class="form-control form-control-sm" placeholder="Optional clinical or transaction remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" {{ $medicine->available_stock <= 0 ? 'disabled' : '' }}>Complete OTC Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
