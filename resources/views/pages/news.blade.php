@extends('layouts.public')

@section('title', 'Hospital News, Advisories & Health Breakthroughs')
@section('meta_description', 'Latest clinical news, hospital announcements, medical wellness advisories, and community health updates from MediCare Hospital.')

@section('content')

    <x-page-header
        title="News & Health Advisories"
        subtitle="Medical Breakthroughs, Clinical Announcements, Public Health Advisories, and Community Programs"
        :breadcrumbs="[['label' => 'News & Announcements']]"
    />

    <!-- Featured Health Advisory Section -->
    <section class="py-5 bg-white border-bottom">
        <div class="container" data-aos="fade-up">
            <div class="card border-0 rounded-4 overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); color: #fff;">
                <div class="row g-0 align-items-center">
                    <div class="col-lg-8 p-4 p-md-5">
                        <span class="badge bg-warning text-dark fw-bold mb-3 px-3 py-2 text-uppercase">
                            <i class="bi bi-bell-fill me-1"></i>Public Health Advisory
                        </span>
                        <h2 class="display-6 fw-bold mb-3 text-white">Seasonal Respiratory Illness & Flu Vaccination Clinic</h2>
                        <p class="text-white opacity-90 leading-relaxed mb-4">
                            In light of the recent seasonal spike in respiratory viral infections, MediCare has expanded our outpatient immunization clinics. Walk-in influenza vaccinations and pediatric respiratory assessments are available daily with zero booking fees.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('appointments.create') }}" class="btn btn-light fw-bold text-primary px-4 py-2">
                                Book Vaccination Slot
                            </a>
                            <a href="{{ route('contact') }}" class="btn btn-outline-light px-4 py-2">
                                Inquire About Immunization Clinic
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 text-center p-4">
                        <img src="{{ asset('assets/img/health/consultation-3.webp') }}" alt="Flu Clinic" class="img-fluid rounded-3 shadow">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- News Grid Section -->
    <section class="py-5 light-background">
        <div class="container" data-aos="fade-up">

            <div class="text-center mb-5">
                <span class="text-primary text-uppercase fw-bold small">Latest Updates</span>
                <h2 class="display-6 fw-bold" style="color: var(--heading-color);">Hospital Announcements & Articles</h2>
                <p class="text-muted">Stay informed on clinical innovations, doctor appointments, and community health events.</p>
            </div>

            <div class="row g-4">

                <!-- Article 1 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <img src="{{ asset('assets/img/health/pediatrics-4.webp') }}" alt="Pediatric Wing Expansion" class="w-100" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span><i class="bi bi-calendar3 me-1 text-primary"></i>October 15, 2026</span>
                                <span class="badge bg-light text-primary border">Clinical Expansion</span>
                            </div>
                            <h4 class="h5 fw-bold mb-2">
                                <a href="#" class="text-decoration-none text-dark">MediCare Launches Expanded 24/7 Pediatric Emergency Wing</a>
                            </h4>
                            <p class="text-muted small mb-3">
                                With growing community needs, our dedicated children's emergency wing now features 14 private treatment bays, child-friendly monitoring, and round-the-clock pediatric intensivists.
                            </p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-person me-1"></i>Dr. Amina Patel</small>
                                <span class="text-primary small fw-bold">Read Advisory &rarr;</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 2 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <img src="{{ asset('assets/img/health/facilities-6.webp') }}" alt="New MRI Scanner" class="w-100" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span><i class="bi bi-calendar3 me-1 text-primary"></i>September 28, 2026</span>
                                <span class="badge bg-light text-info border">Technology</span>
                            </div>
                            <h4 class="h5 fw-bold mb-2">
                                <a href="#" class="text-decoration-none text-dark">Next-Generation 3.0T MRI Operational in Radiology Center</a>
                            </h4>
                            <p class="text-muted small mb-3">
                                The high-field 3T MRI provides dramatically faster scan times and unprecedented imaging clarity for neurovascular, spinal, and musculoskeletal diagnostics.
                            </p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-person me-1"></i>Radiology Directorate</small>
                                <span class="text-primary small fw-bold">Read Advisory &rarr;</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 3 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <img src="{{ asset('assets/img/health/cardiology-2.webp') }}" alt="Cardiology Guidelines" class="w-100" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span><i class="bi bi-calendar3 me-1 text-primary"></i>September 12, 2026</span>
                                <span class="badge bg-light text-danger border">Health Tips</span>
                            </div>
                            <h4 class="h5 fw-bold mb-2">
                                <a href="#" class="text-decoration-none text-dark">Preventative Cardiology: Silent Signs of Heart Disease</a>
                            </h4>
                            <p class="text-muted small mb-3">
                                Senior cardiologist Dr. Marcus Vance outlines preventative screening measures, warning signs of early ischemia, and lifestyle modifications that lower cardiac risks.
                            </p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-person me-1"></i>Dr. Marcus Vance</small>
                                <span class="text-primary small fw-bold">Read Advisory &rarr;</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 4 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <img src="{{ asset('assets/img/health/consultation-3.webp') }}" alt="Community Screening Camp" class="w-100" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span><i class="bi bi-calendar3 me-1 text-primary"></i>August 30, 2026</span>
                                <span class="badge bg-light text-success border">Outreach</span>
                            </div>
                            <h4 class="h5 fw-bold mb-2">
                                <a href="#" class="text-decoration-none text-dark">Upcoming Free Community Hypertension & Diabetes Screening</a>
                            </h4>
                            <p class="text-muted small mb-3">
                                MediCare Hospital will host a free public health wellness day on November 10th featuring free blood pressure screenings, BMI analysis, and nutritional counseling.
                            </p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-person me-1"></i>Community Health Team</small>
                                <span class="text-primary small fw-bold">Read Advisory &rarr;</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 5 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <img src="{{ asset('assets/img/health/facilities-6.webp') }}" alt="Accreditation" class="w-100" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span><i class="bi bi-calendar3 me-1 text-primary"></i>August 14, 2026</span>
                                <span class="badge bg-light text-primary border">Accreditation</span>
                            </div>
                            <h4 class="h5 fw-bold mb-2">
                                <a href="#" class="text-decoration-none text-dark">MediCare Re-Accredited with Highest Hospital Quality Score</a>
                            </h4>
                            <p class="text-muted small mb-3">
                                Following an exhaustive 5-day on-site inspection, MediCare Hospital received international re-accreditation with special commendations in surgical infection control.
                            </p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-person me-1"></i>Quality Board</small>
                                <span class="text-primary small fw-bold">Read Advisory &rarr;</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 6 -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
                    <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <img src="{{ asset('assets/img/health/surgery-2.webp') }}" alt="Minimally Invasive Surgery" class="w-100" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span><i class="bi bi-calendar3 me-1 text-primary"></i>July 22, 2026</span>
                                <span class="badge bg-light text-secondary border">Surgical Care</span>
                            </div>
                            <h4 class="h5 fw-bold mb-2">
                                <a href="#" class="text-decoration-none text-dark">Laparoscopic Breakthroughs: Reducing Inpatient Recovery Time</a>
                            </h4>
                            <p class="text-muted small mb-3">
                                How minimally invasive keyhole surgical techniques allow patients undergoing gallstone and hernia repairs to return home within 24 hours.
                            </p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-person me-1"></i>Surgical Team</small>
                                <span class="text-primary small fw-bold">Read Advisory &rarr;</span>
                            </div>
                        </div>
                    </article>
                </div>

            </div>

        </div>
    </section>

@endsection
