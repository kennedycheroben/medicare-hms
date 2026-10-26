@extends('layouts.public')

@section('title', 'Patient Information & Visiting Guidelines')
@section('meta_description', 'Everything patients and visitors need to know: hospital admission procedures, ward visiting hours, insurance billing, patient rights, and facilities.')

@section('content')

    <x-page-header
        title="Patient Information & Visiting Guide"
        subtitle="Important Guidelines for Admissions, Ward Visiting Schedules, Insurance Coverage, and Patient Rights"
        :breadcrumbs="[['label' => 'Patient Information']]"
    />

    <!-- Demonstration Notice -->
    @if(config('hospital.is_demo'))
        <div class="container mt-4">
            <div class="alert alert-info border-0 rounded-3 small p-3 bg-info-subtle text-dark shadow-sm">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <strong>Demonstration Notice:</strong> Patient admission checklists, visiting hours, and policies shown below illustrate hospital communication guidelines within MediCare HMS. For real medical emergencies, call national emergency dispatch (<strong>999 / 112</strong> in Kenya).
            </div>
        </div>
    @endif

    <!-- Quick Navigation Anchor Pills -->
    <section class="py-3 bg-white border-bottom sticky-top" style="top: 80px; z-index: 990;">
        <div class="container">
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="#admission" class="btn btn-sm btn-outline-primary rounded-pill">Admission Checklist</a>
                <a href="#visiting" class="btn btn-sm btn-outline-primary rounded-pill">Visiting Hours</a>
                <a href="#rights" class="btn btn-sm btn-outline-primary rounded-pill">Patient Rights</a>
                <a href="#insurance" class="btn btn-sm btn-outline-primary rounded-pill">Insurance & Billing</a>
                <a href="#faq" class="btn btn-sm btn-outline-primary rounded-pill">Patient FAQs</a>
            </div>
        </div>
    </section>

    <!-- Admission Checklist Section -->
    <section id="admission" class="py-5">
        <div class="container" data-aos="fade-up">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="text-primary text-uppercase fw-bold small">Hospital Entry Guide</span>
                    <h2 class="display-6 fw-bold mt-2 mb-4" style="color: var(--heading-color);">Preparing for Inpatient Admission</h2>
                    <p class="text-muted leading-relaxed mb-4">
                        Whether your stay is scheduled for elective surgery or following emergency triage, our admission desk is structured to complete paperwork efficiently so your medical team can focus immediately on your treatment.
                    </p>

                    <h5 class="fw-bold mb-3" style="color: var(--heading-color);">What to Bring With You:</h5>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-2 flex-shrink-0"></i>
                            <div><strong>Identification:</strong> Official government photo ID, passport, or birth certificate (for minors).</div>
                        </li>
                        <li class="mb-2 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-2 flex-shrink-0"></i>
                            <div><strong>Health Insurance:</strong> Valid physical or digital insurance card and any doctor pre-authorization letters.</div>
                        </li>
                        <li class="mb-2 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-2 flex-shrink-0"></i>
                            <div><strong>Current Medications:</strong> An itemized list or actual prescription bottles of your current daily medications.</div>
                        </li>
                        <li class="mb-2 d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-2 flex-shrink-0"></i>
                            <div><strong>Personal Necessities:</strong> Comfortable clothing, non-slip slippers, and personal toiletries for extended stays.</div>
                        </li>
                    </ul>

                    <div class="alert alert-warning border mt-4 small">
                        <i class="bi bi-shield-exclamation me-1"></i><strong>Please Note:</strong> Please leave high-value electronics, significant cash sums, and expensive jewelry at home. A security safe is available upon request at the nursing station.
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm p-4 rounded-4 bg-light">
                        <h4 class="fw-bold mb-3" style="color: var(--heading-color);">Admission Process Workflow</h4>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded-circle p-2 px-3 fw-bold me-3">1</div>
                            <div>
                                <h6 class="fw-bold mb-1">Check-in at Reception Desk</h6>
                                <p class="small text-muted mb-0">Present your ID and insurance card at the main foyer ground floor admission desk.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded-circle p-2 px-3 fw-bold me-3">2</div>
                            <div>
                                <h6 class="fw-bold mb-1">Clinical Triage & Room Allocation</h6>
                                <p class="small text-muted mb-0">Initial vital signs assessment by an admission nurse and escort to your private or semi-private room.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded-circle p-2 px-3 fw-bold me-3">3</div>
                            <div>
                                <h6 class="fw-bold mb-1">Consulting Physician Briefing</h6>
                                <p class="small text-muted mb-0">Your primary attending doctor conducts a comprehensive bedside evaluation and finalizes the care plan.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded-circle p-2 px-3 fw-bold me-3">4</div>
                            <div>
                                <h6 class="fw-bold mb-1">Inpatient Care & Continuous Monitoring</h6>
                                <p class="small text-muted mb-0">Round-the-clock nursing care, scheduled medication rounds, and specialist ward rounds daily.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Visiting Hours Section -->
    <section id="visiting" class="py-5 light-background">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Family & Visitors</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Visiting Hours & Ward Regulations</h2>
                <p class="text-muted">Balancing the emotional support of loved ones with our patients' need for rest and sterile recovery.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-door-open-fill text-primary fs-3 me-2"></i>
                            <h5 class="fw-bold mb-0">General Medical & Surgical Wards</h5>
                        </div>
                        <p class="text-muted small mb-3">Standard inpatient rooms and recovery suites.</p>
                        <div class="p-3 bg-light rounded-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>Mid-Day Visit:</strong>
                                <span>11:00 AM – 01:00 PM</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Evening Visit:</strong>
                                <span>04:30 PM – 07:00 PM</span>
                            </div>
                        </div>
                        <small class="text-muted d-block">&bull; Maximum 2 visitors per patient bed at any one time.</small>
                        <small class="text-muted d-block">&bull; Children under 12 must be accompanied by an adult.</small>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-heart-pulse-fill text-danger fs-3 me-2"></i>
                            <h5 class="fw-bold mb-0">Intensive Care Units (ICU & CCU)</h5>
                        </div>
                        <p class="text-muted small mb-3">Critical care, cardiac telemetry, and neurological ICU.</p>
                        <div class="p-3 bg-light rounded-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>Afternoon Slot:</strong>
                                <span>12:00 PM – 01:00 PM</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Evening Slot:</strong>
                                <span>05:00 PM – 06:00 PM</span>
                            </div>
                        </div>
                        <small class="text-muted d-block">&bull; Restricted strictly to immediate family members.</small>
                        <small class="text-muted d-block">&bull; 1 visitor at a time with sanitization gowning required.</small>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-emoji-smile-fill text-warning fs-3 me-2"></i>
                            <h5 class="fw-bold mb-0">Maternity & Pediatric Units</h5>
                        </div>
                        <p class="text-muted small mb-3">Postnatal maternity suites and children’s ward.</p>
                        <div class="p-3 bg-light rounded-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>Parents & Guardians:</strong>
                                <span class="text-success fw-bold">24/7 Open Access</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>General Visitors:</strong>
                                <span>02:00 PM – 06:00 PM</span>
                            </div>
                        </div>
                        <small class="text-muted d-block">&bull; One designated parent may stay overnight bedside.</small>
                        <small class="text-muted d-block">&bull; NICU visits limited to parents only for hygiene safety.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Patient Rights Section -->
    <section id="rights" class="py-5 bg-white">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Ethical Standards</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Patient Rights & Responsibilities</h2>
                <p class="text-muted">We champion transparency, informed consent, and mutual respect.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-4 rounded-4 border bg-light h-100">
                        <h4 class="h5 fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Your Patient Rights</h4>
                        <ul class="text-muted small mb-0">
                            <li class="mb-2"><strong>Dignified Care:</strong> The right to receive considerate and respectful healthcare regardless of race, gender, nationality, or religion.</li>
                            <li class="mb-2"><strong>Informed Decision Making:</strong> The right to clear, understandable explanations regarding your diagnosis, treatment alternatives, and expected outcomes before consenting.</li>
                            <li class="mb-2"><strong>Medical Privacy:</strong> Strict confidentiality of your medical records and consultation history in compliance with health data protection standards.</li>
                            <li class="mb-2"><strong>Second Opinion:</strong> The right to seek a second clinical opinion or request a change in your primary consultant physician.</li>
                            <li class="mb-0"><strong>Financial Transparency:</strong> An itemized, explained estimate of all clinical costs, lab fees, and medication charges prior to elective procedures.</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-4 rounded-4 border bg-light h-100">
                        <h4 class="h5 fw-bold mb-3 text-secondary"><i class="bi bi-clipboard2-check me-2"></i>Patient Responsibilities</h4>
                        <ul class="text-muted small mb-0">
                            <li class="mb-2"><strong>Accurate History:</strong> Providing accurate and complete medical histories, previous drug allergies, and current medications to your medical team.</li>
                            <li class="mb-2"><strong>Treatment Adherence:</strong> Following the mutually agreed treatment plan and notifying your nurse or doctor promptly of any adverse reactions or pain.</li>
                            <li class="mb-2"><strong>Hospital Consideration:</strong> Respecting hospital hygiene protocols, quiet hours, and showing courtesy to other patients and clinical staff.</li>
                            <li class="mb-2"><strong>Financial Obligation:</strong> Fulfilling financial arrangements or coordinating with your insurance provider in a timely manner.</li>
                            <li class="mb-0"><strong>Safety Compliance:</strong> Observing smoke-free and infection control regulations throughout all hospital facilities.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Insurance & Billing Section -->
    <section id="insurance" class="py-5 light-background">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Direct Settlement</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Accepted Insurance Providers & Payment</h2>
                <p class="text-muted">Partnered with leading national and international underwriters for seamless coverage.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white mb-4">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6">
                        <h4 class="fw-bold mb-3" style="color: var(--heading-color);">Insurance & Billing Coordination Desk</h4>
                        <p class="text-muted small leading-relaxed mb-3">
                            The Medicare Hospital Management System supports flexible claims processing for public health funds, corporate medical covers, and private health insurance schemes.
                        </p>
                        <div class="alert alert-warning border-0 rounded-3 p-3 mb-3 small bg-warning-subtle text-dark">
                            <i class="bi bi-info-circle-fill text-warning me-2"></i>
                            <strong>Verification Notice:</strong> Active insurance partnerships and direct settlement eligibility vary by healthcare institution. Patients are advised to confirm specific underwriter agreements with the hospital billing office prior to admission or elective procedures.
                        </div>
                        <div class="p-3 bg-light rounded-3 small">
                            <strong class="d-block mb-1 text-dark">Have an insurance or billing question?</strong>
                            <span class="text-muted">Contact the hospital billing coordination desk at: <strong>{{ config('hospital.phone') }}</strong> or email <strong>{{ config('hospital.email') }}</strong></span>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="p-4 rounded-3 border bg-light">
                            <h6 class="fw-bold mb-3 text-dark">Step-by-Step Insurance Authorization:</h6>
                            <ol class="small text-muted mb-0 ps-3">
                                <li class="mb-2"><strong>Presentation:</strong> Present your insurance card and national ID card at our triage or reception desk.</li>
                                <li class="mb-2"><strong>Verification:</strong> Our officer verifies active cover, copayment requirements, and scheme benefits.</li>
                                <li class="mb-2"><strong>Pre-Auth Submission:</strong> For surgical or inpatient admissions, our doctors prepare the clinical summary for underwriter pre-authorization.</li>
                                <li class="mb-0"><strong>Discharge Settlement:</strong> Upon medical discharge, you only pay your designated deductible or copay, while the balance is settled directly.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-white">
        <div class="container" data-aos="fade-up">
            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Common Questions</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Frequently Asked Questions</h2>
                <p class="text-muted">Answers to the most common queries from our patients and family members.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion shadow-sm rounded-3 overflow-hidden" id="patientFaqAccordion">

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How early should I arrive for a scheduled specialist consultation?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#patientFaqAccordion">
                                <div class="accordion-body text-muted small">
                                    For first-time visits, we recommend arriving 15 to 20 minutes prior to your appointment time to allow for patient file creation and nurse triage vital signs checks. For follow-up visits, 10 minutes is sufficient.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Are visitor meals provided in the hospital wards?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#patientFaqAccordion">
                                <div class="accordion-body text-muted small">
                                    Inpatient meals are customized specifically to the patient’s clinical dietary requirements. Visitors are welcome to use our 24/7 Cafeteria on the ground floor, which serves fresh meals, snacks, and beverages.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Can I access my diagnostic lab results and radiology scans online?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#patientFaqAccordion">
                                <div class="accordion-body text-muted small">
                                    Yes! Once authorized by your physician, lab results and diagnostic summaries are published securely to the MediCare Patient Portal. You can access the portal login page via the navigation bar above.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    What is the hospital's policy on emergency admissions without insurance?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#patientFaqAccordion">
                                <div class="accordion-body text-muted small">
                                    Under our emergency care charter, all life-threatening emergencies receive immediate resuscitation and stabilization regardless of immediate payment or insurance verification. Financial counseling is conducted once the patient is clinically stable.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
