<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main administrative dashboard with database-backed metrics.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Core Hospital Operational Statistics
        $totalPatients = Patient::count();
        $newPatientsLast30d = Patient::where('created_at', '>=', now()->subDays(30))->count();
        $appointmentsToday = Appointment::whereDate('appointment_date', today())->count();
        $appointmentsPending = Appointment::whereIn('status', ['pending', 'rescheduled'])->count();
        $appointmentsConfirmed = Appointment::where('status', 'confirmed')->count();
        $appointmentsCompleted = Appointment::where('status', 'completed')->count();
        $appointmentsTotal = Appointment::count();

        $visitsToday = Visit::whereDate('checked_in_at', today())->count();
        $consultationsSigned = Consultation::where('status', 'signed')->count();
        $pharmacyPendingRx = Prescription::whereIn('status', ['finalized', 'partially_dispensed'])->count();
        $pharmacyLowStock = Medicine::lowStock()->count();
        $pharmacyExpiringBatches = MedicineBatch::where('is_active', true)
            ->where('quantity_current', '>', 0)
            ->whereBetween('expiry_date', [today(), today()->addDays(90)])
            ->count();

        $stats = [
            'appointments_count' => $appointmentsTotal,
            'doctors_count' => Doctor::where('is_available', true)->count(),
            'departments_count' => Department::where('is_active', true)->count(),
            'staff_count' => User::where('is_active', true)->count(),
            // Enriched Hospital Operational Metrics
            'total_patients' => $totalPatients,
            'new_patients_30d' => $newPatientsLast30d,
            'appointments_today' => $appointmentsToday,
            'appointments_pending' => $appointmentsPending,
            'appointments_confirmed' => $appointmentsConfirmed,
            'appointments_completed' => $appointmentsCompleted,
            'visits_today' => $visitsToday,
            'consultations_signed' => $consultationsSigned,
            'pharmacy_pending_rx' => $pharmacyPendingRx,
            'pharmacy_low_stock' => $pharmacyLowStock,
            'pharmacy_expiring_batches' => $pharmacyExpiringBatches,
        ];

        // 2. Chart: Appointment Status Distribution (Donut Chart)
        $statusCounts = Appointment::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusCategories = [
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'rescheduled' => 'Rescheduled',
            'cancelled' => 'Cancelled',
        ];

        $statusLabels = [];
        $statusSeries = [];
        foreach ($statusCategories as $statusKey => $label) {
            $count = (int) ($statusCounts[$statusKey] ?? 0);
            if ($count > 0 || $appointmentsTotal === 0) {
                $statusLabels[] = $label;
                $statusSeries[] = $count;
            }
        }
        if (empty($statusSeries)) {
            $statusLabels = ['No Appointments'];
            $statusSeries = [0];
        }

        // 3. Chart: Weekly Patient & Appointment Flow (Bar Chart, last 7 days)
        $weeklyDays = collect();
        for ($i = 6; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $weeklyDays->push([
                'date' => $d->toDateString(),
                'day' => $d->format('D'),
                'label' => $d->format('M j'),
            ]);
        }
        $weeklyDateStrings = $weeklyDays->pluck('date')->toArray();

        $weeklyApptsData = Appointment::whereIn('appointment_date', $weeklyDateStrings)
            ->selectRaw('DATE(appointment_date) as d, count(*) as count')
            ->groupBy('d')
            ->pluck('count', 'd')
            ->toArray();

        $weeklyVisitsData = Visit::whereBetween('checked_in_at', [today()->subDays(6)->startOfDay(), today()->endOfDay()])
            ->selectRaw('DATE(checked_in_at) as d, count(*) as count')
            ->groupBy('d')
            ->pluck('count', 'd')
            ->toArray();

        $weeklyCategories = [];
        $weeklyApptSeries = [];
        $weeklyVisitSeries = [];
        foreach ($weeklyDays as $dayInfo) {
            $weeklyCategories[] = $dayInfo['day'];
            $weeklyApptSeries[] = (int) ($weeklyApptsData[$dayInfo['date']] ?? 0);
            $weeklyVisitSeries[] = (int) ($weeklyVisitsData[$dayInfo['date']] ?? 0);
        }

        // 4. Chart: 30-Day Activity Trend (Area Spline Chart)
        $monthlyDays = collect();
        for ($i = 29; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $monthlyDays->push([
                'date' => $d->toDateString(),
                'label' => $d->format('d M'),
            ]);
        }

        $monthlyApptsData = Appointment::whereBetween('appointment_date', [today()->subDays(29)->toDateString(), today()->toDateString()])
            ->selectRaw('DATE(appointment_date) as d, count(*) as count')
            ->groupBy('d')
            ->pluck('count', 'd')
            ->toArray();

        $monthlyVisitsData = Visit::whereBetween('checked_in_at', [today()->subDays(29)->startOfDay(), today()->endOfDay()])
            ->selectRaw('DATE(checked_in_at) as d, count(*) as count')
            ->groupBy('d')
            ->pluck('count', 'd')
            ->toArray();

        $monthlyCategories = [];
        $monthlyApptSeries = [];
        $monthlyVisitSeries = [];
        foreach ($monthlyDays as $dayInfo) {
            $monthlyCategories[] = $dayInfo['label'];
            $monthlyApptSeries[] = (int) ($monthlyApptsData[$dayInfo['date']] ?? 0);
            $monthlyVisitSeries[] = (int) ($monthlyVisitsData[$dayInfo['date']] ?? 0);
        }

        // 5. Department Workload Distribution (progress indicators)
        $departments = Department::where('is_active', true)
            ->withCount(['appointments', 'visits'])
            ->get();
        $totalDeptActivity = $departments->sum(fn ($d) => $d->appointments_count + $d->visits_count);
        $departmentDistribution = $departments->map(function ($dept) use ($totalDeptActivity) {
            $activity = $dept->appointments_count + $dept->visits_count;
            $percentage = $totalDeptActivity > 0 ? round(($activity / $totalDeptActivity) * 100) : 0;

            return [
                'name' => $dept->name,
                'appointments' => $dept->appointments_count,
                'visits' => $dept->visits_count,
                'total' => $activity,
                'percentage' => $percentage,
            ];
        })->sortByDesc('total')->take(5)->values();

        // 6. Recent Appointments Table (Last 6 appointments)
        $recentAppointments = Appointment::with(['patient', 'department', 'doctor'])
            ->latest('id')
            ->limit(6)
            ->get();

        // 7. Critical Pharmacy Formulary & Stock Watch (Top 5 alert medicines)
        $inventoryAlerts = Medicine::with(['batches' => function ($query) {
            $query->where('is_active', true)->where('expiry_date', '>=', today());
        }])
            ->get()
            ->map(function ($medicine) {
                return [
                    'id' => $medicine->id,
                    'code' => $medicine->code,
                    'brand_name' => $medicine->brand_name,
                    'generic_name' => $medicine->generic_name,
                    'category' => $medicine->category,
                    'available_stock' => $medicine->available_stock,
                    'reorder_level' => $medicine->reorder_level,
                    'is_low' => $medicine->isLowStock(),
                    'status' => $medicine->available_stock <= 0 ? 'Out of Stock' : ($medicine->isLowStock() ? 'Low Stock' : 'In Stock'),
                ];
            })
            ->sortBy(function ($item) {
                if ($item['available_stock'] <= 0) {
                    return 0;
                }
                if ($item['is_low']) {
                    return 1;
                }

                return 2;
            })
            ->take(5)
            ->values();

        // 8. SuperAdmin and Hospital Director: Recent Security & Clinical Audit Logs
        $recentAuditLogs = [];
        if ($user->hasRole(['super_admin', 'hospital_director'])) {
            $recentAuditLogs = AuditLog::with('user')
                ->latest('id')
                ->limit(8)
                ->get();
        }

        // 9. Role-Aware Task Count for Authenticated User
        $roleTasksCount = match (true) {
            $user->hasRole('doctor') => Visit::where('doctor_id', $user->doctor?->id)->whereIn('status', ['waiting_triage', 'triaged'])->count(),
            $user->hasRole('nurse') => Visit::whereDate('checked_in_at', today())->where('status', 'waiting_triage')->count(),
            $user->hasRole('pharmacist') => $pharmacyPendingRx,
            $user->hasRole('receptionist') => $appointmentsPending,
            default => 0,
        };

        return view('admin.dashboard', [
            'currentUser' => $user,
            'stats' => $stats,
            'recentAuditLogs' => $recentAuditLogs,
            'statusLabels' => $statusLabels,
            'statusSeries' => $statusSeries,
            'weeklyCategories' => $weeklyCategories,
            'weeklyApptSeries' => $weeklyApptSeries,
            'weeklyVisitSeries' => $weeklyVisitSeries,
            'monthlyCategories' => $monthlyCategories,
            'monthlyApptSeries' => $monthlyApptSeries,
            'monthlyVisitSeries' => $monthlyVisitSeries,
            'departmentDistribution' => $departmentDistribution,
            'recentAppointments' => $recentAppointments,
            'inventoryAlerts' => $inventoryAlerts,
            'roleTasksCount' => $roleTasksCount,
        ]);
    }
}
