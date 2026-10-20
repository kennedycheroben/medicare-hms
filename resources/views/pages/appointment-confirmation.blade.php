@extends('layouts.public')

@section('title', 'Appointment Request Received - Ref #' . $referenceCode)
@section('meta_description', 'Your consultation reservation request has been submitted to MediCare Hospital. Keep your tracking reference code safe.')

@section('content')

    <x-page-header
        title="Appointment Request Received"
        subtitle="Your Consultation Request Has Been Placed in the Clinical Coordination Queue"
        :breadcrumbs="[['label' => 'Appointment Request Confirmation']]"
    />

    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Main Confirmation Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-center mb-4">
                        <div class="rounded-circle bg-success-subtle text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-check2-circle fs-1"></i>
                        </div>

                        <span class="badge bg-success text-uppercase fw-bold px-3 py-2 mb-2">Request Submitted</span>
                        <h2 class="display-6 fw-bold mb-1" style="color: var(--heading-color);">Appointment Request Registered</h2>
                        <p class="text-muted small mb-4">Your consultation booking request has been logged into the hospital coordination queue.</p>

                        <!-- Big Reference Code Badge -->
                        <div class="p-3 bg-light border rounded-3 mb-4 d-inline-block mx-auto text-center" style="min-width: 280px;">
                            <small class="text-muted text-uppercase fw-bold d-block mb-1" style="letter-spacing: 1px;">Tracking Reference Code</small>
                            <span class="h3 fw-bold text-primary font-monospace mb-0 d-block">{{ $referenceCode }}</span>
                        </div>

                        <!-- Important Disclaimer Reminder -->
                        <div class="alert alert-warning border-0 rounded-3 text-start small p-3 mb-4 bg-warning-subtle text-dark">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-3 flex-shrink-0 mt-1"></i>
                                <div>
                                    <strong class="d-block mb-1">Important Notice — Request Under Review:</strong>
                                    Submitting this request <strong>does not constitute a confirmed appointment</strong>. Our clinical coordination desk will review provider schedules and contact you directly via the telephone number or email address provided on your form to confirm consultation availability.
                                </div>
                            </div>
                        </div>

                        <!-- Privacy Notice -->
                        <div class="alert alert-info border-0 rounded-3 text-start small p-3 mb-4 bg-info-subtle text-dark">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-shield-lock-fill text-primary fs-5 me-3 flex-shrink-0 mt-1"></i>
                                <div>
                                    <strong class="d-block mb-1">Privacy & Data Protection Notice:</strong>
                                    To protect patient confidentiality and personal health data, personal details, contact information, and medical selections are not displayed on this public tracking page.
                                </div>
                            </div>
                        </div>

                        <!-- General Next Steps & Instructions -->
                        <div class="text-start bg-light p-4 rounded-3 border mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-card-checklist me-2 text-primary"></i>What Happens Next & How to Prepare:</h6>
                            <ul class="text-muted small mb-0 ps-3">
                                <li class="mb-2"><strong>Save Your Reference Code:</strong> Keep reference <strong>{{ $referenceCode }}</strong> handy for any future inquiries with our admissions or coordination desk.</li>
                                <li class="mb-2"><strong>Awaiting Confirmation:</strong> Our team reviews requests during regular clinic hours. If a physician is unavailable at the requested time, an alternative slot will be offered.</li>
                                <li class="mb-2"><strong>On the Day of Your Visit (Once Confirmed):</strong> Please present your reference code and a valid national ID or passport at the outpatient reception desk 15 minutes prior to your scheduled time.</li>
                                <li class="mb-2"><strong>Insurance Patients:</strong> Bring your physical or digital membership card and pre-authorization documentation if applicable.</li>
                                <li class="mb-0"><strong>Emergency Situations:</strong> If you or the patient requires immediate or emergency medical care, <em>do not wait for an appointment confirmation call</em>. Please proceed immediately to our 24/7 Emergency Department or dial your local emergency services line.</li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <button onclick="window.print()" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-printer me-1"></i>Print Reference Receipt
                            </button>
                            <a href="{{ route('home') }}" class="btn btn-primary px-4">
                                <i class="bi bi-house me-1"></i>Return to Home
                            </a>
                            <a href="{{ route('contact') }}" class="btn btn-outline-primary px-4">
                                <i class="bi bi-telephone me-1"></i>Contact Hospital Desk
                            </a>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </section>

@endsection
