<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ContactInquiry;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\User;
use App\Notifications\HospitalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Dispatch notification for new public appointment booking.
     */
    public static function notifyAppointmentCreated(Appointment $appointment): void
    {
        $recipients = User::where('is_active', true)
            ->whereIn('role', [UserRole::RECEPTIONIST->value, UserRole::SUPER_ADMIN->value])
            ->get();

        $reference = $appointment->reference_code ?? $appointment->appointment_number ?? '#'.$appointment->id;
        $deptName = $appointment->department?->name ?? 'General Intake';

        $notification = new HospitalNotification(
            category: 'appointment',
            eventType: 'appointment.created',
            title: "New Appointment Request ({$reference})",
            message: "A new appointment request for {$deptName} requires front-desk triage and review.",
            actionUrl: route('admin.appointments.show', $appointment->id),
            recordType: 'appointment',
            recordId: (string) $appointment->id
        );

        Notification::send($recipients, $notification);
    }

    /**
     * Dispatch notification when an appointment is assigned to a specific doctor.
     */
    public static function notifyAppointmentAssigned(Appointment $appointment): void
    {
        $recipientIds = [];

        // Notify assigned doctor's user account
        if ($appointment->doctor_id) {
            $doctorUser = User::where('is_active', true)
                ->where('doctor_id', $appointment->doctor_id)
                ->first();

            if ($doctorUser) {
                $recipientIds[] = $doctorUser->id;
            }
        }

        // Also notify receptionists and super admin for coordination
        $staff = User::where('is_active', true)
            ->whereIn('role', [UserRole::RECEPTIONIST->value, UserRole::SUPER_ADMIN->value])
            ->pluck('id')
            ->toArray();

        $allRecipientIds = array_unique(array_merge($recipientIds, $staff));
        $recipients = User::whereIn('id', $allRecipientIds)->get();

        $reference = $appointment->reference_code ?? $appointment->appointment_number ?? '#'.$appointment->id;
        $doctorName = $appointment->doctor?->name ?? 'Doctor';

        $notification = new HospitalNotification(
            category: 'appointment',
            eventType: 'appointment.assigned',
            title: "Appointment Assigned ({$reference})",
            message: "Appointment {$reference} has been assigned to {$doctorName} for {$appointment->appointment_date}.",
            actionUrl: route('admin.appointments.show', $appointment->id),
            recordType: 'appointment',
            recordId: (string) $appointment->id
        );

        Notification::send($recipients, $notification);
    }

    /**
     * Dispatch notification when appointment status is cancelled or rescheduled.
     */
    public static function notifyAppointmentStatusChanged(Appointment $appointment, string $status, ?string $reason = null): void
    {
        $recipientIds = [];

        if ($appointment->doctor_id) {
            $doctorUser = User::where('is_active', true)
                ->where('doctor_id', $appointment->doctor_id)
                ->first();

            if ($doctorUser) {
                $recipientIds[] = $doctorUser->id;
            }
        }

        $staff = User::where('is_active', true)
            ->whereIn('role', [UserRole::RECEPTIONIST->value, UserRole::SUPER_ADMIN->value])
            ->pluck('id')
            ->toArray();

        $allRecipientIds = array_unique(array_merge($recipientIds, $staff));
        $recipients = User::whereIn('id', $allRecipientIds)->get();

        $reference = $appointment->reference_code ?? $appointment->appointment_number ?? '#'.$appointment->id;
        $label = ucfirst($status);

        $notification = new HospitalNotification(
            category: 'appointment',
            eventType: "appointment.{$status}",
            title: "Appointment {$label} ({$reference})",
            message: "Appointment {$reference} scheduled for {$appointment->appointment_date} has been {$status}.".($reason ? " Reason: {$reason}" : ''),
            actionUrl: route('admin.appointments.show', $appointment->id),
            recordType: 'appointment',
            recordId: (string) $appointment->id
        );

        Notification::send($recipients, $notification);
    }

    /**
     * Dispatch notification when a doctor submits their profile for administrative review.
     */
    public static function notifyDoctorProfileSubmitted(Doctor $doctor): void
    {
        $recipients = User::where('is_active', true)
            ->whereIn('role', [UserRole::SUPER_ADMIN->value, UserRole::HOSPITAL_DIRECTOR->value])
            ->get();

        $notification = new HospitalNotification(
            category: 'doctor_profile',
            eventType: 'doctor_profile.submitted',
            title: "Doctor Profile Submitted: {$doctor->name}",
            message: "{$doctor->name} has submitted their clinical profile for review and website publication.",
            actionUrl: route('admin.doctors.index', ['status' => 'pending_review']),
            recordType: 'doctor',
            recordId: (string) $doctor->id
        );

        Notification::send($recipients, $notification);
    }

    /**
     * Dispatch notification to a doctor when their profile is approved and published.
     */
    public static function notifyDoctorProfileApproved(Doctor $doctor): void
    {
        $doctorUser = User::where('is_active', true)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (! $doctorUser) {
            return;
        }

        $notification = new HospitalNotification(
            category: 'doctor_profile',
            eventType: 'doctor_profile.approved',
            title: 'Your Doctor Profile Has Been Approved',
            message: 'Your professional credentials and biography have been approved and published to the public hospital website.',
            actionUrl: route('doctor.profile.edit'),
            recordType: 'doctor',
            recordId: (string) $doctor->id
        );

        $doctorUser->notify($notification);
    }

    /**
     * Dispatch notification to a doctor when their profile is returned for correction.
     */
    public static function notifyDoctorProfileReturned(Doctor $doctor, string $feedback): void
    {
        $doctorUser = User::where('is_active', true)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (! $doctorUser) {
            return;
        }

        $notification = new HospitalNotification(
            category: 'doctor_profile',
            eventType: 'doctor_profile.returned',
            title: 'Action Required: Doctor Profile Returned for Revision',
            message: "Your profile requires revision: {$feedback}",
            actionUrl: route('doctor.profile.edit'),
            recordType: 'doctor',
            recordId: (string) $doctor->id
        );

        $doctorUser->notify($notification);
    }

    /**
     * Dispatch pharmacy low-stock warning with deduplication (max once per 24 hours per medicine).
     */
    public static function notifyPharmacyLowStock(Medicine $medicine, int $currentStock, int $reorderLevel): bool
    {
        // Deduplication check: Has a low stock notification been recorded in the past 24 hours for this medicine?
        $recentNotice = DB::table('notifications')
            ->where('data', 'like', '%"record_type":"medicine"%')
            ->where('data', 'like', '%"record_id":"'.$medicine->id.'"%')
            ->where('data', 'like', '%"event_type":"pharmacy.low_stock"%')
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($recentNotice) {
            return false;
        }

        $recipients = User::where('is_active', true)
            ->whereIn('role', [UserRole::PHARMACIST->value, UserRole::SUPER_ADMIN->value])
            ->get();

        $notification = new HospitalNotification(
            category: 'pharmacy',
            eventType: 'pharmacy.low_stock',
            title: "Low Stock Alert: {$medicine->name}",
            message: "Inventory for {$medicine->name} ({$currentStock} units remaining) has fallen to or below reorder threshold ({$reorderLevel}).",
            actionUrl: route('admin.inventory.medicines.show', $medicine->id),
            recordType: 'medicine',
            recordId: (string) $medicine->id,
            extra: [
                'current_stock' => $currentStock,
                'reorder_level' => $reorderLevel,
            ]
        );

        Notification::send($recipients, $notification);

        return true;
    }

    /**
     * Dispatch notification for incoming public contact enquiry.
     */
    public static function notifyContactEnquiry(ContactInquiry $contact): void
    {
        $recipients = User::where('is_active', true)
            ->whereIn('role', [UserRole::RECEPTIONIST->value, UserRole::SUPER_ADMIN->value])
            ->get();

        $notification = new HospitalNotification(
            category: 'contact',
            eventType: 'contact.enquiry',
            title: "New Public Contact Enquiry ({$contact->subject})",
            message: "A new public message regarding '{$contact->subject}' has been received.",
            actionUrl: route('admin.dashboard'),
            recordType: 'contact',
            recordId: (string) $contact->id
        );

        Notification::send($recipients, $notification);
    }
}
