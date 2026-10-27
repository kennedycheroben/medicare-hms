<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DoctorProfileController extends Controller
{
    /**
     * Show the authenticated doctor's profile editor.
     */
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole('doctor')) {
            abort(403, 'Unauthorized. Profile management is strictly for medical doctors.');
        }

        $doctor = $user->doctor;

        if (! $doctor) {
            $department = Department::where('is_active', true)->first();
            $slugBase = Str::slug($user->name);
            $slug = $slugBase;
            $counter = 1;
            while (Doctor::where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$counter}";
                $counter++;
            }

            $doctor = Doctor::create([
                'name' => $user->name,
                'slug' => $slug,
                'department_id' => $user->department_id ?? $department?->id,
                'specialty' => 'Medical Specialist',
                'qualifications' => 'MBChB',
                'experience_years' => 1,
                'bio' => 'Hospital clinical specialist.',
                'status' => 'draft',
                'is_published' => false,
                'is_available' => true,
            ]);

            $user->update(['doctor_id' => $doctor->id]);
        }

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('doctor.profile.edit', [
            'doctor' => $doctor,
            'departments' => $departments,
            'user' => $user,
        ]);
    }

    /**
     * Update the authenticated doctor's profile.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole('doctor')) {
            abort(403, 'Unauthorized.');
        }

        $doctor = $user->doctor;

        if (! $doctor) {
            abort(404, 'Doctor profile record not found.');
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
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_available' => ['required', 'boolean'],
            'action' => ['nullable', 'string', 'in:save_draft,submit_review'],
        ]);

        $submitAction = $validated['action'] ?? 'submit_review';
        $newStatus = $submitAction === 'save_draft' ? 'draft' : 'pending_review';

        $data = [
            'name' => $validated['name'],
            'department_id' => $validated['department_id'],
            'specialty' => $validated['specialty'],
            'qualifications' => $validated['qualifications'],
            'experience_years' => $validated['experience_years'],
            'bio' => $validated['bio'],
            'languages' => $validated['languages'] ?? $doctor->languages ?? 'English, Swahili',
            'consultation_days' => $validated['consultation_days'] ?? $doctor->consultation_days ?? 'Mon - Fri',
            'consultation_hours' => $validated['consultation_hours'] ?? $doctor->consultation_hours ?? '09:00 AM - 04:00 PM',
            'room_number' => $validated['room_number'] ?? null,
            'is_available' => $validated['is_available'],
            'status' => $newStatus,
            // Doctor edits require admin re-approval; never allow self-publishing
            'is_published' => false,
        ];

        // Handle profile photo upload safely
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('doctors', $filename, 'public');

            // Delete old file if on public disk and not shared
            if ($doctor->image && Storage::disk('public')->exists($doctor->image)) {
                $isReferencedByOthers = Doctor::where('image', $doctor->image)
                    ->where('id', '!=', $doctor->id)
                    ->exists();

                if (! $isReferencedByOthers) {
                    Storage::disk('public')->delete($doctor->image);
                }
            }

            $data['image'] = $path;
        }

        $doctor->update($data);

        if ($newStatus === 'pending_review') {
            NotificationService::notifyDoctorProfileSubmitted($doctor);
        }

        AuditService::record(
            action: $newStatus === 'pending_review' ? 'doctor.profile_submitted' : 'doctor.profile_draft_saved',
            resourceType: 'doctor',
            resourceId: (string) $doctor->id,
            details: [
                'doctor_id' => $doctor->id,
                'name' => $doctor->name,
                'status' => $newStatus,
            ],
            outcome: 'success',
            actor: $user
        );

        $message = $newStatus === 'pending_review'
            ? 'Your professional profile has been submitted to the Super Administrator for review and publication.'
            : 'Your profile changes have been saved as a draft.';

        return redirect()->route('doctor.profile.edit')->with('status', $message);
    }
}
