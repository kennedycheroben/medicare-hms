<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;
use App\Models\Visit;

class ConsultationPolicy
{
    /**
     * Determine whether the user can view the consultation queue.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasValidDoctorProfile();
    }

    /**
     * Determine whether the user can start a consultation for the given visit.
     */
    public function start(User $user, Visit $visit): bool
    {
        if (! $user->hasValidDoctorProfile()) {
            return false;
        }

        if (! in_array($visit->status, ['triaged', 'in_consultation'], true)) {
            return false;
        }

        if ($visit->doctor_id && (int) $visit->doctor_id !== (int) $user->doctor_id) {
            return false;
        }

        // For unassigned visits, enforce department authorization
        if (! $visit->doctor_id) {
            $doctorDepartmentId = $user->getClinicalDepartmentId();

            if (! $doctorDepartmentId) {
                return false;
            }

            if ($visit->department_id && (int) $visit->department_id !== (int) $doctorDepartmentId) {
                return false;
            }
        }

        // If existing consultation exists, only author clinician can resume
        if ($visit->consultation && $visit->consultation->clinician_id !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view the consultation.
     */
    public function view(User $user, Consultation $consultation): bool
    {
        if (! $user->hasValidDoctorProfile()) {
            return false;
        }

        // Confidentiality: draft records only visible to authoring clinician
        if ($consultation->isDraft()) {
            return $consultation->clinician_id === $user->id;
        }

        // Signed records are viewable by attending licensed physicians for clinical continuity
        return $consultation->isSigned();
    }

    /**
     * Determine whether the user can update the consultation draft.
     */
    public function update(User $user, Consultation $consultation): bool
    {
        if (! $user->hasValidDoctorProfile()) {
            return false;
        }

        return $consultation->canBeEditedBy($user);
    }

    /**
     * Determine whether the user can sign the consultation.
     */
    public function sign(User $user, Consultation $consultation): bool
    {
        if (! $user->hasValidDoctorProfile()) {
            return false;
        }

        return $consultation->canBeEditedBy($user);
    }

    /**
     * Determine whether the user can add an addendum to the consultation.
     */
    public function addendum(User $user, Consultation $consultation): bool
    {
        if (! $user->hasValidDoctorProfile()) {
            return false;
        }

        return $consultation->isSigned();
    }
}
