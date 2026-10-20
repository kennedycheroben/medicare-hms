@extends('layouts.admin')

@section('title', 'Staff Management')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Staff Management & Role Assignment</h1>
            <p class="text-muted small mb-0">Manage hospital staff accounts, roles, departmental assignments, and status.</p>
        </div>
        <div>
            <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" disabled title="Feature coming in Phase 5 staff provisioning">
                <i class="bi bi-person-plus-fill"></i> Add Staff Member
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Staff Member</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Terminal IP</th>
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
                                {{ $staff->department->name ?? 'General / Cross-Departmental' }}
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
                                {{ $staff->last_login_at ? $staff->last_login_at->format('Y-m-d H:i') : 'Never' }}
                            </td>
                            <td class="text-muted small">
                                <code>{{ $staff->last_login_ip ?? '—' }}</code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No staff accounts found.</td>
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
