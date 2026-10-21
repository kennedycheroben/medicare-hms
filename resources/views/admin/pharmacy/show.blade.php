@extends('layouts.admin')

@section('title', 'Dispense Prescription #' . $prescription->prescription_number)

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.pharmacy.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Dispensary Queue
            </a>
            <div>
                <h1 class="h4 fw-bold text-dark mb-0">Pharmacy Dispensing Workspace</h1>
                <span class="text-muted small">Prescription #{{ $prescription->prescription_number }} &bull; Transmitted: {{ $prescription->created_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
        <div>
            @if($prescription->status === 'dispensed')
                <span class="badge bg-success px-3 py-2 fs-6"><i class="bi bi-check-circle-fill me-1"></i> Fully Dispensed</span>
            @elseif($prescription->status === 'partially_dispensed')
                <span class="badge bg-info text-dark px-3 py-2 fs-6">Partially Dispensed</span>
            @elseif($prescription->status === 'finalized')
                <span class="badge bg-primary px-3 py-2 fs-6">Pending Fulfillment</span>
            @else
                <span class="badge bg-secondary px-3 py-2 fs-6">{{ ucfirst($prescription->status) }}</span>
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

    <!-- Patient Context & Allergy Safety Check -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 50px; height: 50px;">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                {{ $prescription->patient->full_name }}
                            </h5>
                            <div class="text-muted small">
                                MRN: <strong class="font-monospace text-dark">{{ $prescription->patient->mrn }}</strong> &bull;
                                Age: {{ $prescription->patient->age ?? 'N/A' }} yrs &bull;
                                Gender: {{ ucfirst($prescription->patient->gender) }} &bull;
                                Prescribing Doctor: <strong>{{ $prescription->prescriber->name ?? 'Attending Clinician' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    @if(!empty($prescription->patient->allergies))
                        <div class="alert alert-danger mb-0 p-2 border-danger-subtle small">
                            <strong class="text-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> Patient Allergies Noted:</strong>
                            <div class="fw-bold text-dark mt-1">{{ $prescription->patient->allergies }}</div>
                            <div class="extra-small text-muted mt-1">* Pharmacy Safety Reminder: Screen all dispensed medications against documented hypersensitivities.</div>
                        </div>
                    @else
                        <div class="alert alert-light mb-0 p-2 border small text-muted">
                            <i class="bi bi-shield-check text-success me-1"></i> No adverse allergies recorded for patient.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Prescribed Medication Items & Dispensing Actions -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-capsule-pill text-primary"></i> Medication Line Items Fulfillment
            </h5>
            <span class="text-muted small">Stock deducted in First Expired, First Out (FEFO) batch priority</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Medication & Regimen</th>
                        <th>Prescribed Qty</th>
                        <th>Dispensed Qty</th>
                        <th>Remaining</th>
                        <th>Available Non-Expired Stock</th>
                        <th>Item Status</th>
                        <th class="pe-4 text-end">Dispense Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescription->items as $item)
                        @php
                            $availableStock = $item->medicine ? $item->medicine->available_stock : 0;
                            $canDispense = in_array($item->dispense_status, ['pending', 'partially_dispensed'], true) && $item->remaining_quantity > 0 && in_array($prescription->status, ['finalized', 'partially_dispensed'], true);
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark fs-6">{{ $item->medication_name }}</div>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-dark border me-1">{{ ucfirst($item->route) }}</span>
                                    {{ $item->dosage }} &bull; {{ $item->frequency }} &bull; Duration: {{ $item->duration }}
                                </div>
                                @if($item->instructions)
                                    <div class="text-muted extra-small fst-italic">Note: {{ $item->instructions }}</div>
                                @endif
                                @if($item->non_dispense_reason)
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-x-circle me-1"></i> Declined: {{ $item->non_dispense_reason }}
                                    </div>
                                @endif
                            </td>
                            <td class="fw-bold fs-6">{{ $item->quantity_prescribed }}</td>
                            <td class="fw-bold text-success fs-6">{{ $item->quantity_dispensed }}</td>
                            <td class="fw-bold {{ $item->remaining_quantity > 0 ? 'text-primary' : 'text-muted' }} fs-6">
                                {{ $item->remaining_quantity }}
                            </td>
                            <td>
                                @if($item->medicine)
                                    <div>
                                        <span class="badge {{ $availableStock >= $item->remaining_quantity ? 'bg-success-subtle text-success border border-success-subtle' : ($availableStock > 0 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle') }} fs-6">
                                            {{ $availableStock }} units available
                                        </span>
                                    </div>
                                    <div class="extra-small text-muted mt-1">
                                        {{ $item->medicine->batches->count() }} active batch(es) in stock
                                    </div>
                                @else
                                    <span class="badge bg-secondary">Uncatalogued</span>
                                @endif
                            </td>
                            <td>
                                @if($item->dispense_status === 'dispensed')
                                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> Fulfilled</span>
                                @elseif($item->dispense_status === 'partially_dispensed')
                                    <span class="badge bg-info text-dark">Partial</span>
                                @elseif($item->dispense_status === 'cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                @if($canDispense)
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#dispenseModal{{ $item->id }}">
                                            <i class="bi bi-check-circle"></i> Dispense
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#declineModal{{ $item->id }}" title="Decline Item">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted small">No action required</span>
                                @endif
                            </td>
                        </tr>

                        @if($canDispense)
                            <!-- Modal: Dispense Line Item -->
                            <div class="modal fade" id="dispenseModal{{ $item->id }}" tabindex="-1" aria-labelledby="dispenseModalLabel{{ $item->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <form method="POST" action="{{ route('admin.pharmacy.dispense', $item->id) }}">
                                            @csrf
                                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold text-dark" id="dispenseModalLabel{{ $item->id }}">
                                                    <i class="bi bi-capsule text-success me-1"></i> Dispense Medication
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="text-muted extra-small fw-bold">Medication Line Item</label>
                                                    <div class="fw-bold text-dark fs-6">{{ $item->medication_name }}</div>
                                                    <div class="text-muted small">Prescribed: {{ $item->quantity_prescribed }} &bull; Already Dispensed: {{ $item->quantity_dispensed }} &bull; Outstanding: {{ $item->remaining_quantity }}</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="qty{{ $item->id }}" class="form-label fw-bold small">Quantity to Dispense Now <span class="text-danger">*</span></label>
                                                    <input type="number" name="quantity_to_dispense" id="qty{{ $item->id }}" class="form-control" min="1" max="{{ $item->remaining_quantity }}" value="{{ min($item->remaining_quantity, $availableStock > 0 ? $availableStock : $item->remaining_quantity) }}" required>
                                                    <div class="form-text small">Maximum outstanding: {{ $item->remaining_quantity }} units. Current non-expired stock: {{ $availableStock }} units.</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="notes{{ $item->id }}" class="form-label fw-bold small">Pharmacist Verification Notes</label>
                                                    <input type="text" name="pharmacist_notes" id="notes{{ $item->id }}" class="form-control form-control-sm" placeholder="e.g. Counseled patient on taking with meals, verified no allergy">
                                                </div>

                                                <div class="alert alert-light border small text-muted mb-0">
                                                    <i class="bi bi-info-circle text-primary me-1"></i>
                                                    Stock will be automatically deducted from eligible non-expired batches ordered by earliest expiry date (FEFO).
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Confirm & Dispense</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal: Decline Line Item -->
                            <div class="modal fade" id="declineModal{{ $item->id }}" tabindex="-1" aria-labelledby="declineModalLabel{{ $item->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <form method="POST" action="{{ route('admin.pharmacy.decline', $item->id) }}">
                                            @csrf
                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold text-dark" id="declineModalLabel{{ $item->id }}">
                                                    <i class="bi bi-x-circle text-danger me-1"></i> Decline to Dispense
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <p class="text-muted small mb-3">
                                                    Document the pharmaceutical or clinical reason why <strong>{{ $item->medication_name }}</strong> cannot be dispensed.
                                                </p>
                                                <div class="mb-3">
                                                    <label for="reason{{ $item->id }}" class="form-label fw-bold small">Mandatory Clinical / Stock Reason <span class="text-danger">*</span></label>
                                                    <textarea name="non_dispense_reason" id="reason{{ $item->id }}" rows="3" class="form-control form-control-sm" placeholder="e.g. Stock completely depleted, consultation with prescriber recommended due to allergy risk" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Confirm Decline</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dispensing Audit History -->
    @if($prescription->items->pluck('dispensings')->flatten()->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff text-success"></i> Dispensing Audit Trail for this Prescription
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Dispensation #</th>
                            <th>Medication</th>
                            <th>Batch #</th>
                            <th>Quantity</th>
                            <th>Dispensed By</th>
                            <th>Timestamp</th>
                            <th class="pe-4">Pharmacist Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prescription->items as $item)
                            @foreach($item->dispensings as $dsp)
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-dark">{{ $dsp->dispensation_number }}</td>
                                    <td>{{ $dsp->medicine->brand_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $dsp->batch->batch_number ?? 'N/A' }}</span>
                                    </td>
                                    <td class="fw-bold text-success">{{ $dsp->quantity_dispensed }} units</td>
                                    <td>{{ $dsp->dispensedByUser->name ?? 'Pharmacist' }}</td>
                                    <td class="small text-muted">{{ $dsp->dispensed_at ? $dsp->dispensed_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                                    <td class="pe-4 text-muted small">{{ $dsp->pharmacist_notes ?? '—' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
