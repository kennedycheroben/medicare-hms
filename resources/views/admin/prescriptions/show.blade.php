@extends('layouts.admin')

@section('title', 'Prescription ' . $prescription->prescription_number)

@section('content')
<div class="container-fluid p-0">
    <!-- Header Breadcrumb & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            @if(Auth::user()->hasRole('pharmacist'))
                <a href="{{ route('admin.pharmacy.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Pharmacy Queue
                </a>
            @else
                <a href="{{ route('admin.consultations.show', $prescription->consultation_id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Consultation Workspace
                </a>
            @endif
            <div>
                <h1 class="h4 fw-bold text-dark mb-0">Prescription Order #{{ $prescription->prescription_number }}</h1>
                <span class="text-muted small">Issued on {{ $prescription->created_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($prescription->status === 'dispensed')
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                    <i class="bi bi-check-all me-1"></i> Fully Dispensed
                </span>
            @elseif($prescription->status === 'partially_dispensed')
                <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 fs-6">
                    <i class="bi bi-pie-chart-fill me-1"></i> Partially Dispensed
                </span>
            @elseif($prescription->status === 'finalized')
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                    <i class="bi bi-send-check me-1"></i> Queued at Pharmacy
                </span>
            @elseif($prescription->status === 'cancelled')
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
                    <i class="bi bi-x-circle-fill me-1"></i> Cancelled
                </span>
            @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 fs-6">
                    <i class="bi bi-pencil-fill me-1"></i> Draft
                </span>
            @endif

            @if(Auth::user()->hasRole('pharmacist') && in_array($prescription->status, ['finalized', 'partially_dispensed'], true))
                <a href="{{ route('admin.pharmacy.prescriptions.show', $prescription->id) }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-box-arrow-in-right"></i> Open Dispensing Workspace
                </a>
            @endif

            @if($prescription->status !== 'dispensed' && $prescription->status !== 'cancelled')
                <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#cancelPrescriptionModal">
                    <i class="bi bi-x-circle"></i> Cancel Prescription
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

    <!-- Patient Context & Allergy Alert Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 48px; height: 48px;">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <a href="{{ route('admin.patients.show', $prescription->patient_id) }}" class="text-decoration-none text-dark">
                                    {{ $prescription->patient->full_name }}
                                </a>
                            </h5>
                            <div class="text-muted small">
                                MRN: <span class="font-monospace fw-semibold">{{ $prescription->patient->mrn }}</span> &bull;
                                Age: {{ $prescription->patient->age ?? 'N/A' }} yrs &bull;
                                Gender: {{ ucfirst($prescription->patient->gender) }} &bull;
                                Blood Group: <strong class="text-danger">{{ $prescription->patient->blood_group ?? 'N/A' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    @if(!empty($prescription->patient->allergies))
                        <div class="alert alert-danger mb-0 p-2 border-danger-subtle small">
                            <strong class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Patient Allergies:</strong>
                            <span class="text-dark">{{ $prescription->patient->allergies }}</span>
                        </div>
                    @else
                        <div class="alert alert-light mb-0 p-2 border text-muted small">
                            <i class="bi bi-shield-check text-success me-1"></i> No recorded allergies in patient file.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Prescription Metadata & Prescriber -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle text-primary me-1"></i> Prescription Details</h6>
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Prescription Number:</span>
                        <span class="font-monospace fw-bold text-dark">{{ $prescription->prescription_number }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Prescribing Clinician:</span>
                        <span class="fw-semibold text-dark">{{ $prescription->prescriber->name ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Visit / Encounter:</span>
                        <span class="text-dark">{{ $prescription->visit->visit_number ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Clinical Notes:</span>
                        <span class="text-dark">{{ $prescription->clinical_notes ?? 'None provided' }}</span>
                    </div>
                    @if($prescription->isCancelled())
                        <div class="alert alert-danger mt-3 mb-0 p-2 small">
                            <strong>Cancelled on {{ $prescription->cancelled_at->format('M d, Y H:i') }}</strong> by {{ $prescription->cancelledBy->name ?? 'Staff' }}.<br>
                            Reason: <em>{{ $prescription->cancellation_reason }}</em>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-1"></i> Lifecycle Status</h6>
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Created Timestamp:</span>
                        <span class="text-dark">{{ $prescription->created_at->format('M d, Y H:i:s') }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Finalized / Transmitted:</span>
                        <span class="text-dark">{{ $prescription->finalized_at ? $prescription->finalized_at->format('M d, Y H:i:s') : 'Pending Finalization' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Current Dispense State:</span>
                        <span class="fw-bold">{{ ucfirst(str_replace('_', ' ', $prescription->status)) }}</span>
                    </div>

                    @if($prescription->isDraft() && $prescription->prescriber_id === Auth::id())
                        <div class="mt-3">
                            <form method="POST" action="{{ route('admin.prescriptions.finalize', $prescription->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-send-fill me-1"></i> Sign & Transmit to Pharmacy Dispensary
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Prescribed Line Items Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-capsule-pill text-primary"></i> Prescribed Medication Line Items
                <span class="badge bg-primary rounded-pill">{{ $prescription->items->count() }}</span>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Medication Name</th>
                        <th>Dosage & Route</th>
                        <th>Frequency & Duration</th>
                        <th>Prescribed Qty</th>
                        <th>Dispensed Qty</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th class="pe-4">Instructions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescription->items as $index => $item)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <strong class="text-dark">{{ $item->medication_name }}</strong>
                                @if($item->medicine)
                                    <div class="text-muted extra-small">Catalogue Code: {{ $item->medicine->code }}</div>
                                @endif
                                @if($item->non_dispense_reason)
                                    <div class="text-danger small mt-1"><em>Reason: {{ $item->non_dispense_reason }}</em></div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $item->dosage }}</div>
                                <span class="badge bg-light text-dark border extra-small">{{ ucfirst($item->route) }}</span>
                            </td>
                            <td>
                                <div>{{ $item->frequency }}</div>
                                <div class="text-muted extra-small">{{ $item->duration }}</div>
                            </td>
                            <td class="fw-bold text-dark">{{ $item->quantity_prescribed }}</td>
                            <td class="fw-bold text-success">{{ $item->quantity_dispensed }}</td>
                            <td class="fw-bold {{ $item->remaining_quantity > 0 ? 'text-primary' : 'text-muted' }}">
                                {{ $item->remaining_quantity }}
                            </td>
                            <td>
                                @if($item->dispense_status === 'dispensed')
                                    <span class="badge bg-success"><i class="bi bi-check"></i> Dispensed</span>
                                @elseif($item->dispense_status === 'partially_dispensed')
                                    <span class="badge bg-info text-dark">Partial</span>
                                @elseif($item->dispense_status === 'cancelled')
                                    <span class="badge bg-danger">Declined/Cancelled</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="pe-4 text-muted small">{{ $item->instructions ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fulfillment Audit Log / Dispensing Records -->
    @if($prescription->dispensings->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff text-success"></i> Pharmacy Dispensing Audit Log
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Dispensation #</th>
                            <th>Medication Item</th>
                            <th>Batch #</th>
                            <th>Qty Dispensed</th>
                            <th>Dispensed By</th>
                            <th>Timestamp</th>
                            <th class="pe-4">Pharmacist Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prescription->dispensings as $dsp)
                            <tr>
                                <td class="ps-4 font-monospace fw-bold text-dark">{{ $dsp->dispensation_number }}</td>
                                <td>{{ $dsp->medicine->brand_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $dsp->batch->batch_number ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="fw-bold text-success">{{ $dsp->quantity_dispensed }} units</td>
                                <td>{{ $dsp->dispensedByUser->name ?? 'Pharmacist' }}</td>
                                <td class="small text-muted">{{ $dsp->dispensed_at ? $dsp->dispensed_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                                <td class="pe-4 text-muted small">{{ $dsp->pharmacist_notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Modal: Cancel Prescription -->
<div class="modal fade" id="cancelPrescriptionModal" tabindex="-1" aria-labelledby="cancelPrescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.prescriptions.cancel', $prescription->id) }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="cancelPrescriptionModalLabel">
                        <i class="bi bi-x-circle text-danger me-1"></i> Cancel Prescription Order
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Cancelling will permanently prevent further dispensing on any unfulfilled items. A mandatory reason is required for clinical and pharmaceutical auditing.
                    </p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label fw-bold small">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" id="cancellation_reason" rows="3" class="form-control form-control-sm" placeholder="e.g. Patient developed allergy, dose adjustment needed, alternative therapy chosen" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
