<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display authenticated user's notification inbox.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $status = $request->input('status', 'all');
        $category = $request->input('category');

        $query = match ($status) {
            'unread' => $user->unreadNotifications(),
            'read' => $user->readNotifications(),
            default => $user->notifications(),
        };

        if ($category) {
            $query->where('data->category', $category);
        }

        $notifications = $query->paginate(15)->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();

        // Categorized summary counts for the user's current notifications
        $categoryCounts = [
            'appointment' => (clone $user->notifications())->where('data->category', 'appointment')->count(),
            'doctor_profile' => (clone $user->notifications())->where('data->category', 'doctor_profile')->count(),
            'pharmacy' => (clone $user->notifications())->where('data->category', 'pharmacy')->count(),
            'contact' => (clone $user->notifications())->where('data->category', 'contact')->count(),
            'clinical' => (clone $user->notifications())->where('data->category', 'clinical')->count(),
        ];

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'status' => $status,
            'selectedCategory' => $category,
            'categoryCounts' => $categoryCounts,
        ]);
    }

    /**
     * Return unread notification count for authenticated staff member (for AJAX / header polling).
     */
    public function unreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'unread_count' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }

    /**
     * Mark a single notification as read (strictly owned by the authenticated user).
     */
    public function markAsRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($id);

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        $actionUrl = $notification->data['action_url'] ?? null;
        if ($actionUrl && $request->has('navigate')) {
            return redirect($actionUrl);
        }

        return redirect()->back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('status', 'All notifications marked as read.');
    }

    /**
     * Super Administrator & Hospital Director category-level oversight dashboard.
     */
    public function oversight(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'hospital_director'])) {
            abort(403, 'Unauthorized. Notification oversight is restricted to Hospital Administration.');
        }

        $selectedCategory = $request->input('category');
        $status = $request->input('status');

        $query = DB::table('notifications')
            ->join('users', 'notifications.notifiable_id', '=', 'users.id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.read_at',
                'notifications.created_at',
                'users.name as recipient_name',
                'users.role as recipient_role'
            )
            ->where('notifications.notifiable_type', User::class);

        if ($selectedCategory) {
            $query->where('notifications.data->category', $selectedCategory);
        }

        if ($status === 'read') {
            $query->whereNotNull('notifications.read_at');
        } elseif ($status === 'unread') {
            $query->whereNull('notifications.read_at');
        }

        $notifications = $query->orderByDesc('notifications.created_at')->paginate(20)->withQueryString();

        // Decode JSON data on collection items for view rendering
        $notifications->getCollection()->transform(function ($item) {
            $item->data = json_decode($item->data, true) ?: [];

            return $item;
        });

        // Overview metrics by category
        $all = DB::table('notifications')->get();
        $categoryBreakdown = [
            'appointment' => 0,
            'doctor_profile' => 0,
            'pharmacy' => 0,
            'contact' => 0,
            'clinical' => 0,
            'system' => 0,
        ];

        $totalDispatched = $all->count();
        $totalUnread = 0;

        foreach ($all as $entry) {
            $data = json_decode($entry->data, true) ?: [];
            $cat = $data['category'] ?? 'system';
            if (isset($categoryBreakdown[$cat])) {
                $categoryBreakdown[$cat]++;
            } else {
                $categoryBreakdown['system']++;
            }

            if ($entry->read_at === null) {
                $totalUnread++;
            }
        }

        return view('admin.notifications.oversight', [
            'notifications' => $notifications,
            'selectedCategory' => $selectedCategory,
            'status' => $status,
            'totalDispatched' => $totalDispatched,
            'totalUnread' => $totalUnread,
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }
}
