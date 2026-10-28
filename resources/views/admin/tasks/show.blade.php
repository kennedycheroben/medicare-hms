@extends('layouts.admin')

@section('title', 'Staff Task: ' . $task->title)

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Tasks
                </a>
                @if($task->priority === 'urgent')
                    <span class="badge bg-danger-subtle text-danger fw-semibold px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>Urgent</span>
                @elseif($task->priority === 'high')
                    <span class="badge bg-warning-subtle text-warning fw-semibold px-2 py-1">High</span>
                @elseif($task->priority === 'medium')
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">Medium</span>
                @else
                    <span class="badge bg-light text-muted fw-semibold px-2 py-1">Low</span>
                @endif

                @if($task->status === 'completed')
                    <span class="badge bg-success-subtle text-success fw-semibold px-2.5 py-1"><i class="bi bi-check-circle me-1"></i>Completed</span>
                @elseif($task->status === 'in_progress')
                    <span class="badge bg-info-subtle text-info fw-semibold px-2.5 py-1"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>
                @elseif($task->status === 'cancelled')
                    <span class="badge bg-secondary-subtle text-secondary fw-semibold px-2.5 py-1">Cancelled</span>
                @else
                    <span class="badge bg-warning-subtle text-warning fw-semibold px-2.5 py-1">Pending</span>
                @endif

                @if($task->isOverdue())
                    <span class="badge bg-danger text-white px-2 py-1">Overdue</span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">{{ $task->title }}</h1>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main: Task Details & History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-card-text text-primary"></i> Assignment Specifications
                    </h5>
                    <span class="text-muted small">
                        Created {{ $task->created_at->format('M d, Y \a\t H:i') }} ({{ $task->created_at->diffForHumans() }})
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Current Assignee</span>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-person-circle text-primary me-1"></i> {{ $task->assignee?->name ?? 'Unassigned' }}
                            </span>
                            <div class="extra-small text-muted">{{ $task->assignee?->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Delegated By</span>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-person-up text-secondary me-1"></i> {{ $task->creator?->name ?? 'System' }}
                            </span>
                            <div class="extra-small text-muted">{{ $task->creator?->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Target Due Date</span>
                            @if($task->due_date)
                                <span class="fw-bold {{ $task->isOverdue() ? 'text-danger' : 'text-dark' }}">
                                    <i class="bi bi-calendar3 me-1"></i> {{ $task->due_date->format('l, F j, Y') }}
                                </span>
                            @else
                                <span class="text-muted">No deadline specified</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Completed Timestamp</span>
                            @if($task->completed_at)
                                <span class="badge bg-success-subtle text-success fw-bold">
                                    <i class="bi bi-check-all me-1"></i> {{ $task->completed_at->format('M d, Y H:i') }}
                                </span>
                            @else
                                <span class="text-muted small">Pending completion</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-muted small mb-1 fw-bold">Detailed Instructions / Clinical Protocol</label>
                        <div class="p-4 bg-light rounded-3 text-dark border" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $task->description ?: 'No detailed instructions provided.' }}</div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail of Task Progress -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i> Action History & Progress Trail
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Timestamp</th>
                                    <th>Action</th>
                                    <th>Performed By</th>
                                    <th class="pe-4">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td class="ps-4 small text-muted font-monospace">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                        <td><span class="badge bg-light text-dark font-monospace">{{ $log->action }}</span></td>
                                        <td>{{ $log->user?->name ?? $log->actor_email ?? 'Staff' }}</td>
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
                                            No action logs recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Update Status & Manage Task -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-primary"></i> {{ $canManage ? 'Manage & Reassign Task' : 'Update Task Status' }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.tasks.update', $task->id) }}">
                        @csrf
                        @method('PUT')

                        @if($canManage)
                            <div class="mb-3">
                                <label for="edit_title" class="form-label fw-bold small">Task Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="edit_title" class="form-control form-control-sm" value="{{ old('title', $task->title) }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="edit_assigned_to" class="form-label fw-bold small">Assignee <span class="text-danger">*</span></label>
                                <select name="assigned_to" id="edit_assigned_to" class="form-select form-select-sm" required>
                                    @foreach($staffMembers as $staff)
                                        <option value="{{ $staff->id }}" @selected(old('assigned_to', $task->assigned_to) == $staff->id)>
                                            {{ $staff->name }} ({{ ucfirst(str_replace('_', ' ', is_string($staff->role) ? $staff->role : $staff->role->value)) }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text extra-small">Changing assignee will automatically dispatch a reassignment notification.</div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_priority" class="form-label fw-bold small">Priority <span class="text-danger">*</span></label>
                                <select name="priority" id="edit_priority" class="form-select form-select-sm" required>
                                    <option value="low" @selected(old('priority', $task->priority) === 'low')>Low</option>
                                    <option value="medium" @selected(old('priority', $task->priority) === 'medium')>Medium</option>
                                    <option value="high" @selected(old('priority', $task->priority) === 'high')>High</option>
                                    <option value="urgent" @selected(old('priority', $task->priority) === 'urgent')>Urgent</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_due_date" class="form-label fw-bold small">Target Due Date</label>
                                <input type="date" name="due_date" id="edit_due_date" class="form-control form-control-sm" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                            </div>

                            <div class="mb-3">
                                <label for="edit_description" class="form-label fw-bold small">Instructions</label>
                                <textarea name="description" id="edit_description" class="form-control form-control-sm" rows="3">{{ old('description', $task->description) }}</textarea>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="edit_status" class="form-label fw-bold small">Current Lifecycle Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_status" class="form-select form-select-sm" required>
                                <option value="pending" @selected(old('status', $task->status) === 'pending')>Pending (Not Started)</option>
                                <option value="in_progress" @selected(old('status', $task->status) === 'in_progress')>In Progress</option>
                                <option value="completed" @selected(old('status', $task->status) === 'completed')>Completed</option>
                                <option value="cancelled" @selected(old('status', $task->status) === 'cancelled')>Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
