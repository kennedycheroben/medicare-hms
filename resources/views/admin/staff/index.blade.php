@extends('layouts.admin')

@section('title', 'Staff Management')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Staff Management & RBAC Directory</h1>
            <p class="text-muted small mb-0">Manage hospital staff accounts, roles, departmental assignments, and status.</p>
        </div>
        <div>
            <a href="{{ route('admin.staff.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-person-plus-fill"></i> Add Staff Member
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Total Staff</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Active Accounts</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['active']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Deactivated</div>
                <div class="fs-4 fw-bold text-danger mt-1">{{ number_format($stats['inactive']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Super Admins</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($stats['super_admins']) }}</div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.staff.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name or email..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" {{ ($filters['role'] ?? '') === $role->value ? 'selected' : '' }}>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6 col-md-3">
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Directory Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Staff Member</th>
                        <th>Role</th>
                        <th>Department & Clinic</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ $staff->name }}</div>
                                <div class="small text-muted">{{ $staff->email }}</div>
                            </td>
                            <td>
                                @php
                                    $roleVal = $staff->role instanceof \App\Enums\UserRole ? $staff->role->value : (string) $staff->role;
                                    $roleLabel = $staff->role instanceof \App\Enums\UserRole ? $staff->role->label() : ucfirst(str_replace('_', ' ', $roleVal));
                                @endphp
                                <span class="badge badge-role-{{ $roleVal }}">{{ $roleLabel }}</span>
                            </td>
                            <td>
                                <div class="text-dark small">{{ $staff->department->name ?? 'General / Non-Departmental' }}</div>
                                @if($staff->doctor)
                                    <div class="text-muted small"><i class="bi bi-stethoscope me-1"></i> {{ $staff->doctor->name }}</div>
                                @endif
                            </td>
                            <td>
                                @if($staff->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-x-circle-fill me-1"></i> Deactivated
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                <div>{{ $staff->last_login_at ? $staff->last_login_at->format('Y-m-d H:i') : 'Never logged in' }}</div>
                                @if($staff->last_login_ip)
                                    <code>{{ $staff->last_login_ip }}</code>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.staff.edit', $staff->id) }}" class="btn btn-outline-secondary" title="Edit Staff Profile">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if(Auth::id() !== $staff->id)
                                        <form action="{{ route('admin.staff.toggle-status', $staff->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $staff->is_active ? 'deactivate' : 'activate' }} {{ $staff->name }}?');">
                                            @csrf
                                            <button type="submit" class="btn {{ $staff->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $staff->is_active ? 'Deactivate Account' : 'Activate Account' }}">
                                                <i class="bi {{ $staff->is_active ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                No staff accounts found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($staffMembers->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $staffMembers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
