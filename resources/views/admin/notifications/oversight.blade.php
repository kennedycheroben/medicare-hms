@extends('layouts.admin')

@section('title', 'Hospital Notification Oversight')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Hospital Notification Oversight</h1>
            <p class="text-muted small mb-0">Centralized administrative monitoring of workflow alerts and staff notification delivery across categories.</p>
        </div>
        <div>
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-inbox me-1"></i> My Personal Inbox
            </a>
        </div>
    </div>

    <!-- Category Overview Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary">
                        <i class="bi bi-send-check fs-4"></i>
                    </div>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">Dispatched</div>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalDispatched) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-bell fs-4"></i>
                    </div>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">Unread</div>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalUnread) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-info-subtle text-info">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">Appointments</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $categoryBreakdown['appointment'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-danger-subtle text-danger">
                        <i class="bi bi-capsule fs-4"></i>
                    </div>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">Pharmacy</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $categoryBreakdown['pharmacy'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary">
                        <i class="bi bi-person-badge fs-4"></i>
                    </div>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">Doctor Profiles</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $categoryBreakdown['doctor_profile'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-success-subtle text-success">
                        <i class="bi bi-envelope fs-4"></i>
                    </div>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">Enquiries</div>
                        <h4 class="fw-bold text-dark mb-0">{{ $categoryBreakdown['contact'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.notifications.oversight') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="category" class="form-select bg-light">
                        <option value="">-- All Categories --</option>
                        <option value="appointment" {{ $selectedCategory === 'appointment' ? 'selected' : '' }}>Appointments</option>
                        <option value="doctor_profile" {{ $selectedCategory === 'doctor_profile' ? 'selected' : '' }}>Doctor Profiles</option>
                        <option value="pharmacy" {{ $selectedCategory === 'pharmacy' ? 'selected' : '' }}>Pharmacy & Stock</option>
                        <option value="contact" {{ $selectedCategory === 'contact' ? 'selected' : '' }}>Contact Inquiries</option>
                        <option value="clinical" {{ $selectedCategory === 'clinical' ? 'selected' : '' }}>Clinical Care</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select bg-light">
                        <option value="">-- All Statuses --</option>
                        <option value="unread" {{ $status === 'unread' ? 'selected' : '' }}>Unread by Recipient</option>
                        <option value="read" {{ $status === 'read' ? 'selected' : '' }}>Read by Recipient</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter Oversight</button>
                    @if($selectedCategory || $status)
                        <a href="{{ route('admin.notifications.oversight') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Dispatched At</th>
                        <th>Category</th>
                        <th>Recipient</th>
                        <th>Title & Summary</th>
                        <th class="pe-4 text-end">Recipient Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $item)
                        @php
                            $cat = $item->data['category'] ?? 'system';
                            $badgeClass = match($cat) {
                                'appointment' => 'bg-info-subtle text-info border-info-subtle',
                                'doctor_profile' => 'bg-primary-subtle text-primary border-primary-subtle',
                                'pharmacy' => 'bg-danger-subtle text-danger border-danger-subtle',
                                'contact' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                            };
                        @endphp
                        <tr>
                            <td class="ps-4 small text-muted">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') }}
                                <div class="extra-small text-muted">{{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}</div>
                            </td>
                            <td>
                                <span class="badge border {{ $badgeClass }} text-uppercase extra-small fw-bold">
                                    {{ str_replace('_', ' ', $cat) }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">{{ $item->recipient_name }}</div>
                                <span class="badge bg-light text-dark border extra-small">{{ ucfirst(str_replace('_', ' ', $item->recipient_role)) }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small">{{ $item->data['title'] ?? 'Notification' }}</div>
                                <div class="text-muted extra-small text-truncate" style="max-width: 450px;">
                                    {{ $item->data['message'] ?? '' }}
                                </div>
                            </td>
                            <td class="pe-4 text-end">
                                @if($item->read_at)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check2-all me-1"></i> Read {{ \Carbon\Carbon::parse($item->read_at)->format('M d, H:i') }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-clock me-1"></i> Unread
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-5 text-center text-muted">
                                <i class="bi bi-bar-chart-steps fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">No Dispatched Notifications Found</h6>
                                <p class="small text-muted mb-0">No records match the current category or status filter criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notifications->hasPages())
            <div class="card-footer bg-white border-0 p-3">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
