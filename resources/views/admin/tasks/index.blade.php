@extends('layouts.admin')

@section('title', 'Staff Tasks & Follow-Up Workflow')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Staff Tasks & Delegations</h1>
            <p class="text-muted small mb-0">Create, assign, track, and follow up on clinical, administrative, and patient care assignments.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="bi bi-plus-lg"></i> Assign New Task
            </button>
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

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Assigned</span>
                    <i class="bi bi-list-task text-primary fs-5"></i>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['total']) }}</div>
                <div class="extra-small text-muted mt-1">Total active scope tasks</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pending</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5">Awaiting Action</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['pending']) }}</div>
                <div class="extra-small text-muted mt-1">Not yet started</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">In Progress</span>
                    <span class="badge bg-info-subtle text-info rounded-pill px-2.5">Active</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['in_progress']) }}</div>
                <div class="extra-small text-muted mt-1">Work underway</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Completed</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2.5">Closed</span>
                </div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($stats['completed']) }}</div>
                <div class="extra-small text-muted mt-1">Fulfilled assignments</div>
            </div>
        </div>
    </div>

    <!-- Scope Tabs & Filter -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 border-bottom pb-3">
                <ul class="nav nav-pills gap-1">
                    <li class="nav-item">
                        <a href="{{ route('admin.tasks.index', array_merge(request()->query(), ['scope' => 'assigned_to_me'])) }}" class="nav-link btn-sm {{ $scope === 'assigned_to_me' ? 'active' : '' }}">
                            <i class="bi bi-person-check me-1"></i> Assigned To Me
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.tasks.index', array_merge(request()->query(), ['scope' => 'created_by_me'])) }}" class="nav-link btn-sm {{ $scope === 'created_by_me' ? 'active' : '' }}">
                            <i class="bi bi-person-up me-1"></i> Assigned By Me
                        </a>
                    </li>
                    @if($isExecutive)
                        <li class="nav-item">
                            <a href="{{ route('admin.tasks.index', array_merge(request()->query(), ['scope' => 'all'])) }}" class="nav-link btn-sm {{ $scope === 'all' ? 'active' : '' }}">
                                <i class="bi bi-hospital me-1"></i> All Hospital Tasks
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <form method="GET" action="{{ route('admin.tasks.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="scope" value="{{ $scope }}">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search task title or description..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm bg-light">
                        <option value="">All Statuses</option>
                        <option value="pending" @selected($status === 'pending')>Pending</option>
                        <option value="in_progress" @selected($status === 'in_progress')>In Progress</option>
                        <option value="completed" @selected($status === 'completed')>Completed</option>
                        <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="priority" class="form-select form-select-sm bg-light">
                        <option value="">All Priorities</option>
                        <option value="low" @selected($priority === 'low')>Low</option>
                        <option value="medium" @selected($priority === 'medium')>Medium</option>
                        <option value="high" @selected($priority === 'high')>High</option>
                        <option value="urgent" @selected($priority === 'urgent')>Urgent</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    @if($search || $status || $priority)
                        <a href="{{ route('admin.tasks.index', ['scope' => $scope]) }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Task Details</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $t)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $t->title }}</div>
                                    @if($t->description)
                                        <div class="extra-small text-muted">{{ Str::limit($t->description, 60) }}</div>
                                    @endif
                                    <div class="extra-small text-muted mt-1">
                                        Assigned by: <strong>{{ $t->creator?->name ?? 'System' }}</strong> &bull; {{ $t->created_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td>
                                    @if($t->priority === 'urgent')
                                        <span class="badge bg-danger-subtle text-danger fw-semibold px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>Urgent</span>
                                    @elseif($t->priority === 'high')
                                        <span class="badge bg-warning-subtle text-warning fw-semibold px-2 py-1">High</span>
                                    @elseif($t->priority === 'medium')
                                        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">Medium</span>
                                    @else
                                        <span class="badge bg-light text-muted fw-semibold px-2 py-1">Low</span>
                                    @endif
                                </td>
                                <td>
                                    @if($t->status === 'completed')
                                        <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1"><i class="bi bi-check-circle me-1"></i>Completed</span>
                                    @elseif($t->status === 'in_progress')
                                        <span class="badge bg-info-subtle text-info fw-semibold px-2 py-1"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>
                                    @elseif($t->status === 'cancelled')
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-2 py-1">Cancelled</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning fw-semibold px-2 py-1">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $t->assignee?->name ?? 'Unassigned' }}</div>
                                    <div class="extra-small text-muted">{{ $t->assignee?->role ? ucfirst(str_replace('_', ' ', is_string($t->assignee->role) ? $t->assignee->role : $t->assignee->role->value)) : 'Staff' }}</div>
                                </td>
                                <td>
                                    @if($t->due_date)
                                        <div class="small fw-semibold {{ $t->isOverdue() ? 'text-danger' : 'text-dark' }}">
                                            <i class="bi bi-calendar3 me-1"></i>{{ $t->due_date->format('M d, Y') }}
                                        </div>
                                        @if($t->isOverdue())
                                            <span class="badge bg-danger text-white extra-small">Overdue</span>
                                        @endif
                                    @else
                                        <span class="text-muted extra-small">No deadline</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('admin.tasks.show', $t->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-eye"></i> View & Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-clipboard2-check fs-1 d-block mb-2"></i>
                                        No tasks found under this view.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($tasks->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Create Task -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="{{ route('admin.tasks.store') }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="createTaskModalLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i> Assign New Hospital Task
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="task_title" class="form-label fw-bold small">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="task_title" class="form-control" placeholder="e.g. Follow up on pathology results for MRN-2026-0042" required maxlength="255">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="task_assigned_to" class="form-label fw-bold small">Assign To Staff Member <span class="text-danger">*</span></label>
                            <select name="assigned_to" id="task_assigned_to" class="form-select" required>
                                <option value="">-- Choose Staff Assignee --</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" @selected(old('assigned_to') == $staff->id)>
                                        {{ $staff->name }} ({{ ucfirst(str_replace('_', ' ', is_string($staff->role) ? $staff->role : $staff->role->value)) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="task_priority" class="form-label fw-bold small">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority" id="task_priority" class="form-select" required>
                                <option value="low">Low (Routine)</option>
                                <option value="medium" selected>Medium (Standard)</option>
                                <option value="high">High (Priority Clinical / Admin)</option>
                                <option value="urgent">Urgent (Immediate Patient Follow-Up)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="task_due_date" class="form-label fw-bold small">Target Due Date (Optional)</label>
                            <input type="date" name="due_date" id="task_due_date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="task_related_type" class="form-label fw-bold small">Related Category (Optional)</label>
                            <select name="related_type" id="task_related_type" class="form-select">
                                <option value="">-- None / General Task --</option>
                                <option value="appointment">Appointment</option>
                                <option value="patient">Patient Record</option>
                                <option value="inquiry">Public Contact Ticket</option>
                                <option value="pharmacy">Pharmacy / Stock</option>
                                <option value="clinical">Clinical Consultation</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="task_description" class="form-label fw-bold small">Task Description & Instructions</label>
                        <textarea name="description" id="task_description" class="form-control" rows="4" placeholder="Provide detailed instructions, clinical notes, patient phone numbers, or administrative requirements..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Assign Task & Notify Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
