@extends('layouts.admin')

@section('title', 'Administrative Dashboard')

@section('content')
<div class="container-fluid p-0">

    <!-- Page Header & Breadcrumbs -->
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1">Hospital Operations &amp; Clinical Dashboard</h1>
            <nav class="breadcrumb mb-0">
                <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item text-decoration-none">MediCare HMS</a>
                <span class="breadcrumb-item active">Operational Overview</span>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-secondary border px-3 py-2 shadow-xs d-none d-sm-inline-flex align-items-center gap-1">
                <i class="bi bi-shield-check text-success"></i>
                <span>Staff Session Active</span>
            </span>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-clockwise"></i>
                <span class="d-none d-sm-inline">Refresh</span>
            </a>
        </div>
    </div>

    <!-- Row 1: Welcome Banner & Core Metric Cards -->
    <div class="row g-4 mb-4">
        <!-- Welcome Card -->
        <div class="col-12 col-xxl-5">
            <div class="card welcome-card h-100 shadow-sm border-0">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div class="welcome-content">
                        <p class="welcome-greeting text-muted mb-1">Good day,</p>
                        <h3 class="welcome-name fw-bold mb-2">{{ $currentUser->name }}</h3>
                        @php
                            $roleVal = $currentUser->role instanceof \App\Enums\UserRole ? $currentUser->role->value : (string) $currentUser->role;
                            $roleLabel = $currentUser->role instanceof \App\Enums\UserRole ? $currentUser->role->label() : ucfirst(str_replace('_', ' ', $roleVal));
                        @endphp
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="badge badge-role-{{ $roleVal }} px-2.5 py-1">{{ $roleLabel }}</span>
                            @if($currentUser->department)
                                <span class="badge bg-light text-dark border">{{ $currentUser->department->name }}</span>
                            @endif
                        </div>
                        <div class="welcome-date text-muted small d-flex flex-wrap align-items-center gap-3">
                            <span class="d-inline-flex align-items-center">
                                <i class="bi bi-calendar3 me-1 text-primary"></i>
                                <span id="currentDate">{{ now()->format('M j, Y') }}</span>
                            </span>
                            <span class="d-inline-flex align-items-center">
                                <i class="bi bi-clock me-1 text-primary"></i>
                                <span id="currentTime">{{ now()->format('H:i:s') }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="welcome-illustration d-none d-sm-block ms-3">
                        <img src="{{ asset('admin-assets/img/illustrations/welcome.svg') }}" alt="Hospital Portal Welcome" style="max-height: 125px; width: auto;" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Card 1: Today's Appointments -->
        <div class="col-12 col-sm-6 col-xxl">
            <div class="card stat-card card-lift h-100 shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="stat-card-header d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-card-label text-muted small fw-semibold text-uppercase">Appointments Today</span>
                        <div class="stat-card-icon primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(26, 102, 196, 0.12); color: #1a66c4;">
                            <i class="bi bi-calendar2-check fs-5"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-2 fw-bold text-dark mb-1">{{ number_format($stats['appointments_today']) }}</div>
                    <div class="stat-card-change small {{ $stats['appointments_pending'] > 0 ? 'text-warning' : 'text-muted' }}">
                        <i class="bi {{ $stats['appointments_pending'] > 0 ? 'bi-hourglass-split' : 'bi-check-all' }} me-1"></i>
                        <strong>{{ number_format($stats['appointments_pending']) }}</strong> pending triage/confirmation
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Card 2: Registered Patients -->
        <div class="col-12 col-sm-6 col-xxl">
            <div class="card stat-card card-lift h-100 shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="stat-card-header d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-card-label text-muted small fw-semibold text-uppercase">Registered Patients</span>
                        <div class="stat-card-icon success rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(34, 197, 94, 0.12); color: #16a34a;">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-2 fw-bold text-dark mb-1">{{ number_format($stats['total_patients']) }}</div>
                    <div class="stat-card-change text-success small">
                        <i class="bi bi-arrow-up-right me-1"></i>
                        <strong>+{{ number_format($stats['new_patients_30d']) }}</strong> new in last 30 days
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Card 3: Active Encounters & Completed Consultations -->
        <div class="col-12 col-sm-6 col-xxl">
            <div class="card stat-card card-lift h-100 shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="stat-card-header d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-card-label text-muted small fw-semibold text-uppercase">Active Encounters</span>
                        <div class="stat-card-icon info rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(6, 182, 212, 0.12); color: #0891b2;">
                            <i class="bi bi-clipboard2-pulse fs-5"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-2 fw-bold text-dark mb-1">{{ number_format($stats['visits_today']) }}</div>
                    <div class="stat-card-change text-muted small">
                        <i class="bi bi-check-circle me-1 text-success"></i>
                        <strong>{{ number_format($stats['consultations_signed']) }}</strong> signed consultations
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Card 4: Pharmacy Alerts -->
        <div class="col-12 col-sm-6 col-xxl">
            <div class="card stat-card card-lift h-100 shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="stat-card-header d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-card-label text-muted small fw-semibold text-uppercase">Pharmacy Alerts</span>
                        <div class="stat-card-icon warning rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                            <i class="bi bi-capsule fs-5"></i>
                        </div>
                    </div>
                    <div class="stat-card-value fs-2 fw-bold text-dark mb-1">{{ number_format($stats['pharmacy_low_stock'] + $stats['pharmacy_expiring_batches']) }}</div>
                    <div class="stat-card-change small {{ ($stats['pharmacy_low_stock'] + $stats['pharmacy_expiring_batches']) > 0 ? 'text-warning' : 'text-success' }}">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>{{ $stats['pharmacy_low_stock'] }}</strong> low stock, <strong>{{ $stats['pharmacy_expiring_batches'] }}</strong> expiring
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Operational Analytics Charts -->
    <div class="row g-4 mb-4">
        <!-- Appointment Status Distribution (Donut Chart) -->
        <div class="col-12 col-xl-5">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">Appointment Status Distribution</h5>
                        <p class="text-muted extra-small mb-0">Total {{ number_format($stats['appointments_count']) }} appointments on record</p>
                    </div>
                    <span class="badge bg-light text-secondary border">Real Data</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    @if($stats['appointments_count'] > 0)
                        <div id="statusDistributionChart" style="min-height: 290px;"></div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-pie-chart fs-1 opacity-50 d-block mb-2"></i>
                            <p class="mb-0">No appointment bookings recorded yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Weekly Clinical Flow (Bar Chart) -->
        <div class="col-12 col-xl-7">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">Weekly Clinical Patient Flow</h5>
                        <p class="text-muted extra-small mb-0">Appointments booked vs. Intake visits checked in (Last 7 Days)</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Bookings</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Intake Visits</span>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div id="weeklyFlowChart" style="min-height: 290px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: 30-Day Activity Trend & Formulary Stock Watch -->
    <div class="row g-4 mb-4">
        <!-- 30-Day Activity Trend (Area Spline Chart) -->
        <div class="col-12 col-xl-7">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="card-title fw-bold text-dark mb-0">Clinical Activity &amp; Booking Trends</h5>
                            <p class="text-muted extra-small mb-0">Aggregated daily flow over the rolling 30-day window</p>
                        </div>
                        <ul class="nav nav-pills nav-pills-sm" id="trendTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active px-3 py-1 btn-sm" id="pills-30d-tab" data-bs-toggle="pill" data-bs-target="#pills-30d" type="button" role="tab">30 Days</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1 btn-sm" id="pills-today-tab" data-bs-toggle="pill" data-bs-target="#pills-today" type="button" role="tab">Today</button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4">
                    <!-- Metrics Summary Strip -->
                    <div class="row g-3 mb-3 border-bottom pb-3">
                        <div class="col-4">
                            <div class="text-muted small">Total Bookings</div>
                            <div class="fs-5 fw-bold text-primary">{{ number_format($stats['appointments_count']) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Signed Encounters</div>
                            <div class="fs-5 fw-bold text-success">{{ number_format($stats['consultations_signed']) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Active Clinics</div>
                            <div class="fs-5 fw-bold text-dark">{{ number_format($stats['departments_count']) }}</div>
                        </div>
                    </div>
                    <div class="tab-content" id="trendTabsContent">
                        <div class="tab-pane fade show active" id="pills-30d" role="tabpanel">
                            <div id="monthlyTrendChart" style="min-height: 260px;"></div>
                        </div>
                        <div class="tab-pane fade" id="pills-today" role="tabpanel">
                            <div class="d-flex flex-column align-items-center justify-content-center py-4 text-center">
                                <i class="bi bi-clock-history text-primary fs-2 mb-2"></i>
                                <h6 class="fw-bold mb-1">Today's Clinic Throughput</h6>
                                <p class="text-muted small mb-3">
                                    {{ $stats['appointments_today'] }} appointment(s) scheduled for today &bull;
                                    {{ $stats['visits_today'] }} patient visit(s) active in intake/triage.
                                </p>
                                <a href="{{ route('admin.intake.index') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Open Front-Desk Intake
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Medicine & Stock Watch Table -->
        <div class="col-12 col-xl-5">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">Critical Formulary Stock Watch</h5>
                        <p class="text-muted extra-small mb-0">Monitored inventory levels vs reorder thresholds</p>
                    </div>
                    @if($currentUser->hasRole(['super_admin', 'hospital_director', 'pharmacist']))
                        <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-boxes me-1"></i> Inventory
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Reorder</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventoryAlerts as $med)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $med['brand_name'] }}</div>
                                            <div class="text-muted extra-small">{{ $med['generic_name'] }} &bull; {{ ucfirst($med['category']) }}</div>
                                        </td>
                                        <td class="text-end fw-bold {{ $med['available_stock'] <= 0 ? 'text-danger' : ($med['is_low'] ? 'text-warning' : 'text-dark') }}">
                                            {{ number_format($med['available_stock']) }}
                                        </td>
                                        <td class="text-end text-muted">{{ number_format($med['reorder_level']) }}</td>
                                        <td class="text-center">
                                            @if($med['available_stock'] <= 0)
                                                <span class="badge badge-soft-danger px-2 py-1">Out of Stock</span>
                                            @elseif($med['is_low'])
                                                <span class="badge badge-soft-warning px-2 py-1">Low Stock</span>
                                            @else
                                                <span class="badge badge-soft-success px-2 py-1">Adequate</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No medicines catalogued in inventory yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Recent Appointments, Departmental Workload & Audit Trail -->
    <div class="row g-4 mb-4">
        <!-- Recent Patient Appointments Table -->
        <div class="col-12 col-lg-5">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">Recent Appointments</h5>
                        <p class="text-muted extra-small mb-0">Latest bookings across clinical departments</p>
                    </div>
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-outline-secondary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Ref #</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAppointments as $appt)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.appointments.show', $appt) }}" class="fw-semibold text-primary text-decoration-none">
                                                {{ $appt->reference_code }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark text-truncate" style="max-width: 140px;">
                                                {{ $appt->patient?->full_name ?? $appt->patient_name }}
                                            </div>
                                            <div class="text-muted extra-small text-truncate" style="max-width: 140px;">
                                                {{ $appt->department?->name ?? 'General Clinic' }}
                                            </div>
                                        </td>
                                        <td class="text-nowrap text-muted">
                                            {{ $appt->appointment_date ? $appt->appointment_date->format('M d') : 'N/A' }}
                                        </td>
                                        <td class="text-center">
                                            @if($appt->status === 'confirmed')
                                                <span class="badge badge-soft-success">Confirmed</span>
                                            @elseif($appt->status === 'completed')
                                                <span class="badge badge-soft-info">Completed</span>
                                            @elseif($appt->status === 'pending')
                                                <span class="badge badge-soft-warning">Pending</span>
                                            @elseif($appt->status === 'cancelled')
                                                <span class="badge badge-soft-danger">Cancelled</span>
                                            @else
                                                <span class="badge badge-soft-secondary">{{ ucfirst($appt->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No appointments recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departmental Workload Distribution -->
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">Departmental Workload</h5>
                        <p class="text-muted extra-small mb-0">Operational volume by clinic specialty</p>
                    </div>
                    <span class="badge bg-light text-secondary border">{{ count($departmentDistribution) }} Units</span>
                </div>
                <div class="card-body p-4">
                    <div class="department-stats d-flex flex-column gap-3">
                        @forelse($departmentDistribution as $dept)
                            <div class="dept-item">
                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                    <span class="fw-semibold text-dark text-truncate" style="max-width: 200px;">{{ $dept['name'] }}</span>
                                    <span class="text-muted"><strong>{{ $dept['total'] }}</strong> encounters ({{ $dept['percentage'] }}%)</span>
                                </div>
                                <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ max($dept['percentage'], 4) }}%; background: var(--accent-color, #1a66c4);" aria-valuenow="{{ $dept['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">No active clinical departments found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity / Security Audit Feed -->
        <div class="col-12 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">Recent Activity</h5>
                        <p class="text-muted extra-small mb-0">Live audit &amp; security event stream</p>
                    </div>
                    @if($currentUser->hasRole('super_admin'))
                        <a href="{{ route('admin.audit.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">All</a>
                    @endif
                </div>
                <div class="card-body p-3 overflow-auto" style="max-height: 380px;">
                    @if($currentUser->hasRole(['super_admin', 'hospital_director']))
                        <div class="activity-feed">
                            @forelse($recentAuditLogs as $log)
                                <div class="d-flex align-items-start gap-2 mb-3 pb-2 border-bottom border-light">
                                    <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center mt-0.5" style="width: 24px; height: 24px; background: rgba(26, 102, 196, 0.12); color: #1a66c4;">
                                        <i class="bi bi-activity fs-xs"></i>
                                    </div>
                                    <div class="flex-grow-1" style="line-height: 1.3;">
                                        <div class="fw-semibold text-dark extra-small"><code>{{ $log->action }}</code></div>
                                        <div class="text-muted extra-small">{{ $log->actor_email ?? 'System' }}</div>
                                        <div class="text-muted extra-small" style="font-size: 0.7rem;">{{ $log->created_at ? $log->created_at->diffForHumans() : 'Just now' }}</div>
                                    </div>
                                    <div>
                                        @if($log->outcome === 'success')
                                            <span class="badge bg-success-subtle text-success extra-small">ok</span>
                                        @elseif($log->outcome === 'denied')
                                            <span class="badge bg-danger-subtle text-danger extra-small">denied</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning extra-small">{{ $log->outcome }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted small">No audit events recorded yet.</div>
                            @endforelse
                        </div>
                    @else
                        <!-- Staff Clinical Summary for non-superadmin roles -->
                        <div class="p-2 text-center text-muted">
                            <i class="bi bi-shield-check text-primary fs-2 mb-2 d-block"></i>
                            <div class="fw-semibold text-dark small mb-1">Audit Stream Protected</div>
                            <p class="extra-small mb-2">Centralized security audit events are reserved for Hospital Directors and Super Administrators under least privilege.</p>
                            <span class="badge bg-light text-secondary border">Your Terminal IP: {{ request()->ip() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Row 5: Role Profile & Permissions (Mandatory RBAC Transparency) -->
    <div class="row g-4 mb-4">
        <!-- Authorized Department Modules Quick Launchpad -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-grid-fill text-primary me-2"></i> Authorized Department Modules
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @php $user = $currentUser; @endphp

                        @if($user->hasRole(['super_admin', 'receptionist', 'nurse']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.intake.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-box-arrow-in-right fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Front-Desk Intake</div>
                                            <div class="small text-muted">Check-in appointments &amp; walk-in visits</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'hospital_director', 'doctor', 'nurse', 'receptionist']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.patients.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-person-vcard fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Patient Registry (PMI)</div>
                                            <div class="small text-muted">Patient master index, MRNs &amp; profiles</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'nurse', 'receptionist']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.triage.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-clipboard2-pulse fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Triage &amp; Vitals</div>
                                            <div class="small text-muted">Patient intake, vital signs &amp; bed queue</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'doctor']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.clinical.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-stethoscope fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Clinical Consultations</div>
                                            <div class="small text-muted">SOAP notes, EHR &amp; prescriptions</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'pharmacist']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.pharmacy.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-capsule fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Pharmacy Dispensation</div>
                                            <div class="small text-muted">Formulary, batch inventory &amp; dispensing</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'lab_technician']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.laboratory.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-eyedropper fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Laboratory Diagnostics</div>
                                            <div class="small text-muted">Specimen accessioning &amp; test results</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'cashier']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.billing.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-receipt fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Billing &amp; Cashier</div>
                                            <div class="small text-muted">Patient invoices &amp; receipt issuance</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'hospital_director']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.reports.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-danger bg-opacity-10 text-danger">
                                            <i class="bi bi-bar-chart-line fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Executive Reports</div>
                                            <div class="small text-muted">Bed census, clinical activity &amp; revenue</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole('super_admin'))
                            <div class="col-md-6">
                                <a href="{{ route('admin.staff.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-secondary bg-opacity-10 text-dark">
                                            <i class="bi bi-people fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Staff &amp; RBAC Accounts</div>
                                            <div class="small text-muted">User provisioning, roles &amp; activation</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-6">
                                <a href="{{ route('admin.audit.index') }}" class="card text-decoration-none border p-3 h-100 card-lift rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-dark bg-opacity-10 text-dark">
                                            <i class="bi bi-shield-lock fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Centralized Audit Log</div>
                                            <div class="small text-muted">Immutable security &amp; operational trail</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Profile & Permissions Details (Verified by Test Assertions) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-shield-shaded text-primary me-2"></i> Role Profile &amp; Permissions
                    </h5>
                    <span class="badge bg-secondary-subtle text-secondary">RBAC Least Privilege</span>
                </div>
                <div class="card-body p-4">
                    @php
                        $userRole = $currentUser->role instanceof \App\Enums\UserRole ? $currentUser->role : null;
                    @endphp

                    @if($userRole)
                        <!-- Purpose -->
                        <div class="mb-3">
                            <span class="fw-bold text-dark small d-block mb-1"><i class="bi bi-info-circle text-primary me-1"></i> Role Purpose:</span>
                            <p class="small text-muted mb-0">{{ $userRole->purpose() }}</p>
                        </div>

                        <!-- What staff can view -->
                        <div class="mb-3">
                            <span class="fw-bold text-dark small d-block mb-1 text-success"><i class="bi bi-eye text-success me-1"></i> Authorized to View:</span>
                            <ul class="list-unstyled mb-0 small text-muted ps-1">
                                @foreach($userRole->canViewDescriptions() as $viewDesc)
                                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> {{ $viewDesc }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- What staff can manage -->
                        <div class="mb-3">
                            <span class="fw-bold text-dark small d-block mb-1 text-primary"><i class="bi bi-pencil-square text-primary me-1"></i> Authorized to Create &amp; Change:</span>
                            <ul class="list-unstyled mb-0 small text-muted ps-1">
                                @foreach($userRole->canManageDescriptions() as $manageDesc)
                                    <li class="mb-1"><i class="bi bi-arrow-right-short text-primary me-1"></i> {{ $manageDesc }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Important Restrictions -->
                        <div class="mb-3">
                            <span class="fw-bold text-dark small d-block mb-1 text-danger"><i class="bi bi-shield-lock text-danger me-1"></i> Operational &amp; Clinical Restrictions:</span>
                            <ul class="list-unstyled mb-0 small text-muted ps-1">
                                @foreach($userRole->restrictions() as $restr)
                                    <li class="mb-1"><i class="bi bi-dash text-danger me-1"></i> {{ $restr }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Granular Capabilities -->
                        <div class="mt-3 pt-3 border-top">
                            <span class="fw-bold text-dark small d-block mb-2">Granted Technical Capabilities:</span>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($userRole->capabilities() as $cap)
                                    <span class="badge bg-light text-dark border extra-small" title="{{ $cap }}">
                                        {{ \App\Enums\UserRole::describeCapability($cap) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="small text-muted mb-0">Role details unavailable.</p>
                    @endif

                    <div class="alert alert-info border-0 mt-3 p-2.5 extra-small mb-0 rounded-3">
                        <i class="bi bi-shield-check me-1"></i>
                        Protected by server-side gate policies. Actions and queries are recorded in the immutable audit trail.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Digital Clock & Date Updater
    function updateClock() {
        const now = new Date();
        const dateEl = document.getElementById('currentDate');
        const timeEl = document.getElementById('currentTime');
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        if (timeEl) {
            timeEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        }
    }
    updateClock();
    setInterval(updateClock, 1000);

    // 2. Fetch Theme Variables
    function getCssVar(name, fallback) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
    }

    const accentColor = getCssVar('--accent-color', '#1a66c4');
    const successColor = getCssVar('--success-color', '#22c55e');
    const warningColor = getCssVar('--warning-color', '#f59e0b');
    const infoColor = getCssVar('--info-color', '#06b6d4');
    const dangerColor = getCssVar('--danger-color', '#ef4444');
    const mutedColor = getCssVar('--muted-color', '#64748b');
    const borderColor = getCssVar('--border-color', '#e2e8f0');

    // 3. Appointment Status Donut Chart
    const statusChartEl = document.querySelector('#statusDistributionChart');
    if (statusChartEl && typeof ApexCharts !== 'undefined') {
        const statusLabels = @json($statusLabels);
        const statusSeries = @json($statusSeries);

        const statusOptions = {
            series: statusSeries,
            labels: statusLabels,
            chart: {
                type: 'donut',
                height: 290,
                fontFamily: 'inherit'
            },
            colors: [successColor, infoColor, warningColor, accentColor, dangerColor],
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name: {
                                fontSize: '13px',
                                fontWeight: 500,
                                color: mutedColor
                            },
                            value: {
                                fontSize: '22px',
                                fontWeight: 700,
                                formatter: function (val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '12px',
                                fontWeight: 600,
                                color: mutedColor,
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom',
                fontSize: '12px',
                markers: { width: 10, height: 10, radius: 3 }
            },
            stroke: { width: 2, colors: [getCssVar('--surface-color', '#ffffff')] }
        };

        const statusChart = new ApexCharts(statusChartEl, statusOptions);
        statusChart.render();
    }

    // 4. Weekly Patient Flow Bar Chart
    const weeklyChartEl = document.querySelector('#weeklyFlowChart');
    if (weeklyChartEl && typeof ApexCharts !== 'undefined') {
        const weeklyCategories = @json($weeklyCategories);
        const weeklyAppts = @json($weeklyApptSeries);
        const weeklyVisits = @json($weeklyVisitSeries);

        const weeklyOptions = {
            series: [
                { name: 'Booked Appointments', data: weeklyAppts },
                { name: 'Intake Visits', data: weeklyVisits }
            ],
            chart: {
                type: 'bar',
                height: 290,
                fontFamily: 'inherit',
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            colors: [accentColor, successColor],
            dataLabels: { enabled: false },
            xaxis: {
                categories: weeklyCategories,
                labels: { style: { colors: mutedColor, fontSize: '12px' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: mutedColor, fontSize: '12px' },
                    formatter: function (val) { return Math.floor(val); }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 3,
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                markers: { width: 10, height: 10, radius: 3 }
            },
            tooltip: {
                shared: true,
                intersect: false
            }
        };

        const weeklyChart = new ApexCharts(weeklyChartEl, weeklyOptions);
        weeklyChart.render();
    }

    // 5. Monthly 30-Day Activity Trend Spline Area Chart
    const trendChartEl = document.querySelector('#monthlyTrendChart');
    if (trendChartEl && typeof ApexCharts !== 'undefined') {
        const monthlyCategories = @json($monthlyCategories);
        const monthlyAppts = @json($monthlyApptSeries);
        const monthlyVisits = @json($monthlyVisitSeries);

        const trendOptions = {
            series: [
                { name: 'Appointments Scheduled', data: monthlyAppts },
                { name: 'Patient Visits Registered', data: monthlyVisits }
            ],
            chart: {
                type: 'area',
                height: 260,
                fontFamily: 'inherit',
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            colors: [accentColor, successColor],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: monthlyCategories,
                labels: {
                    style: { colors: mutedColor, fontSize: '11px' },
                    rotate: -45,
                    rotateAlways: false,
                    showDuplicates: false
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: mutedColor, fontSize: '11px' },
                    formatter: function (val) { return Math.floor(val); }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 3
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                markers: { width: 10, height: 10, radius: 3 }
            }
        };

        const trendChart = new ApexCharts(trendChartEl, trendOptions);
        trendChart.render();
    }
});
</script>
@endpush
