@extends('layouts.public')

@section('title', 'Contact Us, Hospital Directions & Emergency Hotline')
@section('meta_description', 'Contact MediCare Hospital. Reach our 24/7 emergency hotline, general inquiries desk, clinical departments, and view hospital location directions.')

@section('content')

    <x-page-header
        title="Contact Us & General Enquiries"
        subtitle="Get in Touch with Our Patient Care Team, Emergency Dispatch, and Hospital Administration"
        :breadcrumbs="[['label' => 'Contact']]"
    />

    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">

            <!-- Contact Information Cards -->
            <div class="row g-4 mb-5">
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-geo-alt"></i></div>
                        <h5 class="fw-bold mb-2">Hospital Location</h5>
                        <p class="text-muted small mb-0">
                            {{ config('hospital.address') }}
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-danger mb-3"><i class="bi bi-telephone-inbound-fill"></i></div>
                        <h5 class="fw-bold mb-2 text-danger">Emergency Hotline</h5>
                        <p class="text-muted small mb-2">
                            For urgent medical & trauma dispatch:
                        </p>
                        <a href="tel:{{ config('hospital.emergency_phone') }}" class="h6 fw-bold text-danger text-decoration-none">
                            {{ config('hospital.emergency_phone') }}
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-telephone"></i></div>
                        <h5 class="fw-bold mb-2">General Switchboard</h5>
                        <p class="text-muted small mb-2">
                            Consultation bookings & inquiries:
                        </p>
                        <a href="tel:{{ config('hospital.phone') }}" class="h6 fw-bold text-primary text-decoration-none">
                            {{ config('hospital.phone') }}
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white text-center">
                        <div class="fs-1 text-primary mb-3"><i class="bi bi-envelope"></i></div>
                        <h5 class="fw-bold mb-2">Email Desk</h5>
                        <p class="text-muted small mb-2">
                            General correspondence & inquiries:
                        </p>
                        <a href="mailto:{{ config('hospital.email') }}" class="small fw-bold text-primary text-decoration-none">
                            {{ config('hospital.email') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Form & Location Map -->
            <div class="row g-5">

                <!-- Left Column: Inquiry Form -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                        <div class="border-bottom pb-3 mb-4">
                            <span class="badge bg-primary-subtle text-primary border mb-2 px-3 py-2 text-uppercase fw-bold">
                                Patient Inquiries
                            </span>
                            <h3 class="fw-bold mb-1" style="color: var(--heading-color);">Send an Online Enquiry</h3>
                            <p class="text-muted small mb-0">Our coordination desk typically replies to online inquiries within 2 to 4 business hours.</p>
                        </div>

                        <form action="{{ route('contact.store') }}" method="POST">
                            @csrf

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        id="name"
                                        name="name"
                                        placeholder="e.g. Jane Doe"
                                        value="{{ old('name') }}"
                                        required
                                    >
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                                    <input
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        placeholder="e.g. jane@example.com"
                                        value="{{ old('email') }}"
                                        required
                                    >
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label small fw-bold">Phone Number <span class="text-muted">(Optional)</span></label>
                                    <input
                                        type="tel"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        id="phone"
                                        name="phone"
                                        placeholder="e.g. +1 555 123 4567"
                                        value="{{ old('phone') }}"
                                    >
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="department" class="form-label small fw-bold">Department of Interest</label>
                                    <select class="form-select @error('department') is-invalid @enderror" id="department" name="department">
                                        <option value="General Patient Care" {{ old('department') == 'General Patient Care' ? 'selected' : '' }}>General Patient Care</option>
                                        <option value="Cardiology" {{ old('department') == 'Cardiology' ? 'selected' : '' }}>Cardiology & Heart Center</option>
                                        <option value="Neurology" {{ old('department') == 'Neurology' ? 'selected' : '' }}>Neurology & Brain Sciences</option>
                                        <option value="Pediatrics" {{ old('department') == 'Pediatrics' ? 'selected' : '' }}>Pediatrics & NICU</option>
                                        <option value="Orthopedics" {{ old('department') == 'Orthopedics' ? 'selected' : '' }}>Orthopedic Surgery</option>
                                        <option value="Oncology" {{ old('department') == 'Oncology' ? 'selected' : '' }}>Oncology & Cancer Care</option>
                                        <option value="Billing & Insurance" {{ old('department') == 'Billing & Insurance' ? 'selected' : '' }}>Billing & Health Insurance</option>
                                        <option value="Executive Administration" {{ old('department') == 'Executive Administration' ? 'selected' : '' }}>Hospital Administration</option>
                                    </select>
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label small fw-bold">Subject / Inquiry Nature <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control @error('subject') is-invalid @enderror"
                                    id="subject"
                                    name="subject"
                                    placeholder="e.g. Consultation fee inquiry, visiting guidance..."
                                    value="{{ old('subject') }}"
                                    required
                                >
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label small fw-bold">Message <span class="text-danger">*</span></label>
                                <textarea
                                    class="form-control @error('message') is-invalid @enderror"
                                    id="message"
                                    name="message"
                                    rows="5"
                                    placeholder="Please describe your question, clinical inquiry, or feedback in detail..."
                                    required
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg px-5 py-3 fw-bold">
                                <i class="bi bi-send me-2"></i>Submit Inquiry
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Operating Hours & Map -->
                <div class="col-lg-5">

                    <!-- Operating Hours Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-3" style="color: var(--heading-color);">
                            <i class="bi bi-clock-history text-primary me-2"></i>Operating Hours
                        </h5>
                        <ul class="list-unstyled small mb-0">
                            <li class="py-2 border-bottom d-flex justify-content-between">
                                <strong>Emergency & Trauma Center:</strong>
                                <span class="text-success fw-bold">24 Hours / 7 Days</span>
                            </li>
                            <li class="py-2 border-bottom d-flex justify-content-between">
                                <strong>Outpatient Specialist Clinics:</strong>
                                <span>Mon – Fri: 08:00 AM – 06:00 PM</span>
                            </li>
                            <li class="py-2 border-bottom d-flex justify-content-between">
                                <strong>Saturday Clinics:</strong>
                                <span>08:30 AM – 02:00 PM</span>
                            </li>
                            <li class="py-2 border-bottom d-flex justify-content-between">
                                <strong>Diagnostic Laboratories:</strong>
                                <span class="text-success fw-bold">24 Hours Open</span>
                            </li>
                            <li class="py-2 d-flex justify-content-between">
                                <strong>Inpatient Pharmacy:</strong>
                                <span class="text-success fw-bold">24 Hours Open</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Ambulance Bay Notice -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light">
                        <h5 class="fw-bold mb-2 text-danger"><i class="bi bi-truck-front-fill me-2"></i>Emergency Ambulance Bay</h5>
                        <p class="text-muted small mb-0">
                            Emergency ambulance vehicles enter through Gate 2 off Boulevard Avenue for direct, level-grade patient transfer into the resuscitation suites.
                        </p>
                    </div>

                    <!-- Campus Location Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
                        <i class="bi bi-pin-map text-primary fs-1 mb-2"></i>
                        <h5 class="fw-bold mb-1" style="color: var(--heading-color);">Hospital Campus</h5>
                        <p class="text-muted small mb-3">Accessible via Metro Line 4 (Medical Center Station) and North/South Expressways with 500+ visitor parking spaces.</p>
                        <a href="https://maps.google.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Get Driving Directions
                        </a>
                    </div>

                </div>

            </div>

        </div>
    </section>

@endsection
