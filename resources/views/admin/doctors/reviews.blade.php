@extends('layouts.admin')

@section('title', 'Patient Reviews Moderation')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.doctors.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Doctor Directory
            </a>
            <div>
                <h1 class="h3 fw-bold text-dark mb-0">Patient Review Moderation</h1>
                <span class="text-muted small">Moderate verified post-consultation feedback. Only approved reviews appear on doctor profiles.</span>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Status Tabs -->
    <div class="d-flex gap-2 mb-4 border-bottom pb-2">
        <a href="{{ route('admin.doctors.reviews', ['status' => 'pending_moderation']) }}" class="btn btn-sm {{ $status === 'pending_moderation' ? 'btn-primary' : 'btn-light border' }}">
            Pending Review <span class="badge bg-danger ms-1">{{ $stats['pending'] }}</span>
        </a>
        <a href="{{ route('admin.doctors.reviews', ['status' => 'approved']) }}" class="btn btn-sm {{ $status === 'approved' ? 'btn-primary' : 'btn-light border' }}">
            Approved &amp; Published <span class="badge bg-secondary ms-1">{{ $stats['approved'] }}</span>
        </a>
        <a href="{{ route('admin.doctors.reviews', ['status' => 'rejected']) }}" class="btn btn-sm {{ $status === 'rejected' ? 'btn-primary' : 'btn-light border' }}">
            Rejected <span class="badge bg-secondary ms-1">{{ $stats['rejected'] }}</span>
        </a>
        <a href="{{ route('admin.doctors.reviews', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-light border' }}">
            All Reviews
        </a>
    </div>

    <!-- Reviews Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Submitted</th>
                        <th>Physician</th>
                        <th>Verified Patient &amp; Encounter</th>
                        <th>Rating</th>
                        <th style="width: 35%;">Feedback Comment</th>
                        <th>Public Identity</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td class="ps-4 small text-muted">
                                {{ $review->created_at->format('M d, Y') }}
                                <div class="extra-small">{{ $review->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $review->doctor->name ?? 'N/A' }}</div>
                                <div class="extra-small text-muted">{{ $review->doctor->department->name ?? '' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $review->patient->first_name ?? '' }} {{ $review->patient->last_name ?? '' }}</div>
                                <div class="extra-small text-muted font-monospace">MRN: {{ $review->patient->mrn ?? 'N/A' }}</div>
                                <div class="extra-small text-muted">Visit #{{ $review->visit->visit_number ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="text-warning fw-bold">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }} small"></i>
                                    @endfor
                                    <span class="text-dark small ms-1">{{ $review->rating }}/5</span>
                                </div>
                            </td>
                            <td>
                                <div class="small text-dark p-2 rounded bg-light border">
                                    "{{ e($review->comment) }}"
                                </div>
                            </td>
                            <td>
                                @if($review->public_consent && $review->display_name_type === 'full_name')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-person-check me-1"></i> Full Name Consented
                                    </span>
                                @elseif($review->display_name_type === 'initials')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="bi bi-person me-1"></i> Initials
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-shield-shaded me-1"></i> Anonymous
                                    </span>
                                @endif
                                <div class="extra-small text-muted mt-1">
                                    Displays: <strong>{{ $review->public_author_name }}</strong>
                                </div>
                            </td>
                            <td>
                                @if($review->status === 'approved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Approved</span>
                                @elseif($review->status === 'rejected')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rejected</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending Moderation</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                @if($review->status === 'pending_moderation')
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="{{ route('admin.doctors.reviews.approve', $review->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success text-white" title="Approve for public view">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $review->id }}">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </div>

                                    <!-- Modal: Reject Review -->
                                    <div class="modal fade" id="rejectModal{{ $review->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form method="POST" action="{{ route('admin.doctors.reviews.reject', $review->id) }}">
                                                    @csrf
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fw-bold text-dark">Reject Feedback Submission</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <label class="form-label fw-bold small">Documented Moderation Rationale <span class="text-danger">*</span></label>
                                                        <textarea name="moderation_notes" rows="3" class="form-control form-control-sm" placeholder="Specify clinical/administrative reason for rejection (profanity, PHI violation, harassment)..." required></textarea>
                                                    </div>
                                                    <div class="modal-footer border-top">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="extra-small text-muted">
                                        Moderated by {{ $review->moderator->name ?? 'Admin' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No patient reviews found in this status queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reviews->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
