<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StaffTask;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffTaskController extends Controller
{
    /**
     * Display a listing of hospital staff tasks.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $isExecutive = $user->hasRole(['super_admin', 'hospital_director']);

        $status = $request->query('status');
        $priority = $request->query('priority');
        $scope = $request->query('scope', $isExecutive ? 'all' : 'assigned_to_me');
        $search = $request->query('search');

        $query = StaffTask::with(['assignee', 'creator']);

        if (! $isExecutive && $scope === 'all') {
            $scope = 'assigned_to_me';
        }

        if ($scope === 'assigned_to_me') {
            $query->where('assigned_to', $user->id);
        } elseif ($scope === 'created_by_me') {
            $query->where('assigned_by', $user->id);
        }

        if ($status && in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        if ($priority && in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $query->where('priority', $priority);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => $isExecutive ? StaffTask::count() : StaffTask::where('assigned_to', $user->id)->count(),
            'pending' => $isExecutive ? StaffTask::where('status', 'pending')->count() : StaffTask::where('assigned_to', $user->id)->where('status', 'pending')->count(),
            'in_progress' => $isExecutive ? StaffTask::where('status', 'in_progress')->count() : StaffTask::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
            'completed' => $isExecutive ? StaffTask::where('status', 'completed')->count() : StaffTask::where('assigned_to', $user->id)->where('status', 'completed')->count(),
        ];

        $staffMembers = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.tasks.index', [
            'tasks' => $tasks,
            'stats' => $stats,
            'scope' => $scope,
            'status' => $status,
            'priority' => $priority,
            'search' => $search,
            'staffMembers' => $staffMembers,
            'isExecutive' => $isExecutive,
        ]);
    }

    /**
     * Store a newly created task and dispatch assignment notification.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'related_type' => ['nullable', 'string', 'max:50'],
            'related_id' => ['nullable', 'string', 'max:100'],
        ]);

        $task = StaffTask::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'pending',
            'assigned_to' => $validated['assigned_to'],
            'assigned_by' => $user->id,
            'due_date' => $validated['due_date'] ?? null,
            'related_type' => $validated['related_type'] ?? null,
            'related_id' => $validated['related_id'] ?? null,
        ]);

        // Send notification to assignee
        NotificationService::notifyTaskAssigned($task);

        AuditService::record(
            action: 'task.created',
            resourceType: 'staff_task',
            resourceId: (string) $task->id,
            details: [
                'title' => $task->title,
                'priority' => $task->priority,
                'assigned_to' => $task->assigned_to,
                'due_date' => $task->due_date?->toDateString(),
            ],
            actor: $user
        );

        return redirect()->route('admin.tasks.show', $task->id)->with('success', 'Task has been assigned and the staff member has been notified.');
    }

    /**
     * Display comprehensive details of an individual staff task.
     */
    public function show(StaffTask $task): View
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $task->canBeUpdatedBy($user)) {
            abort(403, 'Unauthorized. You do not have permission to view or manage this staff task.');
        }

        $task->load(['assignee.department', 'creator.department']);

        $auditLogs = AuditLog::where('resource_type', 'staff_task')
            ->where('resource_id', (string) $task->id)
            ->latest('id')
            ->get();

        $staffMembers = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.tasks.show', [
            'task' => $task,
            'auditLogs' => $auditLogs,
            'staffMembers' => $staffMembers,
            'canManage' => $task->canBeManagedBy($user),
        ]);
    }

    /**
     * Update task status, priority, or reassign to another staff member.
     */
    public function update(Request $request, StaffTask $task): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $task->canBeUpdatedBy($user)) {
            abort(403, 'Unauthorized. You cannot update this staff task.');
        }

        $canManage = $task->canBeManagedBy($user);

        if ($canManage) {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:2000'],
                'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
                'status' => ['required', 'string', 'in:pending,in_progress,completed,cancelled'],
                'assigned_to' => ['required', 'integer', 'exists:users,id'],
                'due_date' => ['nullable', 'date'],
            ]);
        } else {
            // Assignee can only update status
            $validated = $request->validate([
                'status' => ['required', 'string', 'in:pending,in_progress,completed,cancelled'],
            ]);
        }

        $oldStatus = $task->status;
        $oldAssigneeId = $task->assigned_to;
        $previousAssignee = $task->assignee;

        if ($canManage) {
            $task->title = $validated['title'];
            $task->description = $validated['description'] ?? null;
            $task->priority = $validated['priority'];
            $task->assigned_to = $validated['assigned_to'];
            $task->due_date = $validated['due_date'] ?? null;
        }

        $task->status = $validated['status'];

        if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
            $task->completed_at = now();
        } elseif ($validated['status'] !== 'completed') {
            $task->completed_at = null;
        }

        $task->save();

        // Check if reassigned to another staff member
        if ($canManage && (int) $oldAssigneeId !== (int) $validated['assigned_to']) {
            $newAssignee = User::find($validated['assigned_to']);
            if ($newAssignee) {
                NotificationService::notifyTaskReassigned($task, $previousAssignee, $newAssignee);
            }
        }

        AuditService::record(
            action: 'task.updated',
            resourceType: 'staff_task',
            resourceId: (string) $task->id,
            details: [
                'title' => $task->title,
                'old_status' => $oldStatus,
                'new_status' => $task->status,
                'assigned_to' => $task->assigned_to,
            ],
            actor: $user
        );

        return back()->with('success', 'Staff task updated successfully.');
    }
}
