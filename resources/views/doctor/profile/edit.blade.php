@extends('layouts.admin')

@section('title', 'My Professional Profile')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Doctor Profile Management</h1>
            <p class="text-muted small mb-0">Manage your clinical credentials, biography, and consultation schedule for public listing.</p>
        </div>
        <div>
            @if($doctor->status === 'approved' && $doctor->is_published)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-check-circle-fill me-1"></i> Published on Website
                </span>
            @elseif($doctor->status === 'pending_review')
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-hourglass-split me-1"></i> Awaiting Administrator Review
                </span>
            @elseif($doctor->status === 'needs_revision')
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Needs Revision
                </span>
            @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-pencil-square me-1"></i> Draft Profile
                </span>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span class="fw-bold">Please correct the following errors:</span>
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($doctor->status === 'needs_revision' && $doctor->publication_notes)
        <div class="alert alert-warning border-start border-4 border-warning shadow-sm rounded-3 mb-4" role="alert">
            <h5 class="alert-heading fw-bold text-dark mb-1">
                <i class="bi bi-chat-left-dots-fill text-warning me-2"></i> Reviewer Feedback
            </h5>
            <p class="mb-1 text-dark">{{ $doctor->publication_notes }}</p>
            <div class="extra-small text-muted">Please update the requested information below and resubmit your profile for review.</div>
        </div>
    @endif

    @if($doctor->status === 'pending_review')
        <div class="alert alert-info border-start border-4 border-info shadow-sm rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                <div>
                    <h6 class="fw-bold mb-1 text-dark">Profile Submitted for Review</h6>
                    <p class="mb-0 small text-muted">Your profile is currently being reviewed by Hospital Administration. While under review, your profile is not visible to the public. You may still make revisions below if needed.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-1">Professional Details</h5>
                    <p class="text-muted small">Accurate qualifications and specialties help patients find the right care.</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('doctor.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold small">Doctor Full Name & Title <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $doctor->name) }}" required placeholder="e.g. Dr. Sarah Mwangi, MD">
                            </div>

                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-bold small">Clinical Department <span class="text-danger">*</span></label>
                                <select name="department_id" id="department_id" class="form-select" required>
                                    <option value="">Select your primary department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id', $doctor->department_id) == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="specialty" class="form-label fw-bold small">Medical Specialty <span class="text-danger">*</span></label>
                                <input type="text" name="specialty" id="specialty" class="form-control" value="{{ old('specialty', $doctor->specialty) }}" required placeholder="e.g. Consultant Cardiologist">
                            </div>

                            <div class="col-md-6">
                                <label for="qualifications" class="form-label fw-bold small">Degrees & Board Certifications <span class="text-danger">*</span></label>
                                <input type="text" name="qualifications" id="qualifications" class="form-control" value="{{ old('qualifications', $doctor->qualifications) }}" required placeholder="e.g. MBChB, MMed (Internal Med), FACC">
                            </div>

                            <div class="col-md-4">
                                <label for="experience_years" class="form-label fw-bold small">Years of Clinical Experience <span class="text-danger">*</span></label>
                                <input type="number" name="experience_years" id="experience_years" class="form-control" min="0" max="70" value="{{ old('experience_years', $doctor->experience_years) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label for="languages" class="form-label fw-bold small">Languages Spoken</label>
                                <input type="text" name="languages" id="languages" class="form-control" value="{{ old('languages', $doctor->languages) }}" placeholder="e.g. English, Swahili">
                            </div>

                            <div class="col-md-4">
                                <label for="room_number" class="form-label fw-bold small">Consultation Suite / Room</label>
                                <input type="text" name="room_number" id="room_number" class="form-control" value="{{ old('room_number', $doctor->room_number) }}" placeholder="e.g. Suite 304, Block B">
                            </div>

                            <div class="col-12">
                                <label for="bio" class="form-label fw-bold small">Professional Biography <span class="text-danger">*</span></label>
                                <textarea name="bio" id="bio" rows="4" class="form-control" required placeholder="Describe your clinical background, areas of clinical interest, and patient care philosophy...">{{ old('bio', $doctor->bio) }}</textarea>
                                <div class="form-text extra-small">Maximum 2,000 characters. Visible on the public website once approved.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="consultation_days" class="form-label fw-bold small">Consultation Days</label>
                                <input type="text" name="consultation_days" id="consultation_days" class="form-control" value="{{ old('consultation_days', $doctor->consultation_days) }}" placeholder="e.g. Monday - Thursday">
                            </div>

                            <div class="col-md-6">
                                <label for="consultation_hours" class="form-label fw-bold small">Consultation Hours</label>
                                <input type="text" name="consultation_hours" id="consultation_hours" class="form-control" value="{{ old('consultation_hours', $doctor->consultation_hours) }}" placeholder="e.g. 08:30 AM - 04:00 PM">
                            </div>

                            <div class="col-md-6">
                                <label for="is_available" class="form-label fw-bold small">Clinical Availability Status <span class="text-danger">*</span></label>
                                <select name="is_available" id="is_available" class="form-select" required>
                                    <option value="1" {{ old('is_available', $doctor->is_available) ? 'selected' : '' }}>Available for Appointments</option>
                                    <option value="0" {{ !old('is_available', $doctor->is_available) ? 'selected' : '' }}>On Leave / Unavailable</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="image_file" class="form-label fw-bold small">Profile Photo (Optional)</label>
                                <input type="file" name="image_file" id="image_file" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text extra-small">JPEG, PNG, or WebP up to 2MB. Professional portrait recommended.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">
                                <i class="bi bi-save me-1"></i> Save as Draft
                            </button>
                            <button type="submit" name="action" value="submit_review" class="btn btn-primary px-4">
                                <i class="bi bi-send-check me-1"></i> Submit for Administrator Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Profile Preview Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0">Public Listing Preview</h6>
                </div>
                <div class="card-body p-4 text-center">
                    <img src="{{ $doctor->imageUrl() }}" alt="{{ $doctor->name }}" class="rounded-4 img-fluid shadow-sm mb-3" style="width: 140px; height: 165px; object-fit: cover;">
                    <h5 class="fw-bold text-dark mb-1">{{ $doctor->name }}</h5>
                    <div class="text-primary fw-semibold small mb-1">{{ $doctor->specialty }}</div>
                    <div class="text-muted small mb-3">{{ $doctor->department->name ?? 'General Medical' }}</div>

                    <div class="bg-light rounded-3 p-3 text-start small">
                        <div class="mb-1"><span class="text-muted">Experience:</span> <span class="fw-semibold">{{ $doctor->experience_years }} Years</span></div>
                        <div class="mb-1"><span class="text-muted">Credentials:</span> <span class="fw-semibold">{{ $doctor->qualifications }}</span></div>
                        <div class="mb-1"><span class="text-muted">Suite:</span> <span class="fw-semibold">{{ $doctor->room_number ?? 'Consultation Wing' }}</span></div>
                        <div><span class="text-muted">Schedule:</span> <span class="fw-semibold">{{ $doctor->consultation_days ?? 'By Appointment' }}</span></div>
                    </div>
                </div>
            </div>

            <!-- Approval Policy Notes -->
            <div class="card border-0 bg-light rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-shield-check text-primary me-1"></i> Publication Governance
                    </h6>
                    <ul class="extra-small text-muted ps-3 mb-0">
                        <li class="mb-2">Doctors manage their own profile details and submit updates for hospital review.</li>
                        <li class="mb-2">Only authorized hospital administrators can approve and publish profiles to the public website.</li>
                        <li class="mb-2">Editing an already published profile will require re-verification before changes appear publicly.</li>
                        <li>Unpublished profiles are hidden from patient search, department rosters, and booking directories.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
