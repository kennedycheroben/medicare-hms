<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class StaffManagementController extends Controller
{
    /**
     * Display a listing of staff accounts with search and filters.
     */
    public function index(Request $request): View
    {
        $query = User::with(['department', 'doctor']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $staffMembers = $query->orderBy('name')->paginate(15)->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'super_admins' => User::where('role', UserRole::SUPER_ADMIN)->where('is_active', true)->count(),
        ];

        return view('admin.staff.index', [
            'staffMembers' => $staffMembers,
            'departments' => $departments,
            'roles' => UserRole::cases(),
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'department_id', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new staff account.
     */
    public function create(): View
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::where('is_available', true)->orderBy('name')->get();

        return view('admin.staff.create', [
            'roles' => UserRole::cases(),
            'departments' => $departments,
            'doctors' => $doctors,
        ]);
    }

    /**
     * Store a newly created staff account in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', new Enum(UserRole::class)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'], // Casts to hashed automatically
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'doctor_id' => $validated['doctor_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::record(
            action: 'staff.created',
            resourceType: 'user',
            resourceId: (string) $user->id,
            details: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'department_id' => $user->department_id,
            ],
            outcome: 'success'
        );

        return redirect()->route('admin.staff.index')
            ->with('status', "Staff account for {$user->name} has been successfully created.");
    }

    /**
     * Show the form for editing the specified staff account.
     */
    public function edit(User $staff): View
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::where('is_available', true)->orderBy('name')->get();

        return view('admin.staff.edit', [
            'staff' => $staff,
            'roles' => UserRole::cases(),
            'departments' => $departments,
            'doctors' => $doctors,
        ]);
    }

    /**
     * Update the specified staff account in storage.
     */
    public function update(Request $request, User $staff): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'role' => ['required', new Enum(UserRole::class)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'is_active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $newRole = UserRole::from($validated['role']);
        $newActiveStatus = $request->boolean('is_active');

        // Security Safeguard 1: Prevent removing or demoting the last active SuperAdmin
        if ($staff->isSuperAdmin() && $newRole !== UserRole::SUPER_ADMIN) {
            $otherSuperAdminsCount = User::where('role', UserRole::SUPER_ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $staff->id)
                ->count();

            if ($otherSuperAdminsCount === 0) {
                return back()->withInput()->withErrors([
                    'role' => 'Cannot reassign role. This is the only active Super Administrator account in the system.',
                ]);
            }
        }

        // Security Safeguard 2: Prevent deactivating the last active SuperAdmin
        if ($staff->isSuperAdmin() && ! $newActiveStatus) {
            $otherSuperAdminsCount = User::where('role', UserRole::SUPER_ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $staff->id)
                ->count();

            if ($otherSuperAdminsCount === 0) {
                return back()->withInput()->withErrors([
                    'is_active' => 'Cannot deactivate this account. At least one active Super Administrator is required.',
                ]);
            }
        }

        // Security Safeguard 3: Prevent self-lockout or self-demotion
        if ($currentUser->id === $staff->id && ! $newActiveStatus) {
            return back()->withInput()->withErrors([
                'is_active' => 'You cannot deactivate your own administrative account.',
            ]);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $newRole,
            'department_id' => $validated['department_id'] ?? null,
            'doctor_id' => $validated['doctor_id'] ?? null,
            'is_active' => $newActiveStatus,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $staff->update($updateData);

        AuditService::record(
            action: 'staff.updated',
            resourceType: 'user',
            resourceId: (string) $staff->id,
            details: [
                'email' => $staff->email,
                'role' => $staff->role->value,
                'is_active' => $staff->is_active,
                'password_reset' => ! empty($validated['password']),
            ],
            outcome: 'success'
        );

        return redirect()->route('admin.staff.index')
            ->with('status', "Staff account for {$staff->name} updated successfully.");
    }

    /**
     * Quick toggle account activation status.
     */
    public function toggleStatus(Request $request, User $staff): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->id === $staff->id) {
            return back()->withErrors([
                'status' => 'You cannot deactivate your own administrative account.',
            ]);
        }

        // Check if deactivating the last SuperAdmin
        if ($staff->isSuperAdmin() && $staff->isActive()) {
            $activeSuperAdmins = User::where('role', UserRole::SUPER_ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $staff->id)
                ->count();

            if ($activeSuperAdmins === 0) {
                return back()->withErrors([
                    'status' => 'Cannot deactivate the last active Super Administrator account.',
                ]);
            }
        }

        $newStatus = ! $staff->isActive();
        $staff->update(['is_active' => $newStatus]);

        $action = $newStatus ? 'staff.activated' : 'staff.deactivated';

        AuditService::record(
            action: $action,
            resourceType: 'user',
            resourceId: (string) $staff->id,
            details: [
                'target_email' => $staff->email,
                'new_status' => $newStatus ? 'active' : 'inactive',
            ],
            outcome: 'success'
        );

        $statusText = $newStatus ? 'activated' : 'deactivated';

        return back()->with('status', "Account for {$staff->name} has been {$statusText}.");
    }
}
