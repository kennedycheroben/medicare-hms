<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>
    <title>@yield('title', 'Accredited Medical Center & Healthcare Services') | {{ config('app.name', 'MediCare Hospital') }}</title>
    <meta name="description" content="@yield('meta_description', 'MediCare Hospital provides 24/7 emergency care, specialized clinical departments, world-class board-certified physicians, and compassionate patient-centered healthcare.')">
    <meta name="keywords" content="hospital, healthcare, medical clinic, cardiology, neurology, pediatrics, surgery, emergency care, appointments, doctors">

    <!-- Favicons -->
    <link href="{{ asset('assets/img/favicon.svg') }}" rel="icon" type="image/svg+xml">
    <link href="{{ asset('assets/img/favicon.png') }}" rel="icon" type="image/png">
    <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body class="@yield('body_class', 'index-page')">

    <!-- Global Hospital Header -->
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

            <a href="{{ route('home') }}" class="logo d-flex align-items-center me-auto me-xl-0 text-decoration-none">
                <i class="bi bi-heart-pulse text-primary fs-3 me-2"></i>
                <h1 class="sitename mb-0">{{ config('app.name', 'MediCare') }}</h1>
            </a>

            <nav id="navmenu" class="navmenu" aria-label="Main Navigation">
                <ul>
                    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                    <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a></li>
                    <li><a href="{{ route('departments') }}" class="{{ request()->routeIs('departments*') ? 'active' : '' }}">Departments</a></li>
                    <li><a href="{{ route('services') }}" class="{{ request()->routeIs('services') ? 'active' : '' }}">Services</a></li>
                    <li><a href="{{ route('doctors') }}" class="{{ request()->routeIs('doctors*') ? 'active' : '' }}">Doctors</a></li>
                    <li><a href="{{ route('patient-info') }}" class="{{ request()->routeIs('patient-info') ? 'active' : '' }}">Patient Info</a></li>
                    <li><a href="{{ route('news') }}" class="{{ request()->routeIs('news') ? 'active' : '' }}">News</a></li>
                    <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a></li>
                    <li>
                        <a href="{{ route('portal.login') }}" class="{{ request()->routeIs('portal.login') ? 'active' : '' }}">
                            <i class="bi bi-person-lock me-1"></i>Portal
                        </a>
                    </li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list" role="button" aria-label="Toggle navigation"></i>
            </nav>

            <a class="btn-getstarted d-none d-sm-inline-block" href="{{ route('appointments.create') }}">
                <i class="bi bi-calendar-check me-1"></i>Book Appointment
            </a>

        </div>
    </header>

    <!-- Main Content Area -->
    <main class="main" id="main-content">
        @include('components.flash-messages')
        @yield('content')
    </main>

    <!-- Global Hospital Footer -->
    <footer id="footer" class="footer dark-background">

        <div class="footer-top py-5">
            <div class="container">
                <div class="row gy-4">

                    <!-- Col 1: About & Emergency Contacts -->
                    <!-- Col 1: About & Emergency Contacts -->
                    <div class="col-lg-4 col-md-6 footer-about">
                        <a href="{{ route('home') }}" class="logo d-flex align-items-center text-decoration-none mb-3">
                            <i class="bi bi-heart-pulse text-info fs-3 me-2"></i>
                            <span class="sitename text-white fs-4 fw-bold">{{ config('hospital.name', 'MediCare Hospital') }}</span>
                        </a>
                        <p class="text-light small">
                            {{ config('hospital.tagline', 'Healthcare Management System') }}
                        </p>
                        <div class="footer-contact pt-2 small">
                            <p class="mb-1"><i class="bi bi-geo-alt me-2 text-info"></i>{{ config('hospital.address') }}</p>
                            <p class="mb-1"><i class="bi bi-telephone me-2 text-info"></i>General Enquiries: {{ config('hospital.phone') }}</p>
                            <p class="mb-1"><i class="bi bi-shield-exclamation me-2 text-danger"></i><strong>Emergency Line:</strong> <span class="text-info fw-bold">{{ config('hospital.emergency_phone') }}</span></p>
                            <p class="mb-0"><i class="bi bi-envelope me-2 text-info"></i>{{ config('hospital.email') }}</p>
                        </div>
                    </div>

                    <!-- Col 2: Navigation Links -->
                    <div class="col-lg-2 col-md-3 col-6 footer-links">
                        <h5 class="text-white mb-3">Quick Links</h5>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="{{ route('home') }}" class="text-light text-decoration-none">Home</a></li>
                            <li class="mb-2"><a href="{{ route('about') }}" class="text-light text-decoration-none">About Us</a></li>
                            <li class="mb-2"><a href="{{ route('departments') }}" class="text-light text-decoration-none">Departments</a></li>
                            <li class="mb-2"><a href="{{ route('services') }}" class="text-light text-decoration-none">Services & Care</a></li>
                            <li class="mb-2"><a href="{{ route('doctors') }}" class="text-light text-decoration-none">Specialist Doctors</a></li>
                            <li class="mb-2"><a href="{{ route('appointments.create') }}" class="text-light text-decoration-none">Book Appointment</a></li>
                            <li class="mb-2"><a href="{{ route('contact') }}" class="text-light text-decoration-none">Contact Us</a></li>
                        </ul>
                    </div>

                    <!-- Col 3: Clinical Specialties -->
                    <div class="col-lg-3 col-md-3 col-6 footer-links">
                        <h5 class="text-white mb-3">Clinical Specialties</h5>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="{{ route('departments.show', 'cardiology') }}" class="text-light text-decoration-none">Cardiology & Heart Care</a></li>
                            <li class="mb-2"><a href="{{ route('departments.show', 'neurology') }}" class="text-light text-decoration-none">Neurology & Brain Sciences</a></li>
                            <li class="mb-2"><a href="{{ route('departments.show', 'pediatrics') }}" class="text-light text-decoration-none">Pediatrics & Neonatal Care</a></li>
                            <li class="mb-2"><a href="{{ route('departments.show', 'orthopedics') }}" class="text-light text-decoration-none">Orthopedic Surgery</a></li>
                            <li class="mb-2"><a href="{{ route('departments.show', 'oncology') }}" class="text-light text-decoration-none">Comprehensive Oncology</a></li>
                            <li class="mb-2"><a href="{{ route('departments.show', 'emergency') }}" class="text-light text-decoration-none">Trauma & Emergency Care</a></li>
                        </ul>
                    </div>

                    <!-- Col 4: Patient Guidance & Portal -->
                    <div class="col-lg-3 col-md-6 footer-links">
                        <h5 class="text-white mb-3">Patient Resources</h5>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="{{ route('patient-info') }}" class="text-light text-decoration-none"><i class="bi bi-info-circle me-1 text-info"></i>Visiting Hours & Policies</a></li>
                            <li class="mb-2"><a href="{{ route('patient-info') }}#insurance" class="text-light text-decoration-none"><i class="bi bi-credit-card me-1 text-info"></i>Insurance & Payment Plans</a></li>
                            <li class="mb-2"><a href="{{ route('news') }}" class="text-light text-decoration-none"><i class="bi bi-newspaper me-1 text-info"></i>Health Advisories & News</a></li>
                            <li class="mb-2"><a href="{{ route('portal.login') }}" class="text-light text-decoration-none"><i class="bi bi-box-arrow-in-right me-1 text-info"></i>Patient Portal Entry</a></li>
                            <li class="mb-2"><a href="{{ route('health') }}" class="text-muted text-decoration-none"><i class="bi bi-heart-pulse-fill me-1"></i>System Status</a></li>
                        </ul>
                        <div class="mt-3 p-3 rounded" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1);">
                            <span class="text-uppercase text-info small fw-bold d-block mb-1">Emergency Services</span>
                            <span class="text-white small">For urgent trauma or acute medical care, call <strong class="text-info">{{ config('hospital.emergency_phone') }}</strong> or visit the hospital emergency department directly.</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        @if(config('hospital.is_demo'))
            <div class="py-2 px-3 bg-dark bg-opacity-75 border-top border-secondary border-opacity-25 text-center small text-light">
                <i class="bi bi-info-circle text-info me-1"></i>
                <span class="small text-muted-50"><strong>Demonstration Template Notice:</strong> Provider profiles, schedules, and clinical offerings shown on this installation are demonstration templates for software evaluation. Actual institutional credentials and rosters are configured by the deploying hospital administration.</span>
            </div>
        @endif

        <!-- Footer Bottom & Copyright -->
        <div class="copyright py-3 border-top border-secondary border-opacity-25">
            <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-center text-center text-lg-start small text-muted">
                <div class="mb-2 mb-lg-0">
                    &copy; {{ date('Y') }} <strong class="text-light">{{ config('hospital.name', 'MediCare Hospital') }}</strong>. All Rights Reserved. Hospital Management System.
                </div>
                <div class="social-links d-flex gap-3 fs-5">
                    <a href="#" class="text-muted" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-muted" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-muted" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-muted" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>

    </footer>

    <!-- Scroll Top Button -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center" aria-label="Back to top">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/purecounter/purecounter_vanilla.js') }}"></script>

    <!-- Main JS File -->
    <script src="{{ asset('assets/js/main.js') }}"></script>

    @stack('scripts')
</body>

</html>
