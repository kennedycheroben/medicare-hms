<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PortalLinkMail;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Staff action: send (or re-send) a patient their portal activation link.
 * The link goes to the email on the patient's record, never to an address typed
 * at the desk, and is never shown to staff.
 */
class PatientPortalAccessController extends Controller
{
    public function issue(Request $request, Patient $patient): RedirectResponse
    {
        if (! $patient->is_active) {
            return back()->withErrors(['portal' => 'This patient record is inactive, so portal access cannot be issued.']);
        }

        if (! $patient->email) {
            return back()->withErrors(['portal' => 'Add the patient\'s email address to their profile first. The activation link is sent only to the email on record.']);
        }

        $account = PatientAccount::firstOrNew(['patient_id' => $patient->id]);
        $wasActivated = $account->exists && $account->isActivated();
        $account->email = $patient->email;
        $account->issued_by = $request->user()->id;
        $account->save();

        $token = $account->issueToken(PatientAccount::ACTIVATION_TTL_HOURS * 60);
        Mail::to($account->email)->send(new PortalLinkMail($account->load('patient'), $token, $wasActivated, PatientAccount::ACTIVATION_TTL_HOURS * 60));

        AuditService::record('portal.access_issued', 'patient', $patient->id, ['reissue' => $wasActivated]);

        return back()->with('status', 'A password link was emailed to the address on file. It works once and expires in '.PatientAccount::ACTIVATION_TTL_HOURS.' hours. Email delivery depends on the hospital mail settings.');
    }
}
