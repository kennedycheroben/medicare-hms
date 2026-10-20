@extends('layouts.admin')

@section('title', 'Administrative Dashboard')

@section('content')
<div class="container-fluid p-0">

    <!-- Staff Profile & Session Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-person-badge fs-2"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h2 class="h4 fw-bold text-dark mb-0">{{ $currentUser->name }}</h2>
                        @php
                            $roleVal = $currentUser->role instanceof \App\Enums\UserRole ? $currentUser->role->value : (string) $currentUser->role;
                            $roleLabel = $currentUser->role instanceof \App\Enums\UserRole ? $currentUser->role->label() : ucfirst(str_replace('_', ' ', $roleVal));
                        @endphp
                        <span class="badge badge-role-{{ $roleVal }} px-2 py-1 fs-6">{{ $roleLabel }}</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> Active Session
                        </span>
                    </div>
                    <div class="text-muted small d-flex flex-wrap gap-3">
                        <span><i class="bi bi-envelope me-1"></i> {{ $currentUser->email }}</span>
                        @if($currentUser->department)
                            <span><i class="bi bi-building me-1"></i> Department: <strong>{{ $currentUser->department->name }}</strong></span>
                        @endif
                        @if($currentUser->doctor)
                            <span><i class="bi bi-stethoscope me-1"></i> Doctor Profile: <strong>{{ $currentUser->doctor->name }}</strong> ({{ $currentUser->doctor->specialty }})</span>
                        @endif
                        <span><i class="bi bi-geo-alt me-1"></i> Terminal IP: <code>{{ $currentUser->last_login_ip ?? request()->ip() }}</code></span>
                        <span><i class="bi bi-clock-history me-1"></i> Logged in: {{ $currentUser->last_login_at ? $currentUser->last_login_at->diffForHumans() : 'Just now' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core Hospital Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Total Appointments</span>
                    <span class="p-2 rounded bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-calendar2-check fs-5"></i>
                    </span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['appointments_count']) }}</div>
                <div class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i> Booked via public site & triage</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Active Medical Doctors</span>
                    <span class="p-2 rounded bg-info bg-opacity-10 text-info">
                        <i class="bi bi-person-video3 fs-5"></i>
                    </span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['doctors_count']) }}</div>
                <div class="small text-muted mt-1"><i class="bi bi-check2-circle me-1"></i> Board-certified specialists</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Clinical Departments</span>
                    <span class="p-2 rounded bg-success bg-opacity-10 text-success">
                        <i class="bi bi-hospital fs-5"></i>
                    </span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['departments_count']) }}</div>
                <div class="small text-muted mt-1"><i class="bi bi-shield-check me-1"></i> Fully operational units</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Active Staff Accounts</span>
                    <span class="p-2 rounded bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-people fs-5"></i>
                    </span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['staff_count']) }}</div>
                <div class="small text-muted mt-1"><i class="bi bi-lock me-1"></i> Role-permission controlled</div>
            </div>
        </div>
    </div>

    <!-- Authorized Operations Matrix & Modules Grid -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h3 class="h5 fw-bold text-dark mb-0">
                        <i class="bi bi-grid-fill text-primary me-2"></i> Authorized Department Modules
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @php
                            $user = $currentUser;
                        @endphp

                        @if($user->hasRole(['super_admin', 'nurse', 'receptionist']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.triage.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-clipboard2-pulse fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Triage & Vitals</div>
                                            <div class="small text-muted">Patient intake, vital signs & bed queue</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'doctor']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.clinical.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-stethoscope fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Clinical Consultations</div>
                                            <div class="small text-muted">SOAP notes, EHR & electronic prescriptions</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'pharmacist']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.pharmacy.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-capsule fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Pharmacy Dispensation</div>
                                            <div class="small text-muted">Formulary, batch inventory & dispensing</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'lab_technician']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.laboratory.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-eyedropper fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Laboratory Diagnostic</div>
                                            <div class="small text-muted">Specimen accessioning & test results</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'cashier']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.billing.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-receipt fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Billing & Cashier</div>
                                            <div class="small text-muted">Patient invoices, MPesa & receipt issuance</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole(['super_admin', 'hospital_director']))
                            <div class="col-md-6">
                                <a href="{{ route('admin.reports.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-danger bg-opacity-10 text-danger">
                                            <i class="bi bi-bar-chart-line fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Executive Reports</div>
                                            <div class="small text-muted">Bed occupancy, revenue & patient census</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        @if($user->hasRole('super_admin'))
                            <div class="col-md-6">
                                <a href="{{ route('admin.staff.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-indigo bg-opacity-10 text-indigo" style="background-color: #ede9fe; color: #6366f1;">
                                            <i class="bi bi-people fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Staff & RBAC Accounts</div>
                                            <div class="small text-muted">User provisioning, roles & activation</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-6">
                                <a href="{{ route('admin.audit.index') }}" class="card text-decoration-none border p-3 h-100 hover-shadow rounded-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 rounded bg-dark bg-opacity-10 text-dark">
                                            <i class="bi bi-shield-lock fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">Centralized Audit Log</div>
                                            <div class="small text-muted">Immutable security & operational trail</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Capabilities & Security Context -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h3 class="h5 fw-bold text-dark mb-0">
                        <i class="bi bi-shield-shaded text-primary me-2"></i> Assigned Role Capabilities
                    </h3>
                    <span class="badge bg-secondary-subtle text-secondary">Principle of Least Privilege</span>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        Your account is provisioned under role <strong>{{ $roleLabel }}</strong>. Below are the verified capabilities granted by the application authorization layer:
                    </p>

                    @php
                        $capabilities = $currentUser->role instanceof \App\Enums\UserRole 
                            ? ($currentUser->isSuperAdmin() ? ['All system privileges (Super Administrator)'] : $currentUser->role->capabilities()) 
                            : [];
                    @endphp

                    <ul class="list-group list-group-flush mb-0 small">
                        @forelse($capabilities as $cap)
                            <li class="list-group-item d-flex align-items-center px-0 py-2 border-light">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <code class="text-dark">{{ $cap }}</code>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">No specific capabilities configured.</li>
                        @endforelse
                    </ul>

                    <div class="alert alert-info border-0 mt-3 p-3 small mb-0 rounded-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Administrative actions, queries, and login/logout events are continuously monitored and logged.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Audit Log Stream (Visible to Super Admin & Director) -->
    @if($currentUser->hasRole(['super_admin', 'hospital_director']))
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h3 class="h5 fw-bold text-dark mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i> Recent Security Audit Trail
                </h3>
                <span class="badge bg-light text-secondary border">Latest {{ count($recentAuditLogs) }} Events</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Timestamp</th>
                            <th scope="col">Action</th>
                            <th scope="col">Actor Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Outcome</th>
                            <th scope="col">IP Address</th>
                            <th scope="col">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAuditLogs as $log)
                            <tr>
                                <td class="text-nowrap text-muted">{{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                <td><code>{{ $log->action }}</code></td>
                                <td>{{ $log->actor_email ?? 'Anonymous' }}</td>
                                <td>
                                    @if($log->actor_role)
                                        <span class="badge badge-role-{{ $log->actor_role }}">{{ ucfirst(str_replace('_', ' ', $log->actor_role)) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->outcome === 'success')
                                        <span class="badge bg-success-subtle text-success">success</span>
                                    @elseif($log->outcome === 'denied')
                                        <span class="badge bg-danger-subtle text-danger">denied</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">{{ $log->outcome }}</span>
                                    @endif
                                </td>
                                <td><code>{{ $log->ip_address }}</code></td>
                                <td class="text-muted" style="max-width: 250px;">
                                    @if($log->details)
                                        <span class="text-truncate d-inline-block" style="max-width: 240px;" title="{{ json_encode($log->details) }}">
                                            {{ json_encode($log->details) }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No audit events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
