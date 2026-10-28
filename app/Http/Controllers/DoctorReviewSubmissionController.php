<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\Patient;
use App\Models\Visit;
use App\Rules\ValidPhoneNumber;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DoctorReviewSubmissionController extends Controller
{
    /**
     * Store a verified patient review for a completed clinical encounter.
     */
    public function store(Request $request, Doctor $doctor): RedirectResponse
    {
        $validated = $request->validate([
            'mrn' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', new ValidPhoneNumber],
            'visit_number' => ['required', 'string', 'max:50'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'display_name_type' => ['required', 'in:full_name,initials,anonymous'],
            'public_consent' => ['nullable', 'boolean'],
        ]);

        $normalizedPhone = ValidPhoneNumber::normalize($validated['phone']);

        // 1. Verify patient identity via MRN and registered telephone number
        /** @var Patient|null $patient */
        $patient = Patient::where('mrn', trim($validated['mrn']))
            ->where(function ($q) use ($normalizedPhone, $validated) {
                $q->where('phone', $normalizedPhone)
                    ->orWhere('phone', $validated['phone']);
            })
            ->first();

        if (! $patient) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['mrn' => 'Patient identity could not be verified with the provided Medical Record Number and telephone number.']);
        }

        // 2. Verify clinical visit encounter belongs to this patient and this doctor
        /** @var Visit|null $visit */
        $visit = Visit::where('visit_number', trim($validated['visit_number']))
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (! $visit) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['visit_number' => "No clinical encounter matching visit #{$validated['visit_number']} was found with {$doctor->name}."]);
        }

        // 3. Must be a completed clinical visit
        if ($visit->status !== 'completed') {
            return redirect()->back()
                ->withInput()
                ->withErrors(['visit_number' => 'Only completed consultation encounters are eligible for patient review.']);
        }

        // 4. Duplicate prevention: strictly one review per visit encounter
        if (DoctorReview::where('visit_id', $visit->id)->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['visit_number' => 'A verified feedback submission for this encounter has already been recorded.']);
        }

        $publicConsent = $request->boolean('public_consent');
        $displayNameType = $validated['display_name_type'];

        // If patient did not provide explicit consent for public name, force anonymous display
        if (! $publicConsent && $displayNameType === 'full_name') {
            $displayNameType = 'anonymous';
        }

        $review = DoctorReview::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'rating' => (int) $validated['rating'],
            'comment' => strip_tags($validated['comment']),
            'public_consent' => $publicConsent,
            'display_name_type' => $displayNameType,
            'status' => 'pending_moderation',
        ]);

        AuditService::record(
            action: 'doctor_review.submitted',
            resourceType: 'doctor_review',
            resourceId: (string) $review->id,
            details: [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'visit_number' => $visit->visit_number,
                'rating' => $review->rating,
            ],
            outcome: 'success'
        );

        return redirect()->back()->with('review_status', 'Thank you for your feedback! Your review has been recorded and will appear on the physician directory once verified by hospital administration.');
    }
}
