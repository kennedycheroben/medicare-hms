@extends('layouts.admin')

@section('title', 'My Notifications')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Notification Inbox</h1>
            <p class="text-muted small mb-0">Role-targeted operational alerts, assignments, and workflow notices.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read ({{ $unreadCount }})
                    </button>
                </form>
            @endif
            @if(Auth::user()->hasRole(['super_admin', 'hospital_director']))
                <a href="{{ route('admin.notifications.oversight') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-bar-chart-steps me-1"></i> Hospital Oversight
                </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Category Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <a href="{{ route('admin.notifications.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' && !$selectedCategory ? 'btn-primary' : 'btn-light border' }} rounded-pill">
                        All
                    </a>
                    <a href="{{ route('admin.notifications.index', ['status' => 'unread']) }}" class="btn btn-sm {{ $status === 'unread' ? 'btn-primary' : 'btn-light border' }} rounded-pill position-relative">
                        Unread
                        @if($unreadCount > 0)
                            <span class="badge rounded-pill bg-danger ms-1">{{ $unreadCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.notifications.index', ['status' => 'read']) }}" class="btn btn-sm {{ $status === 'read' ? 'btn-primary' : 'btn-light border' }} rounded-pill">
                        Read
                    </a>
                    <span class="text-muted mx-1">|</span>
                    <a href="{{ route('admin.notifications.index', ['category' => 'appointment', 'status' => $status]) }}" class="btn btn-sm {{ $selectedCategory === 'appointment' ? 'btn-primary' : 'btn-light border' }} rounded-pill">
                        <i class="bi bi-calendar-check me-1"></i> Appointments ({{ $categoryCounts['appointment'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.notifications.index', ['category' => 'doctor_profile', 'status' => $status]) }}" class="btn btn-sm {{ $selectedCategory === 'doctor_profile' ? 'btn-primary' : 'btn-light border' }} rounded-pill">
                        <i class="bi bi-person-badge me-1"></i> Doctor Profiles ({{ $categoryCounts['doctor_profile'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.notifications.index', ['category' => 'pharmacy', 'status' => $status]) }}" class="btn btn-sm {{ $selectedCategory === 'pharmacy' ? 'btn-primary' : 'btn-light border' }} rounded-pill">
                        <i class="bi bi-capsule me-1"></i> Pharmacy ({{ $categoryCounts['pharmacy'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.notifications.index', ['category' => 'contact', 'status' => $status]) }}" class="btn btn-sm {{ $selectedCategory === 'contact' ? 'btn-primary' : 'btn-light border' }} rounded-pill">
                        <i class="bi bi-envelope me-1"></i> Enquiries ({{ $categoryCounts['contact'] ?? 0 }})
                    </a>
                </div>
                @if($selectedCategory || $status !== 'all')
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-link text-decoration-none">
                        Clear Filters
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Notification Cards List -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="list-group list-group-flush rounded-4">
            @forelse($notifications as $item)
                @php
                    $isUnread = $item->read_at === null;
                    $data = $item->data;
                    $cat = $data['category'] ?? 'system';
                    $badgeClass = match($cat) {
                        'appointment' => 'bg-info-subtle text-info border-info-subtle',
                        'doctor_profile' => 'bg-primary-subtle text-primary border-primary-subtle',
                        'pharmacy' => 'bg-danger-subtle text-danger border-danger-subtle',
                        'contact' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                        default => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                    };
                    $iconClass = match($cat) {
                        'appointment' => 'bi-calendar-check text-info',
                        'doctor_profile' => 'bi-person-badge text-primary',
                        'pharmacy' => 'bi-exclamation-triangle-fill text-danger',
                        'contact' => 'bi-envelope text-warning',
                        default => 'bi-bell text-secondary',
                    };
                @endphp
                <div class="list-group-item p-4 {{ $isUnread ? 'bg-light bg-opacity-75 border-start border-4 border-primary' : '' }}">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 bg-white shadow-sm border mt-1">
                                <i class="bi {{ $iconClass }} fs-4"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge border {{ $badgeClass }} text-uppercase extra-small fw-bold">
                                        {{ str_replace('_', ' ', $cat) }}
                                    </span>
                                    @if($isUnread)
                                        <span class="badge bg-primary rounded-pill extra-small">New</span>
                                    @endif
                                    <span class="text-muted extra-small">
                                        <i class="bi bi-clock me-1"></i> {{ $item->created_at->diffForHumans() }} ({{ $item->created_at->format('M d, H:i') }})
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">{{ $data['title'] ?? 'Notification' }}</h6>
                                <p class="text-muted small mb-0">{{ $data['message'] ?? '' }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-auto">
                            @if(!empty($data['action_url']))
                                <a href="{{ route('admin.notifications.read', [$item->id, 'navigate' => 1]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-arrow-up-right-square me-1"></i> Open Record
                                </a>
                            @endif
                            @if($isUnread)
                                <form method="POST" action="{{ route('admin.notifications.read', $item->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light border rounded-pill" title="Mark as read">
                                        <i class="bi bi-check2"></i> Mark Read
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-light text-muted border extra-small">
                                    <i class="bi bi-check2-circle text-success me-1"></i> Read
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-3 text-secondary opacity-50"></i>
                    <h5 class="fw-bold text-dark mb-1">No Notifications Found</h5>
                    <p class="small text-muted mb-0">Your notification inbox is clean and up to date.</p>
                </div>
            @endforelse
        </div>
        @if($notifications->hasPages())
            <div class="card-footer bg-white border-0 p-3">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
