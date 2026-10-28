<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactInquiry;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactInquiryController extends Controller
{
    /**
     * Display a listing of public contact enquiries and tickets.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $department = $request->query('department');

        $query = ContactInquiry::query();

        if ($status && in_array($status, ['new', 'in_review', 'resolved'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($department) {
            $query->where('department', $department);
        }

        $inquiries = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => ContactInquiry::count(),
            'new' => ContactInquiry::where('status', 'new')->count(),
            'in_review' => ContactInquiry::where('status', 'in_review')->count(),
            'resolved' => ContactInquiry::where('status', 'resolved')->count(),
        ];

        return view('admin.inquiries.index', [
            'inquiries' => $inquiries,
            'stats' => $stats,
            'currentStatus' => $status,
            'search' => $search,
            'department' => $department,
        ]);
    }

    /**
     * Display the full details of a specific contact message.
     */
    public function show(ContactInquiry $inquiry): View
    {
        $auditLogs = AuditLog::where('resource_type', 'contact_inquiry')
            ->where('resource_id', (string) $inquiry->id)
            ->latest('id')
            ->get();

        return view('admin.inquiries.show', [
            'inquiry' => $inquiry,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Update the resolution status of the inquiry.
     */
    public function updateStatus(Request $request, ContactInquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:new,in_review,resolved'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = $inquiry->status;
        $inquiry->status = $validated['status'];
        $inquiry->save();

        AuditService::record(
            action: 'inquiry.status_updated',
            resourceType: 'contact_inquiry',
            resourceId: (string) $inquiry->id,
            details: [
                'ticket_number' => $inquiry->ticket_number,
                'previous_status' => $oldStatus,
                'new_status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ],
            actor: $request->user()
        );

        return back()->with('success', "Enquiry #{$inquiry->ticket_number} marked as ".ucfirst(str_replace('_', ' ', $validated['status'])).'.');
    }
}
