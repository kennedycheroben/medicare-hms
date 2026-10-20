<?php

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.contact');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:25'],
            'department' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'min:4', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2500'],
        ], [
            'name.required' => 'Please provide your full name.',
            'email.required' => 'A valid email address is required so our coordination team can reply.',
            'subject.required' => 'Please indicate the nature of your enquiry.',
            'message.required' => 'Please describe your inquiry or health concern in detail.',
            'message.min' => 'Inquiry message must be at least 10 characters long.',
        ]);

        // Generate unique ticket number (INQ-YYYYMMDD-XXXX)
        do {
            $ticketNumber = 'INQ-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (ContactInquiry::where('ticket_number', $ticketNumber)->exists());

        $inquiry = ContactInquiry::create([
            'ticket_number' => $ticketNumber,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'department' => $validated['department'] ?? 'General Patient Care',
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 500),
        ]);

        return redirect()->route('contact')
            ->with('success', "Thank you, {$inquiry->name}! Your enquiry has been received under Ticket #{$inquiry->ticket_number}. Our patient coordination team typically responds within 2 hours.");
    }
}
