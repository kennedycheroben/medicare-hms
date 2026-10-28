@extends('layouts.public')

@section('title', $doctor->name . ' - ' . $doctor->specialty)
@section('meta_description', Str::limit(strip_tags($doctor->bio), 150))

@section('content')

    <x-page-header
        :title="$doctor->name"
        :subtitle="$doctor->specialty . ' | ' . ($doctor->department->name ?? 'Clinical Department')"
        :breadcrumbs="[
            ['label' => 'Doctors', 'url' => route('doctors')],
            ['label' => $doctor->name]
        ]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Profile Notice:</strong> This physician profile, credentials, and consultation hours schedule are sample demonstration data for software evaluation.
            </div>
        </div>
    @endif

    <section class="py-5">
        <div class="container" data-aos="fade-up">
            <div class="row g-5">

                <!-- Left Column: Doctor Profile & Credentials -->
                <div class="col-lg-8">

                    <!-- Profile Header Card -->
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
                        <div class="d-flex flex-column flex-sm-row gap-4 align-items-center align-items-sm-start">
                            <img src="{{ $doctor->imageUrl() }}" alt="{{ $doctor->name }}" class="rounded-4 img-fluid shadow" style="width: 170px; height: 200px; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('assets/img/health/staff-1.webp') }}';">
                            <div class="flex-grow-1 text-center text-sm-start">
                                @if ($doctor->department)
                                    <a href="{{ route('departments.show', $doctor->department->slug) }}" class="badge bg-primary-subtle text-primary border text-decoration-none mb-2">
                                        <i class="bi bi-hospital me-1"></i>{{ $doctor->department->name }}
                                    </a>
                                @endif
                                <h2 class="h3 fw-bold mb-1" style="color: var(--heading-color);">{{ $doctor->name }}</h2>
                                <p class="text-muted fw-medium mb-2">{{ $doctor->specialty }}</p>

                                <div class="d-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-3 mb-3 small">
                                    <span class="text-warning fw-bold">
                                        <i class="bi bi-star-fill"></i> {{ number_format($doctor->rating, 1) }}
                                        <span class="text-muted fw-normal">({{ $doctor->review_count }} patient reviews)</span>
                                    </span>
                                    <span class="text-muted">&bull;</span>
                                    <span class="text-muted"><i class="bi bi-briefcase me-1 text-primary"></i>{{ $doctor->experience_years }} Years Clinical Practice</span>
                                </div>

                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start">
                                    <a href="{{ route('appointments.create', ['dept' => $doctor->department_id, 'doctor' => $doctor->id]) }}" class="btn btn-primary px-4">
                                        <i class="bi bi-calendar2-check me-2"></i>Book Consultation
                                    </a>
                                    <a href="{{ route('contact') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-chat-dots me-1"></i>Leave an Inquiry
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biography & Clinical Focus -->
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
                        <h3 class="h4 fw-bold mb-3" style="color: var(--heading-color);">Clinical Background & Biography</h3>
                        <p class="text-muted leading-relaxed mb-4">
                            {{ $doctor->bio }}
                        </p>

                        <h4 class="h5 fw-bold mb-3" style="color: var(--heading-color);">Education & Board Certifications</h4>
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-award text-primary fs-3 me-3"></i>
                                <div>
                                    <span class="fw-bold d-block text-dark">{{ $doctor->qualifications }}</span>
                                    <small class="text-muted">{{ config('hospital.is_demo') ? 'Sample Demonstration Board Certification' : 'Accredited by Medical Licensing Authority' }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3 border rounded bg-white">
                                    <span class="text-muted small d-block mb-1"><i class="bi bi-translate text-primary me-1"></i>Spoken Languages</span>
                                    <strong class="text-dark">{{ $doctor->languages ?? 'English, Swahili' }}</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 border rounded bg-white">
                                    <span class="text-muted small d-block mb-1"><i class="bi bi-geo-alt text-primary me-1"></i>Consultation Location</span>
                                    <strong class="text-dark">{{ $doctor->room_number ?? 'Consultation Suite 3B' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consultation Schedule Card -->
                    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
                        <h3 class="h4 fw-bold mb-3" style="color: var(--heading-color);">Regular Consultation Schedule</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Consultation Days</th>
                                        <th>Working Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-medium">{{ $doctor->consultation_days }}</td>
                                        <td>{{ $doctor->consultation_hours }}</td>
                                        <td>
                                            @if ($doctor->is_available)
                                                <span class="badge bg-success-subtle text-success border border-success">Accepting Patients</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border">Unavailable</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            *Schedule is subject to emergency theatre duties. Confirmed appointments receive priority queuing.
                        </small>
                    </div>

                    <!-- Verified Patient Reviews Section -->
                    <div class="mt-5">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                            <div>
                                <h3 class="h4 fw-bold mb-1" style="color: var(--heading-color);">Verified Patient Reviews</h3>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-warning">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi bi-star{{ $i <= round($doctor->rating) ? '-fill' : '' }}"></i>
                                        @endfor
                                    </div>
                                    <span class="fw-bold text-dark fs-5">{{ number_format($doctor->rating, 1) }}</span>
                                    <span class="text-muted small">({{ $doctor->review_count }} {{ Str::plural('verified review', $doctor->review_count) }})</span>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 rounded-pill px-3 py-2" data-bs-toggle="modal" data-bs-target="#patientReviewModal">
                                    <i class="bi bi-chat-heart"></i>
                                    <span>Review Your Consultation</span>
                                </button>
                            </div>
                        </div>

                        @if (session('review_status'))
                            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i> {{ session('review_status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->has('mrn') || $errors->has('visit_number') || $errors->has('phone') || $errors->has('rating') || $errors->has('comment'))
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if($doctor->approvedReviews->isNotEmpty())
                            <div class="d-flex flex-column gap-3">
                                @foreach($doctor->approvedReviews as $review)
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="p-2 rounded-circle bg-light text-primary">
                                                    <i class="bi bi-person-fill"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0">{{ $review->public_author_name }}</div>
                                                    <span class="badge bg-success-subtle text-success extra-small border border-success-subtle">
                                                        <i class="bi bi-patch-check-fill me-1"></i> Verified Hospital Visit
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-warning small mb-1">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                                    @endfor
                                                </div>
                                                <span class="extra-small text-muted">{{ $review->created_at->format('M d, Y') }}</span>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-0 mt-2">
                                            "{{ e($review->comment) }}"
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-light">
                                <i class="bi bi-chat-square-text text-secondary fs-2 mb-2"></i>
                                <h6 class="fw-bold mb-1">No Public Reviews Yet</h6>
                                <p class="text-muted small mb-3">Were you recently treated by {{ $doctor->name }}? Share your verified feedback with future patients.</p>
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#patientReviewModal">
                                        Submit Verified Feedback
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Related Doctors in Department -->
                    @if ($relatedDoctors->isNotEmpty())
                        <div class="mt-5">
                            <h3 class="h4 fw-bold mb-3" style="color: var(--heading-color);">Colleagues in {{ $doctor->department->name ?? 'Department' }}</h3>
                            <div class="row g-3">
                                @foreach ($relatedDoctors as $colleague)
                                    <div class="col-md-4">
                                        <div class="card h-100 border-0 shadow-sm p-3 text-center bg-white">
                                            <img src="{{ $colleague->imageUrl() }}" alt="{{ $colleague->name }}" class="rounded-circle mx-auto mb-2" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('assets/img/health/staff-3.webp') }}';">
                                            <h6 class="fw-bold mb-1">
                                                <a href="{{ route('doctors.show', $colleague->id) }}" class="text-decoration-none text-dark">
                                                    {{ $colleague->name }}
                                                </a>
                                            </h6>
                                            <small class="text-muted d-block mb-2">{{ $colleague->specialty }}</small>
                                            <a href="{{ route('doctors.show', $colleague->id) }}" class="btn btn-sm btn-outline-primary mt-auto">View Profile</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Right Column: Fast Appointment Box -->
                <div class="col-lg-4">

                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-white" style="background: linear-gradient(135deg, #17325b 0%, #0d1e38 100%);">
                        <span class="badge bg-info text-dark fw-bold mb-2 align-self-start">{{ config('hospital.is_demo') ? 'Simulated Booking' : 'Direct Reservation' }}</span>
                        <h4 class="fw-bold mb-2 text-white">{{ config('hospital.is_demo') ? 'Simulate Visit with ' . $doctor->name : 'Book with ' . $doctor->name }}</h4>
                        <p class="small text-light opacity-75 mb-3">
                            {{ config('hospital.is_demo') ? 'Submit a simulated visit request for this demonstration doctor profile.' : 'Submit your visit request to reserve a consultation slot. Our clinical coordinator will confirm your exact time.' }}
                        </p>
                        <a href="{{ route('appointments.create', ['dept' => $doctor->department_id, 'doctor' => $doctor->id]) }}" class="btn btn-primary btn-lg w-100 fw-bold mb-2">
                            {{ config('hospital.is_demo') ? 'Test Visit Request' : 'Reserve Consultation' }}
                        </a>
                        <small class="text-light opacity-50 d-block text-center" style="font-size: 0.75rem;">{{ config('hospital.is_demo') ? 'Software demonstration mode' : 'No payment required online' }}</small>
                    </div>

                    <!-- Department Emergency Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-3" style="color: var(--heading-color);">Department Inquiries</h5>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <span class="text-muted d-block">Department Name:</span>
                                <strong>{{ $doctor->department->name ?? 'Clinical Staff' }}</strong>
                            </li>
                            <li class="mb-2">
                                <span class="text-muted d-block">Extension:</span>
                                <strong>{{ $doctor->department->phone_extension ?? 'Direct Switchboard' }}</strong>
                            </li>
                            <li>
                                <span class="text-muted d-block">Emergency Guidance:</span>
                                @if(config('hospital.is_demo'))
                                    <strong class="text-warning">Call 999 / 112 (National Dispatch)</strong>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Simulated doctor profile</small>
                                @else
                                    <strong class="text-danger">{{ $doctor->department->emergency_contact ?? config('hospital.emergency_phone') }}</strong>
                                @endif
                            </li>
                        </ul>
                    </div>

                    <!-- Patient Assistance Notice -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle-fill text-primary fs-4 me-2"></i>
                            <div>
                                <h6 class="fw-bold mb-1">First-Time Patient?</h6>
                                <p class="small text-muted mb-0">Please arrive 15 minutes prior to your scheduled consultation time to complete registration and initial nurse triage.</p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- Modal: Verified Patient Review Submission -->
    <div class="modal fade" id="patientReviewModal" tabindex="-1" aria-labelledby="patientReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="{{ route('doctors.reviews.store', $doctor->id) }}">
                    @csrf
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark" id="patientReviewModalLabel">
                            <i class="bi bi-patch-check-fill text-primary me-2"></i> Verified Patient Review
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 p-3 small rounded-3 mb-3">
                            <i class="bi bi-shield-lock-fill me-1"></i>
                            To preserve integrity, reviews are restricted to verified patients who completed a consultation with <strong>{{ $doctor->name }}</strong>.
                        </div>

                        <!-- Encounter Verification -->
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label for="revMrn" class="form-label fw-bold small">Patient MRN <span class="text-danger">*</span></label>
                                <input type="text" name="mrn" id="revMrn" class="form-control form-control-sm font-monospace text-uppercase" placeholder="e.g. MRN-2026-0001" value="{{ old('mrn') }}" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="revVisit" class="form-label fw-bold small">Visit Number <span class="text-danger">*</span></label>
                                <input type="text" name="visit_number" id="revVisit" class="form-control form-control-sm font-monospace text-uppercase" placeholder="e.g. VST-2026-0001" value="{{ old('visit_number') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="revPhone" class="form-label fw-bold small">Registered Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="revPhone" class="form-control form-control-sm" placeholder="e.g. +254 712 345 678" value="{{ old('phone') }}" required>
                            <div class="form-text extra-small">Used solely to verify that your record matches the clinical visit.</div>
                        </div>

                        <!-- Rating & Comment -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Overall Consultation Rating <span class="text-danger">*</span></label>
                            <select name="rating" class="form-select form-select-sm" required>
                                <option value="5" {{ old('rating', '5') == '5' ? 'selected' : '' }}>5 Stars - Exceptional Care &amp; Communication</option>
                                <option value="4" {{ old('rating') == '4' ? 'selected' : '' }}>4 Stars - Very Good Consultation</option>
                                <option value="3" {{ old('rating') == '3' ? 'selected' : '' }}>3 Stars - Satisfactory</option>
                                <option value="2" {{ old('rating') == '2' ? 'selected' : '' }}>2 Stars - Needs Improvement</option>
                                <option value="1" {{ old('rating') == '1' ? 'selected' : '' }}>1 Star - Unsatisfactory</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="revComment" class="form-label fw-bold small">Patient Experience Feedback <span class="text-danger">*</span></label>
                            <textarea name="comment" id="revComment" rows="3" class="form-control form-control-sm" placeholder="Share your experience regarding physician bedside manner, clarity of diagnosis explanation, and care quality (10 to 1000 characters)..." required minlength="10" maxlength="1000">{{ old('comment') }}</textarea>
                        </div>

                        <!-- Privacy & Identity Consent -->
                        <div class="card border p-3 rounded-3 mb-3 bg-light">
                            <div class="fw-bold small mb-2 text-dark"><i class="bi bi-person-badge me-1"></i> Public Display Preference</div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="display_name_type" id="nameAnon" value="anonymous" {{ old('display_name_type', 'anonymous') === 'anonymous' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="nameAnon">
                                    Display anonymously (e.g. <em>"Verified Patient (Anonymous)"</em>)
                                </label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="display_name_type" id="nameInit" value="initials" {{ old('display_name_type') === 'initials' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="nameInit">
                                    Display my initials only (e.g. <em>"J. K."</em>)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="display_name_type" id="nameFull" value="full_name" {{ old('display_name_type') === 'full_name' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="nameFull">
                                    Display my full name (Requires explicit consent below)
                                </label>
                            </div>

                            <div class="mt-2 pt-2 border-top">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="public_consent" value="1" id="publicConsent" {{ old('public_consent') ? 'checked' : '' }}>
                                    <label class="form-check-label extra-small text-muted" for="publicConsent">
                                        I explicitly consent to having my feedback published on the hospital website. Clinical diagnoses, prescription details, and private health records are strictly confidential and will never be published.
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-top py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Submit for Moderation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
