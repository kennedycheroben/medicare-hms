<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DoctorManagementController extends Controller
{
    /**
     * Display listing of doctors with publication and approval statuses.
     */
    public function index(Request $request): View
    {
        $query = Doctor::with(['department', 'approver'])
            ->withCount(['reviews', 'approvedReviews']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%");
            });
        }

        if ($deptId = $request->input('department_id')) {
            $query->where('department_id', $deptId);
        }

        if ($status = $request->input('status')) {
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'unpublished') {
                $query->where('is_published', false);
            } elseif (in_array($status, ['draft', 'pending_review', 'approved', 'needs_revision'])) {
                $query->where('status', $status);
            }
        }

        $doctors = $query->orderBy('name')->paginate(15)->withQueryString();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $pendingReviewsCount = DoctorReview::pending()->count();
        $pendingProfilesCount = Doctor::where('status', 'pending_review')->count();

        return view('admin.doctors.index', [
            'doctors' => $doctors,
            'departments' => $departments,
            'pendingReviewsCount' => $pendingReviewsCount,
            'pendingProfilesCount' => $pendingProfilesCount,
            'search' => $search,
            'departmentId' => $deptId,
            'status' => $status,
        ]);
    }

    /**
     * Show edit doctor profile form.
     */
    public function edit(Doctor $doctor): View
    {
        $doctor->load(['department', 'approver']);
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.doctors.edit', [
            'doctor' => $doctor,
            'departments' => $departments,
        ]);
    }

    /**
     * Update doctor professional profile.
     */
    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'hospital_director'])) {
            abort(403, 'Unauthorized. Profile edits are restricted to hospital administration.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'exists:departments,id'],
            'specialty' => ['required', 'string', 'max:150'],
            'qualifications' => ['required', 'string', 'max:255'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:70'],
            'bio' => ['required', 'string', 'max:2000'],
            'languages' => ['nullable', 'string', 'max:255'],
            'consultation_days' => ['nullable', 'string', 'max:255'],
            'consultation_hours' => ['nullable', 'string', 'max:255'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'is_available' => ['required', 'boolean'],
        ]);

        $doctor->update($validated);

        AuditService::record(
            action: 'doctor.profile_updated',
            resourceType: 'doctor',
            resourceId: (string) $doctor->id,
            details: [
                'name' => $doctor->name,
                'specialty' => $doctor->specialty,
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->route('admin.doctors.index')
            ->with('status', "Doctor profile for {$doctor->name} updated successfully.");
    }

    /**
     * Approve and publish doctor profile for public view.
     */
    public function publish(Request $request, Doctor $doctor): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'hospital_director'])) {
            abort(403, 'Unauthorized. Publication approval requires Super Admin or Hospital Director authority.');
        }

        // Validate complete profile before approving publication
        if (empty($doctor->name) || empty($doctor->specialty) || empty($doctor->qualifications) || empty($doctor->bio) || ! $doctor->department_id) {
            return redirect()->back()
                ->withErrors(['publication' => 'Cannot publish incomplete profile. Name, specialty, qualifications, biography, and department are required.']);
        }

        $validated = $request->validate([
            'publication_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $doctor->update([
            'status' => 'approved',
            'is_published' => true,
            'published_at' => now(),
            'approved_by' => $user->id,
            'publication_notes' => $validated['publication_notes'] ?? 'Approved for public listing',
        ]);

        AuditService::record(
            action: 'doctor.published',
            resourceType: 'doctor',
            resourceId: (string) $doctor->id,
            details: [
                'name' => $doctor->name,
                'approved_by' => $user->name,
            ],
            outcome: 'success',
            actor: $user
        );

        NotificationService::notifyDoctorProfileApproved($doctor);

        return redirect()->back()
            ->with('status', "Doctor profile for {$doctor->name} has been verified and published to the website.");
    }

    /**
     * Dedicated approval endpoint (alias for publish).
     */
    public function approveProfile(Request $request, Doctor $doctor): RedirectResponse
    {
        return $this->publish($request, $doctor);
    }

    /**
     * Return doctor profile for correction with feedback.
     */
    public function returnForCorrection(Request $request, Doctor $doctor): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'hospital_director'])) {
            abort(403, 'Unauthorized. Review action requires Super Admin or Hospital Director authority.');
        }

        $validated = $request->validate([
            'publication_notes' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $doctor->update([
            'status' => 'needs_revision',
            'is_published' => false,
            'publication_notes' => $validated['publication_notes'],
        ]);

        AuditService::record(
            action: 'doctor.returned_for_correction',
            resourceType: 'doctor',
            resourceId: (string) $doctor->id,
            details: [
                'name' => $doctor->name,
                'reason' => $validated['publication_notes'],
                'reviewer' => $user->name,
            ],
            outcome: 'success',
            actor: $user
        );

        NotificationService::notifyDoctorProfileReturned($doctor, $validated['publication_notes']);

        return redirect()->back()
            ->with('status', "Doctor profile for {$doctor->name} has been returned for correction.");
    }

    /**
     * Unpublish doctor profile, hiding it from public view.
     */
    public function unpublish(Request $request, Doctor $doctor): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'hospital_director'])) {
            abort(403, 'Unauthorized.');
        }

        $doctor->update([
            'is_published' => false,
            'status' => 'draft',
            'publication_notes' => 'Unpublished by administrative action',
        ]);

        AuditService::record(
            action: 'doctor.unpublished',
            resourceType: 'doctor',
            resourceId: (string) $doctor->id,
            details: ['name' => $doctor->name],
            outcome: 'success',
            actor: $user
        );

        return redirect()->back()
            ->with('status', "Doctor profile for {$doctor->name} has been unpublished.");
    }

    /**
     * Display review moderation dashboard.
     */
    public function reviews(Request $request): View
    {
        $status = $request->input('status', 'pending_moderation');

        $query = DoctorReview::with(['doctor.department', 'patient', 'visit', 'moderator'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $reviews = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => DoctorReview::where('status', 'pending_moderation')->count(),
            'approved' => DoctorReview::where('status', 'approved')->count(),
            'rejected' => DoctorReview::where('status', 'rejected')->count(),
        ];

        return view('admin.doctors.reviews', [
            'reviews' => $reviews,
            'status' => $status,
            'stats' => $stats,
        ]);
    }

    /**
     * Approve patient review for public display.
     */
    public function approveReview(Request $request, DoctorReview $review): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'hospital_director'])) {
            abort(403, 'Unauthorized.');
        }

        $review->update([
            'status' => 'approved',
            'moderated_by' => $user->id,
            'moderated_at' => now(),
            'moderation_notes' => $request->input('moderation_notes', 'Approved for publication'),
        ]);

        // Recalculate official doctor rating from approved reviews
        $review->doctor->recalculateRating();

        AuditService::record(
            action: 'doctor_review.approved',
            resourceType: 'doctor_review',
            resourceId: (string) $review->id,
            details: [
                'doctor_id' => $review->doctor_id,
                'rating' => $review->rating,
                'author_display' => $review->public_author_name,
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->back()->with('status', 'Patient review has been approved and published.');
    }

    /**
     * Reject patient review.
     */
    public function rejectReview(Request $request, DoctorReview $review): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'hospital_director'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'moderation_notes' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $review->update([
            'status' => 'rejected',
            'moderated_by' => $user->id,
            'moderated_at' => now(),
            'moderation_notes' => $validated['moderation_notes'],
        ]);

        $review->doctor->recalculateRating();

        AuditService::record(
            action: 'doctor_review.rejected',
            resourceType: 'doctor_review',
            resourceId: (string) $review->id,
            details: [
                'doctor_id' => $review->doctor_id,
                'reason' => $validated['moderation_notes'],
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->back()->with('status', 'Patient review rejected with documentation.');
    }
}
