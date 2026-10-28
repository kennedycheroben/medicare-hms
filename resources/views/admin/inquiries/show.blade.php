@extends('layouts.admin')

@section('title', 'Enquiry #' . $inquiry->ticket_number)

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.inquiries.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Enquiries
                </a>
                <span class="badge bg-light text-dark font-monospace border px-2 py-1 fs-6">Ticket #{{ $inquiry->ticket_number }}</span>
                @if($inquiry->status === 'new')
                    <span class="badge bg-warning-subtle text-warning fw-semibold px-2.5 py-1">New / Unprocessed</span>
                @elseif($inquiry->status === 'in_review')
                    <span class="badge bg-info-subtle text-info fw-semibold px-2.5 py-1">In Review</span>
                @else
                    <span class="badge bg-success-subtle text-success fw-semibold px-2.5 py-1">Resolved</span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">{{ $inquiry->subject }}</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="mailto:{{ $inquiry->email }}?subject=RE: [Ticket #{{ $inquiry->ticket_number }}] {{ rawurlencode($inquiry->subject) }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-reply"></i> Reply via Email
            </a>
            @if($inquiry->phone)
                <a href="tel:{{ $inquiry->phone }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-telephone"></i> Call Sender
                </a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Content: Message & Details -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-envelope-open text-primary"></i> Submitted Enquiry Message
                    </h5>
                    <span class="text-muted small">
                        Received {{ $inquiry->created_at->format('M d, Y \a\t H:i') }} ({{ $inquiry->created_at->diffForHumans() }})
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-muted small mb-1 fw-bold">Department Destination</label>
                        <div class="text-dark fw-semibold">
                            <i class="bi bi-building me-1 text-primary"></i> {{ $inquiry->department ?: 'General Reception / Unspecified' }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small mb-1 fw-bold">Subject / Purpose</label>
                        <div class="p-3 bg-light rounded-3 text-dark fw-bold fs-6 border">{{ $inquiry->subject }}</div>
                    </div>

                    <div>
                        <label class="form-label text-muted small mb-1 fw-bold">Full Message Content</label>
                        <div class="p-4 bg-light rounded-3 text-dark border" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $inquiry->message }}</div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail of Inquiry Actions -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i> Action History & Status Changes
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Timestamp</th>
                                    <th>Action</th>
                                    <th>Staff Member</th>
                                    <th class="pe-4">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td class="ps-4 small text-muted font-monospace">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                        <td><span class="badge bg-light text-dark font-monospace">{{ $log->action }}</span></td>
                                        <td>{{ $log->user?->name ?? $log->actor_email ?? 'Staff User' }}</td>
                                        <td class="pe-4 small text-muted">
                                            @if($log->details)
                                                @foreach($log->details as $k => $v)
                                                    <span class="d-block">{{ $k }}: <strong>{{ is_array($v) ? json_encode($v) : $v }}</strong></span>
                                                @endforeach
                                            @else
                                                &mdash;
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">
                                            No status change records logged yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Sender Profile & Status Management -->
        <div class="col-lg-4">
            <!-- Status Update Action Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-toggles text-primary"></i> Update Ticket Status
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.inquiries.status', $inquiry->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold small">Current Resolution State <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="new" @selected($inquiry->status === 'new')>New (Unprocessed)</option>
                                <option value="in_review" @selected($inquiry->status === 'in_review')>In Review (Under Investigation)</option>
                                <option value="resolved" @selected($inquiry->status === 'resolved')>Resolved (Completed & Closed)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label fw-bold small">Audit / Resolution Notes (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control form-control-sm" rows="3" placeholder="Enter follow-up steps taken, phone consultation summary, or patient resolution details"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check2-circle me-1"></i> Save Status Change
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sender Metadata Card -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person-lines-fill text-primary"></i> Sender Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted small d-block">Sender Name</span>
                        <span class="fw-bold text-dark fs-6">{{ $inquiry->name }}</span>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Email Address</span>
                        <a href="mailto:{{ $inquiry->email }}" class="text-primary text-decoration-none fw-semibold">
                            <i class="bi bi-envelope me-1"></i>{{ $inquiry->email }}
                        </a>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Phone Number</span>
                        @if($inquiry->phone)
                            <a href="tel:{{ $inquiry->phone }}" class="text-dark text-decoration-none">
                                <i class="bi bi-telephone me-1 text-primary"></i>{{ $inquiry->phone }}
                            </a>
                        @else
                            <span class="text-muted">Not provided</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">Submission Origin</span>
                        <span class="text-muted extra-small font-monospace">IP: {{ $inquiry->ip_address ?: 'Unknown' }}</span>
                    </div>

                    <div class="pt-3 border-top">
                        <span class="text-muted small d-block">Quick Action</span>
                        <a href="{{ route('appointments.create', ['name' => $inquiry->name, 'email' => $inquiry->email, 'phone' => $inquiry->phone]) }}" target="_blank" class="btn btn-outline-info btn-sm w-100 mt-2">
                            <i class="bi bi-calendar-plus me-1"></i> Book Appointment for Sender
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
