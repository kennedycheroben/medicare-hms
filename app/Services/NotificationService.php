<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ContactInquiry;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\StaffTask;
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
     * Dispatch notification when appointment is reassigned to a different doctor.
     */
    public static function notifyAppointmentReassigned(Appointment $appointment, ?Doctor $formerDoctor, Doctor $newDoctor): void
    {
        $reference = $appointment->reference_code ?? $appointment->appointment_number ?? '#'.$appointment->id;
        $appointmentDate = $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : 'scheduled date';

        // 1. Notify newly assigned doctor
        $newDoctorUser = User::where('is_active', true)
            ->where('doctor_id', $newDoctor->id)
            ->first();

        if ($newDoctorUser) {
            $notification = new HospitalNotification(
                category: 'appointment',
                eventType: 'appointment.assigned',
                title: "Appointment Assigned ({$reference})",
                message: "Appointment {$reference} has been assigned to you for {$appointmentDate}.",
                actionUrl: route('admin.appointments.show', $appointment->id),
                recordType: 'appointment',
                recordId: (string) $appointment->id
            );
            $newDoctorUser->notify($notification);
        }

        // 2. Notify former doctor about reassignment (if different and active)
        if ($formerDoctor && (! $newDoctorUser || (int) $formerDoctor->id !== (int) $newDoctor->id)) {
            $formerDoctorUser = User::where('is_active', true)
                ->where('doctor_id', $formerDoctor->id)
                ->first();

            if ($formerDoctorUser) {
                $formerNotice = new HospitalNotification(
                    category: 'appointment',
                    eventType: 'appointment.reassigned',
                    title: "Appointment Reassigned ({$reference})",
                    message: "Appointment {$reference} previously assigned to you has been reassigned to Dr. {$newDoctor->name}.",
                    actionUrl: route('admin.appointments.index'),
                    recordType: 'appointment',
                    recordId: (string) $appointment->id
                );
                $formerDoctorUser->notify($formerNotice);
            }
        }

        // 3. Notify coordination staff (receptionists and super admin)
        $staff = User::where('is_active', true)
            ->whereIn('role', [UserRole::RECEPTIONIST->value, UserRole::SUPER_ADMIN->value])
            ->get();

        $coordinationNotice = new HospitalNotification(
            category: 'appointment',
            eventType: 'appointment.reassigned',
            title: "Appointment Reassigned ({$reference})",
            message: "Appointment {$reference} was reassigned to Dr. {$newDoctor->name}.",
            actionUrl: route('admin.appointments.show', $appointment->id),
            recordType: 'appointment',
            recordId: (string) $appointment->id
        );

        Notification::send($staff, $coordinationNotice);
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
            actionUrl: route('admin.inquiries.show', $contact->id),
            recordType: 'contact',
            recordId: (string) $contact->id
        );

        Notification::send($recipients, $notification);
    }

    /**
     * Dispatch notification when a staff task is assigned.
     */
    public static function notifyTaskAssigned(StaffTask $task): void
    {
        $task->loadMissing('assignee', 'creator');

        if (! $task->assignee || ! $task->assignee->isActive()) {
            return;
        }

        $priorityLabel = strtoupper($task->priority);
        $creatorName = $task->creator?->name ?? 'Staff Administration';
        $dueInfo = $task->due_date ? ' Due by '.$task->due_date->format('M d, Y').'.' : '';

        $notification = new HospitalNotification(
            category: 'task',
            eventType: 'task.assigned',
            title: "[{$priorityLabel}] New Task: {$task->title}",
            message: "You have been assigned a task by {$creatorName}: {$task->title}.{$dueInfo}",
            actionUrl: route('admin.tasks.show', $task->id),
            recordType: 'staff_task',
            recordId: (string) $task->id
        );

        $task->assignee->notify($notification);
    }

    /**
     * Dispatch notification when a staff task is reassigned.
     */
    public static function notifyTaskReassigned(StaffTask $task, ?User $formerAssignee, User $newAssignee): void
    {
        $task->loadMissing('creator');

        $priorityLabel = strtoupper($task->priority);
        $creatorName = $task->creator?->name ?? 'Staff Administration';
        $dueInfo = $task->due_date ? ' Due by '.$task->due_date->format('M d, Y').'.' : '';

        // Notify new assignee
        if ($newAssignee->isActive()) {
            $notification = new HospitalNotification(
                category: 'task',
                eventType: 'task.assigned',
                title: "[{$priorityLabel}] Task Reassigned to You: {$task->title}",
                message: "You have been assigned task '{$task->title}' by {$creatorName}.{$dueInfo}",
                actionUrl: route('admin.tasks.show', $task->id),
                recordType: 'staff_task',
                recordId: (string) $task->id
            );
            $newAssignee->notify($notification);
        }

        // Notify former assignee
        if ($formerAssignee && $formerAssignee->id !== $newAssignee->id && $formerAssignee->isActive()) {
            $formerNotice = new HospitalNotification(
                category: 'task',
                eventType: 'task.reassigned',
                title: "Task Reassigned: {$task->title}",
                message: "Task '{$task->title}' has been reassigned to {$newAssignee->name}.",
                actionUrl: route('admin.tasks.index'),
                recordType: 'staff_task',
                recordId: (string) $task->id
            );
            $formerAssignee->notify($formerNotice);
        }
    }
}
