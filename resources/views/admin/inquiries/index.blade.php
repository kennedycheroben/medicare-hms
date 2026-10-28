@extends('layouts.admin')

@section('title', 'Patient & Public Enquiries')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Public & Patient Enquiries</h1>
            <p class="text-muted small mb-0">Manage incoming correspondence, general hospital inquiries, triage requests, and patient service feedback.</p>
        </div>
        <div>
            <a href="{{ route('contact') }}" target="_blank" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-box-arrow-up-right"></i> Public Contact Portal
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistics Overview -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Inquiries</span>
                    <i class="bi bi-chat-left-text text-primary fs-5"></i>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['total']) }}</div>
                <div class="extra-small text-muted mt-1">All received messages</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">New Messages</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5">Awaiting Triage</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['new']) }}</div>
                <div class="extra-small text-muted mt-1">Unread / unprocessed enquiries</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">In Review</span>
                    <span class="badge bg-info-subtle text-info rounded-pill px-2.5">Under Investigation</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['in_review']) }}</div>
                <div class="extra-small text-muted mt-1">Assigned for follow-up</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Resolved</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2.5">Closed</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['resolved']) }}</div>
                <div class="extra-small text-muted mt-1">Addressed and closed tickets</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.inquiries.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search ticket #, name, email, subject..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm bg-light">
                        <option value="">All Statuses</option>
                        <option value="new" @selected($currentStatus === 'new')>New (Unprocessed)</option>
                        <option value="in_review" @selected($currentStatus === 'in_review')>In Review</option>
                        <option value="resolved" @selected($currentStatus === 'resolved')>Resolved</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="department" class="form-select form-select-sm bg-light">
                        <option value="">All Departments</option>
                        <option value="General Inquiries" @selected($department === 'General Inquiries')>General Inquiries</option>
                        <option value="Patient Support" @selected($department === 'Patient Support')>Patient Support</option>
                        <option value="Billing & Insurance" @selected($department === 'Billing & Insurance')>Billing & Insurance</option>
                        <option value="Medical Records" @selected($department === 'Medical Records')>Medical Records</option>
                        <option value="Appointments" @selected($department === 'Appointments')>Appointments</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    @if($search || $currentStatus || $department)
                        <a href="{{ route('admin.inquiries.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Inquiries Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Ticket</th>
                            <th>Sender</th>
                            <th>Subject / Department</th>
                            <th>Status</th>
                            <th>Received At</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inquiries as $inquiry)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark font-monospace border">{{ $inquiry->ticket_number }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $inquiry->name }}</div>
                                    <div class="extra-small text-muted">
                                        <i class="bi bi-envelope me-1"></i>{{ $inquiry->email }}
                                        @if($inquiry->phone)
                                            <span class="ms-2"><i class="bi bi-telephone me-1"></i>{{ $inquiry->phone }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ Str::limit($inquiry->subject, 40) }}</div>
                                    <div class="extra-small text-muted">
                                        <i class="bi bi-building me-1"></i>{{ $inquiry->department ?: 'General Desk' }}
                                    </div>
                                </td>
                                <td>
                                    @if($inquiry->status === 'new')
                                        <span class="badge bg-warning-subtle text-warning fw-semibold px-2 py-1">New</span>
                                    @elseif($inquiry->status === 'in_review')
                                        <span class="badge bg-info-subtle text-info fw-semibold px-2 py-1">In Review</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1">Resolved</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $inquiry->created_at->format('M d, Y') }}</div>
                                    <div class="extra-small text-muted">{{ $inquiry->created_at->format('H:i') }} ({{ $inquiry->created_at->diffForHumans() }})</div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('admin.inquiries.show', $inquiry->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-eye"></i> View & Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No contact enquiries found matching the criteria.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($inquiries->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $inquiries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
