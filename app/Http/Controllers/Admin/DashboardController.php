<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main administrative dashboard.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $stats = [
            'appointments_count' => Appointment::count(),
            'doctors_count' => Doctor::where('is_available', true)->count(),
            'departments_count' => Department::where('is_active', true)->count(),
            'staff_count' => User::where('is_active', true)->count(),
        ];

        // SuperAdmin and Hospital Director can inspect recent security audit logs
        $recentAuditLogs = [];
        if ($user->hasRole(['super_admin', 'hospital_director'])) {
            $recentAuditLogs = AuditLog::with('user')
                ->latest('id')
                ->limit(10)
                ->get();
        }

        return view('admin.dashboard', [
            'currentUser' => $user,
            'stats' => $stats,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}
