@extends('layouts.admin')

@section('title', 'Security Audit Trail')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Centralized Security Audit Trail</h1>
        <p class="text-muted small mb-0">Immutable record of authentication events, authorization enforcement, and administrative operations.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Timestamp (UTC)</th>
                        <th scope="col">Action</th>
                        <th scope="col">Actor Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Outcome</th>
                        <th scope="col">Resource</th>
                        <th scope="col">IP Address</th>
                        <th scope="col">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>#{{ $log->id }}</td>
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
                            <td class="text-muted">
                                @if($log->resource_type)
                                    <span class="text-dark fw-medium">{{ $log->resource_type }}</span>: {{ $log->resource_id ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><code>{{ $log->ip_address }}</code></td>
                            <td class="text-muted" style="max-width: 260px;">
                                @if($log->details)
                                    <span class="text-truncate d-inline-block" style="max-width: 250px;" title="{{ json_encode($log->details) }}">
                                        {{ json_encode($log->details) }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No audit logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
