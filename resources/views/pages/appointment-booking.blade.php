@extends('layouts.public')

@section('title', 'Schedule an Appointment & Consultation Request')
@section('meta_description', 'Request a specialist consultation appointment online at MediCare Hospital. Choose your department, preferred doctor, and convenient date.')

@section('content')

    <x-page-header
        title="Schedule An Appointment"
        subtitle="{{ config('hospital.is_demo') ? 'Submit a Consultation Request to Test Clinical Scheduling Workflows' : 'Submit a Priority Consultation Request with Our Medical Specialists' }}"
        :breadcrumbs="[['label' => 'Appointment Booking']]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark shadow-sm">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Booking Notice:</strong> This appointment booking form is a demonstration of the MediCare Hospital Management System workflow. Submissions generate simulated confirmation reference codes for software evaluation and do not book physical medical visits.
            </div>
        </div>
    @endif

    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">

            <!-- Process Stepper -->
            <div class="row g-3 process-stepper mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3 rounded-3 bg-white text-center">
                        <span class="badge bg-primary rounded-pill mb-2 align-self-center px-3 py-1">Step 1</span>
                        <div class="fs-2 text-primary mb-2"><i class="bi bi-hospital"></i></div>
                        <h6 class="fw-bold mb-1">Select Specialty</h6>
                        <small class="text-muted">Choose department & preferred doctor</small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3 rounded-3 bg-white text-center">
                        <span class="badge bg-primary rounded-pill mb-2 align-self-center px-3 py-1">Step 2</span>
                        <div class="fs-2 text-primary mb-2"><i class="bi bi-calendar3"></i></div>
                        <h6 class="fw-bold mb-1">Choose Date & Slot</h6>
                        <small class="text-muted">Pick a convenient morning or afternoon time</small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3 rounded-3 bg-white text-center">
                        <span class="badge bg-primary rounded-pill mb-2 align-self-center px-3 py-1">Step 3</span>
                        <div class="fs-2 text-primary mb-2"><i class="bi bi-person-lines-fill"></i></div>
                        <h6 class="fw-bold mb-1">Patient Details</h6>
                        <small class="text-muted">Provide legal name & contact phone</small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3 rounded-3 bg-white text-center">
                        <span class="badge bg-primary rounded-pill mb-2 align-self-center px-3 py-1">Step 4</span>
                        <div class="fs-2 text-primary mb-2"><i class="bi bi-check2-circle"></i></div>
                        <h6 class="fw-bold mb-1">Queue & Confirmation</h6>
                        <small class="text-muted">Receive tracking code & triage confirmation</small>
                    </div>
                </div>
            </div>

            <!-- Emergency Warning Banner -->
            <div class="alert alert-danger border-danger border-start border-4 rounded-3 p-4 mb-5 shadow-sm bg-white" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-octagon-fill text-danger fs-2 me-3 flex-shrink-0"></i>
                    <div>
                        <h5 class="fw-bold text-danger mb-1">Emergency Medical Notice</h5>
                        @if(config('hospital.is_demo'))
                            <p class="mb-2 text-dark small leading-relaxed">
                                If you or the patient are experiencing <strong>chest pain, difficulty breathing, acute trauma, severe bleeding, or signs of stroke</strong>, do not wait for an online appointment form. Call <strong>999 / 112</strong> (National Emergency Dispatch in Kenya) immediately or proceed straight to the nearest hospital emergency room. This website is a demonstration and does not dispatch emergency services.
                            </p>
                            <span class="badge bg-warning text-dark py-2 px-3 fw-bold">
                                <i class="bi bi-shield-exclamation me-1"></i>Real Emergencies: Call 999 / 112
                            </span>
                        @else
                            <p class="mb-2 text-dark small leading-relaxed">
                                If you or the patient are experiencing <strong>chest pain, difficulty breathing, acute trauma, severe bleeding, or signs of stroke</strong>, do not wait for an online appointment request. Call our 24/7 Emergency Line immediately or proceed straight to our emergency triage bay:
                            </p>
                            <a href="tel:{{ config('hospital.emergency_phone') }}" class="btn btn-sm btn-danger fw-bold">
                                <i class="bi bi-telephone-fill me-1"></i>Emergency Care: {{ config('hospital.emergency_phone') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">

                        <div class="border-bottom pb-4 mb-4">
                            <span class="badge bg-primary-subtle text-primary border mb-2 px-3 py-2 text-uppercase fw-bold">
                                Consultation Booking Request
                            </span>
                            <h3 class="fw-bold mb-1" style="color: var(--heading-color);">{{ config('hospital.is_demo') ? 'Simulated Appointment Request' : 'Online Appointment Reservation' }}</h3>
                            <p class="text-muted small mb-0">
                                {{ config('hospital.is_demo') ? 'Complete this form to test our clinic coordination workflow. Submitted requests populate the administrative demonstration queue.' : 'Complete this form to submit your appointment request into our clinic coordination system.' }}
                            </p>
                        </div>

                        <!-- Important Disclaimer Alert Box -->
                        <div class="alert alert-info border-0 rounded-3 p-3 mb-4 d-flex align-items-start bg-info-subtle">
                            <i class="bi bi-info-circle-fill text-info fs-4 me-3 flex-shrink-0"></i>
                            <div class="small text-dark">
                                @if(config('hospital.is_demo'))
                                    <strong>Demonstration Workflow:</strong> Submitting this form creates a simulated appointment record in the system for testing queue management and patient communication. <em>No real doctor appointment is scheduled and no physical consultation slot will be reserved.</em>
                                @else
                                    <strong>Important Appointment Policy:</strong> Submitting this form enters your request into our hospital queue. <em>It does not constitute a guaranteed or confirmed appointment until our triage coordinator contacts you via phone or SMS</em> to verify physician availability and officially confirm your slot.
                                @endif
                            </div>
                        </div>

                        <form action="{{ route('appointments.store') }}" method="POST" class="needs-validation">
                            @csrf

                            <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">
                                <i class="bi bi-person me-2"></i>1. Patient Contact Information
                            </h5>

                            <div class="row g-4 mb-4">
                                <!-- Patient Full Name -->
                                <div class="col-md-4">
                                    <label for="patient_name" class="form-label small fw-bold">
                                        Full Legal Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control @error('patient_name') is-invalid @enderror"
                                        id="patient_name"
                                        name="patient_name"
                                        placeholder="e.g. John Doe"
                                        value="{{ old('patient_name') }}"
                                        required
                                    >
                                    <small class="form-text text-muted" style="font-size: 0.75rem;">As indicated on national ID or passport</small>
                                    @error('patient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Patient Email -->
                                <div class="col-md-4">
                                    <label for="patient_email" class="form-label small fw-bold">
                                        Email Address <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        class="form-control @error('patient_email') is-invalid @enderror"
                                        id="patient_email"
                                        name="patient_email"
                                        placeholder="e.g. john@example.com"
                                        value="{{ old('patient_email') }}"
                                        required
                                    >
                                    <small class="form-text text-muted" style="font-size: 0.75rem;">For appointment confirmation receipt</small>
                                    @error('patient_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Patient Phone -->
                                <div class="col-md-4">
                                    <label for="patient_phone" class="form-label small fw-bold">
                                        Telephone Contact <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        class="form-control @error('patient_phone') is-invalid @enderror"
                                        id="patient_phone"
                                        name="patient_phone"
                                        placeholder="e.g. +254 712 345 678 or 0712 345 678"
                                        value="{{ old('patient_phone') }}"
                                        required
                                    >
                                    <small class="form-text text-muted" style="font-size: 0.75rem;">Required for SMS triage confirmation</small>
                                    @error('patient_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">
                                <i class="bi bi-hospital me-2"></i>2. Clinical Department & Doctor
                            </h5>

                            <div class="row g-4 mb-4">
                                <!-- Department -->
                                <div class="col-md-6">
                                    <label for="department_id" class="form-label small fw-bold">
                                        Medical Department <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                        <option value="">Select Medical Specialty...</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ (old('department_id', $selectedDeptId) == $dept->id) ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted" style="font-size: 0.75rem;">Choose the relevant clinical specialty</small>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Doctor (Optional) -->
                                <div class="col-md-6">
                                    <label for="doctor_id" class="form-label small fw-bold">
                                        Preferred Doctor <span class="text-muted">(Optional)</span>
                                    </label>
                                    <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id">
                                        <option value="">{{ config('hospital.is_demo') ? 'Any Available Specialist (Simulated)' : 'Any Available Specialist Physician' }}</option>
                                        @foreach ($doctors as $doc)
                                            <option value="{{ $doc->id }}" data-department="{{ $doc->department_id }}" {{ (old('doctor_id', $selectedDoctorId) == $doc->id) ? 'selected' : '' }}>
                                                {{ $doc->name }} ({{ $doc->specialty }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted" style="font-size: 0.75rem;">Leave blank to be assigned to the next available consultant</small>
                                    @error('doctor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">
                                <i class="bi bi-calendar-event me-2"></i>3. Preferred Date & Time
                            </h5>

                            <div class="row g-4 mb-4">
                                <!-- Appointment Date -->
                                <div class="col-md-6">
                                    <label for="appointment_date" class="form-label small fw-bold">
                                        Preferred Date <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        class="form-control @error('appointment_date') is-invalid @enderror"
                                        id="appointment_date"
                                        name="appointment_date"
                                        min="{{ date('Y-m-d') }}"
                                        value="{{ old('appointment_date', date('Y-m-d')) }}"
                                        required
                                    >
                                    <small class="form-text text-muted" style="font-size: 0.75rem;">Appointments can be requested for today or upcoming dates</small>
                                    @error('appointment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Appointment Time Slot -->
                                <div class="col-md-6">
                                    <label for="appointment_time" class="form-label small fw-bold">
                                        Preferred Time Slot <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('appointment_time') is-invalid @enderror" id="appointment_time" name="appointment_time" required>
                                        <option value="">Choose Time Window...</option>
                                        <optgroup label="Morning Clinic">
                                            <option value="09:00 AM - 10:00 AM" {{ old('appointment_time') == '09:00 AM - 10:00 AM' ? 'selected' : '' }}>09:00 AM – 10:00 AM</option>
                                            <option value="10:00 AM - 11:00 AM" {{ old('appointment_time') == '10:00 AM - 11:00 AM' ? 'selected' : '' }}>10:00 AM – 11:00 AM</option>
                                            <option value="11:00 AM - 12:00 PM" {{ old('appointment_time') == '11:00 AM - 12:00 PM' ? 'selected' : '' }}>11:00 AM – 12:00 PM</option>
                                        </optgroup>
                                        <optgroup label="Afternoon Clinic">
                                            <option value="02:00 PM - 03:00 PM" {{ old('appointment_time') == '02:00 PM - 03:00 PM' ? 'selected' : '' }}>02:00 PM – 03:00 PM</option>
                                            <option value="03:00 PM - 04:00 PM" {{ old('appointment_time') == '03:00 PM - 04:00 PM' ? 'selected' : '' }}>03:00 PM – 04:00 PM</option>
                                            <option value="04:00 PM - 05:00 PM" {{ old('appointment_time') == '04:00 PM - 05:00 PM' ? 'selected' : '' }}>04:00 PM – 05:00 PM</option>
                                        </optgroup>
                                    </select>
                                    <small class="form-text text-muted" style="font-size: 0.75rem;">Subject to clinic slot confirmation</small>
                                    @error('appointment_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert alert-light border rounded-3 p-3 mb-4 small text-dark bg-light">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-shield-check text-primary fs-4 me-3 flex-shrink-0"></i>
                                    <div>
                                        <strong class="d-block mb-1">Confidential Clinical Privacy Notice:</strong>
                                        To protect patient health confidentiality and personal data, this public scheduling form does not collect medical histories, diagnoses, or clinical notes. Your symptoms, current medications, and past medical history will be taken in complete privacy during your in-person clinical triage with your attending physician.
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 mb-4 small text-muted border">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="terms_agree" required checked>
                                    <label class="form-check-label text-dark" for="terms_agree">
                                        I understand that this submission is an appointment request that will be triaged and confirmed by hospital staff. I consent to receiving SMS and email notifications regarding this consultation.
                                    </label>
                                </div>
                            </div>

                            <div class="text-center pt-2">
                                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 fw-bold shadow-sm">
                                    <i class="bi bi-send-check me-2"></i>Submit Appointment Request
                                </button>
                                <small class="text-muted d-block mt-2">Zero online payment required. Standard consultation fees are settled upon hospital arrival.</small>
                            </div>

                        </form>

                    </div>
                </div>
            </div>

        </div>
    </section>

@endsection
