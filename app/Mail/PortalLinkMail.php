<?php

namespace App\Mail;

use App\Models\PatientAccount;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Emails a one-time link to activate or reset a patient portal password.
 * The plain token is only ever present in this message.
 */
class PortalLinkMail extends Mailable
{
    public function __construct(
        public PatientAccount $account,
        public string $token,
        public bool $isReset,
        public int $validForMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->isReset ? 'Reset your ' : 'Activate your ').config('hospital.name', config('app.name')).' patient portal password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-link',
            with: [
                'url' => route('portal.activate', $this->token),
                'name' => $this->account->patient->first_name,
                'hospital' => config('hospital.name', config('app.name')),
                'hours' => round($this->validForMinutes / 60, 1),
            ],
        );
    }
}
