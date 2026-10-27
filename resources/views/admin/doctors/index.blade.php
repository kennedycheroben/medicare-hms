@extends('layouts.admin')

@section('title', 'Physician Profile & Publication Management')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Physician Directory & Publication</h1>
            <p class="text-muted small mb-0">Verify physician credentials, manage public website publication, and moderate patient feedback.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.doctors.reviews') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-chat-heart"></i>
                <span>Patient Reviews</span>
                @if($pendingReviewsCount > 0)
                    <span class="badge bg-danger rounded-pill">{{ $pendingReviewsCount }}</span>
                @endif
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.doctors.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ $search }}" class="form-control bg-light" placeholder="Search by name, specialty...">
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-select bg-light">
                        <option value="">-- All Departments --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select bg-light">
                        <option value="">-- All Statuses --</option>
                        <option value="pending_review" {{ $status === 'pending_review' ? 'selected' : '' }}>Pending Review Queue {{ $pendingProfilesCount > 0 ? "({$pendingProfilesCount})" : '' }}</option>
                        <option value="needs_revision" {{ $status === 'needs_revision' ? 'selected' : '' }}>Needs Revision</option>
                        <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published to Website</option>
                        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="unpublished" {{ $status === 'unpublished' ? 'selected' : '' }}>Unpublished</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                    @if($search || $departmentId || $status)
                        <a href="{{ route('admin.doctors.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Doctors Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Doctor</th>
                        <th>Department</th>
                        <th>Qualifications</th>
                        <th>Availability</th>
                        <th>Review Status</th>
                        <th>Rating</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $doc)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $doc->imageUrl() }}" alt="{{ $doc->name }}" class="rounded-circle" style="width: 44px; height: 44px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $doc->name }}</div>
                                        <div class="small text-muted">{{ $doc->specialty }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $doc->department->name ?? 'General' }}</span>
                            </td>
                            <td class="small text-muted">{{ $doc->qualifications }}</td>
                            <td>
                                @if($doc->is_available)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Available</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">Unavailable</span>
                                @endif
                            </td>
                            <td>
                                @if($doc->status === 'pending_review')
                                    <span class="badge bg-warning text-dark border border-warning-subtle">
                                        <i class="bi bi-hourglass-split me-1"></i> Pending Review
                                    </span>
                                @elseif($doc->status === 'needs_revision')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Needs Revision
                                    </span>
                                    @if($doc->publication_notes)
                                        <div class="extra-small text-muted text-truncate" style="max-width: 140px;" title="{{ $doc->publication_notes }}">
                                            {{ $doc->publication_notes }}
                                        </div>
                                    @endif
                                @elseif($doc->is_published)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-globe me-1"></i> Published
                                    </span>
                                    <div class="extra-small text-muted">
                                        Approved {{ $doc->published_at ? $doc->published_at->format('M d, Y') : '' }}
                                    </div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-pencil me-1"></i> Draft
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1 text-warning">
                                    <i class="bi bi-star-fill small"></i>
                                    <span class="fw-bold text-dark">{{ number_format($doc->rating, 1) }}</span>
                                    <span class="extra-small text-muted">({{ $doc->approved_reviews_count }})</span>
                                </div>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.doctors.edit', $doc->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    @if($doc->status === 'pending_review')
                                        <form method="POST" action="{{ route('admin.doctors.approve', $doc->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success text-white" title="Approve profile and publish to public website">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#returnModal{{ $doc->id }}" title="Return for correction with feedback">
                                            <i class="bi bi-arrow-return-left"></i> Return
                                        </button>

                                        <!-- Return Modal -->
                                        <div class="modal fade" id="returnModal{{ $doc->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog text-start">
                                                <form method="POST" action="{{ route('admin.doctors.return', $doc->id) }}">
                                                    @csrf
                                                    <div class="modal-content rounded-4 border-0 shadow">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Return Profile for Correction</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-2">Provide feedback explaining what {{ $doc->name }} needs to adjust or verify before publication.</p>
                                                            <label class="form-label small fw-bold">Feedback / Revision Instructions <span class="text-danger">*</span></label>
                                                            <textarea name="publication_notes" rows="3" class="form-control" required placeholder="e.g. Please clarify board certification details and upload a higher-resolution portrait photo."></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-danger text-white">Return with Feedback</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @elseif($doc->is_published)
                                        <form method="POST" action="{{ route('admin.doctors.unpublish', $doc->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Hide profile from website">
                                                <i class="bi bi-eye-slash"></i> Unpublish
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.doctors.approve', $doc->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success text-white" title="Approve and publish to public website">
                                                <i class="bi bi-check-circle"></i> Publish
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No physician profiles found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($doctors->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $doctors->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
