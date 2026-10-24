<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') | {{ config('app.name', 'MediCare HMS') }} Staff Portal</title>

    <!-- Favicons -->
    <link href="{{ asset('assets/img/favicon.svg') }}" rel="icon" type="image/svg+xml">
    <link href="{{ asset('assets/img/favicon.png') }}" rel="icon" type="image/png">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-assets/vendor/phosphor-icons/phosphor-icons.css') }}" rel="stylesheet">

    <!-- Modern Admin Main CSS -->
    <link href="{{ asset('admin-assets/css/main.css') }}" rel="stylesheet">

    <style>
        :root {
            --hms-brand-primary: #1977cc;
            --hms-brand-dark: #125593;
        }

        /* RBAC Role Badges */
        .badge-role-super_admin { background-color: #6366f1; color: #ffffff; }
        .badge-role-hospital_director { background-color: #0284c7; color: #ffffff; }
        .badge-role-doctor { background-color: #0d9488; color: #ffffff; }
        .badge-role-nurse { background-color: #16a34a; color: #ffffff; }
        .badge-role-pharmacist { background-color: #d97706; color: #ffffff; }
        .badge-role-lab_technician { background-color: #0891b2; color: #ffffff; }
        .badge-role-cashier { background-color: #ca8a04; color: #ffffff; }
        .badge-role-receptionist { background-color: #475569; color: #ffffff; }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent-color, #1a66c4);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .profile-menu-header {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
            background: var(--border-color-light, #f8fafc);
        }

        .fs-xs {
            font-size: 0.72rem;
        }

        /* Soft Badges */
        .badge-soft-primary { background-color: rgba(26, 102, 196, 0.12); color: #1a66c4; }
        .badge-soft-success { background-color: rgba(34, 197, 94, 0.12); color: #15803d; }
        .badge-soft-warning { background-color: rgba(245, 158, 11, 0.12); color: #b45309; }
        .badge-soft-danger { background-color: rgba(239, 68, 68, 0.12); color: #b91c1c; }
        .badge-soft-info { background-color: rgba(6, 182, 212, 0.12); color: #0e7490; }

        /* Smooth card hover lift */
        .card-lift {
            transition: transform var(--transition-base, 200ms ease), box-shadow var(--transition-base, 200ms ease);
        }
        .card-lift:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md, 0 4px 6px -1px rgb(0 0 0 / 0.1));
        }

        /* Responsive table wrapping */
        .table-responsive {
            scrollbar-width: thin;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Header Navigation -->
    <header class="header">
        <!-- Header Left -->
        <div class="header-left">
            <a href="{{ route('admin.dashboard') }}" class="header-logo text-decoration-none">
                <i class="bi bi-heart-pulse-fill text-primary fs-4 me-1"></i>
                <span class="fw-bold">MediCare <span class="text-primary">HMS</span></span>
            </a>
            <button class="sidebar-toggle" title="Toggle Sidebar" type="button" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
        </div>

        <!-- Header Search (Desktop) -->
        <div class="header-search">
            <form class="search-form" action="{{ route('admin.appointments.index') }}" method="GET">
                <i class="bi bi-search search-icon"></i>
                <input type="search" name="search" placeholder="Search appointments, patients, MRN..." autocomplete="off">
                <kbd class="search-shortcut">Ctrl+K</kbd>
            </form>
        </div>

        <!-- Header Right -->
        <div class="header-right">
            <!-- Desktop Quick Actions -->
            <div class="header-actions-desktop">
                <!-- Apps Dropdown -->
                <div class="header-action dropdown apps-dropdown">
                    <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Hospital Modules" type="button">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end apps-menu shadow-lg">
                        <div class="apps-menu-header">
                            <h6 class="mb-0 fw-semibold text-dark">Quick Hospital Access</h6>
                        </div>
                        <div class="apps-grid">
                            <a href="{{ route('admin.dashboard') }}" class="apps-item">
                                <div class="apps-item-icon primary">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <span class="apps-item-title">Dashboard</span>
                            </a>
                            @if(Auth::user() && Auth::user()->hasRole(['super_admin', 'receptionist', 'nurse']))
                                <a href="{{ route('admin.intake.index') }}" class="apps-item">
                                    <div class="apps-item-icon success">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                    </div>
                                    <span class="apps-item-title">Intake</span>
                                </a>
                            @endif
                            @if(Auth::user() && Auth::user()->hasRole(['super_admin', 'hospital_director', 'doctor', 'nurse', 'receptionist']))
                                <a href="{{ route('admin.patients.index') }}" class="apps-item">
                                    <div class="apps-item-icon info">
                                        <i class="bi bi-person-vcard"></i>
                                    </div>
                                    <span class="apps-item-title">Patients</span>
                                </a>
                                <a href="{{ route('admin.appointments.index') }}" class="apps-item">
                                    <div class="apps-item-icon warning">
                                        <i class="bi bi-calendar2-check"></i>
                                    </div>
                                    <span class="apps-item-title">Bookings</span>
                                </a>
                            @endif
                            @if(Auth::user() && Auth::user()->hasRole(['super_admin', 'nurse', 'receptionist']))
                                <a href="{{ route('admin.triage.index') }}" class="apps-item">
                                    <div class="apps-item-icon danger">
                                        <i class="bi bi-clipboard2-pulse"></i>
                                    </div>
                                    <span class="apps-item-title">Triage</span>
                                </a>
                            @endif
                            @if(Auth::user() && Auth::user()->hasRole(['super_admin', 'doctor']))
                                <a href="{{ route('admin.clinical.index') }}" class="apps-item">
                                    <div class="apps-item-icon primary">
                                        <i class="bi bi-stethoscope"></i>
                                    </div>
                                    <span class="apps-item-title">Consultations</span>
                                </a>
                            @endif
                            @if(Auth::user() && Auth::user()->hasRole(['super_admin', 'pharmacist']))
                                <a href="{{ route('admin.pharmacy.index') }}" class="apps-item">
                                    <div class="apps-item-icon warning">
                                        <i class="bi bi-capsule"></i>
                                    </div>
                                    <span class="apps-item-title">Pharmacy</span>
                                </a>
                            @endif
                            @if(Auth::user() && Auth::user()->hasRole(['super_admin', 'hospital_director', 'pharmacist']))
                                <a href="{{ route('admin.inventory.index') }}" class="apps-item">
                                    <div class="apps-item-icon success">
                                        <i class="bi bi-boxes"></i>
                                    </div>
                                    <span class="apps-item-title">Inventory</span>
                                </a>
                            @endif
                            <a href="{{ route('guide') }}" target="_blank" class="apps-item">
                                <div class="apps-item-icon muted">
                                    <i class="bi bi-book"></i>
                                </div>
                                <span class="apps-item-title">User Guide</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Dark/Light Theme Toggle -->
                <button class="header-action theme-toggle" title="Toggle Theme" type="button" aria-label="Toggle Theme">
                    <i class="ph ph-moon theme-icon-dark"></i>
                    <i class="ph ph-sun theme-icon-light"></i>
                </button>

                <!-- Operations Guide Shortcut -->
                <a href="{{ route('guide') }}" class="header-action" title="Operations Workbook" target="_blank">
                    <i class="bi bi-question-circle"></i>
                </a>

                <!-- Public Website Link -->
                <a href="{{ route('home') }}" class="header-action" title="View Public Website" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>

            <!-- User Profile Dropdown -->
            @auth
                @php
                    $user = Auth::user();
                    $roleVal = $user->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user->role;
                    $roleLabel = $user->role instanceof \App\Enums\UserRole ? $user->role->label() : ucfirst(str_replace('_', ' ', $roleVal));
                @endphp
                <div class="header-action dropdown profile-dropdown ms-1">
                    <button class="dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent py-1 px-2 rounded-2" data-bs-toggle="dropdown" aria-expanded="false" title="User Menu" type="button">
                        <div class="avatar-circle">
                            <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                        <div class="d-none d-md-flex flex-column text-start" style="line-height: 1.2;">
                            <span class="fw-semibold text-dark small">{{ $user->name }}</span>
                            <span class="text-muted extra-small" style="font-size: 0.72rem;">{{ $roleLabel }}</span>
                        </div>
                        <i class="bi bi-chevron-down d-none d-md-inline ms-1 fs-xs text-muted"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 260px;">
                        <div class="profile-menu-header">
                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                            <div class="text-muted small text-truncate">{{ $user->email }}</div>
                            <div class="mt-2 d-flex align-items-center gap-1">
                                <span class="badge badge-role-{{ $roleVal }}">{{ $roleLabel }}</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Online</span>
                            </div>
                        </div>
                        <div class="py-1">
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2 text-primary"></i>
                                <span>Dashboard</span>
                            </a>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('guide') }}" target="_blank">
                                <i class="bi bi-book text-muted"></i>
                                <span>Operations Workbook</span>
                            </a>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('home') }}" target="_blank">
                                <i class="bi bi-globe text-muted"></i>
                                <span>Public Hospital Site</span>
                            </a>
                        </div>
                        <div class="dropdown-divider my-0"></div>
                        <div class="py-1">
                            <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </header>

    <!-- Mobile Header Quick Menu -->
    <div class="mobile-header-menu">
        <div class="mobile-header-menu-content">
            <button class="mobile-menu-item theme-toggle" title="Toggle Theme" type="button">
                <i class="ph ph-moon theme-icon-dark"></i>
                <i class="ph ph-sun theme-icon-light"></i>
                <span class="mobile-menu-label">Theme</span>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="mobile-menu-item">
                <i class="bi bi-speedometer2"></i>
                <span class="mobile-menu-label">Dashboard</span>
            </a>
            <a href="{{ route('guide') }}" target="_blank" class="mobile-menu-item">
                <i class="bi bi-book"></i>
                <span class="mobile-menu-label">Guide</span>
            </a>
            <form action="{{ route('admin.logout') }}" method="POST" class="d-inline m-0">
                @csrf
                <button type="submit" class="mobile-menu-item mobile-menu-item-danger border-0 bg-transparent w-100 text-start">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="mobile-menu-label">Sign Out</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="adminSidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-logo text-decoration-none">
                <i class="bi bi-heart-pulse-fill text-primary fs-4 me-2"></i>
                <span>MediCare HMS</span>
            </a>
            <button class="sidebar-close" title="Close Sidebar" type="button" aria-label="Close Sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        @php
            $user = Auth::user();
            $isSuperAdmin = $user && $user->hasRole('super_admin');
            $isDirector = $user && $user->hasRole(['super_admin', 'hospital_director']);
            $isDoctor = $user && $user->hasRole('doctor');
            $isPharmacist = $user && $user->hasRole('pharmacist');
            $canViewInventory = $user && $user->hasRole(['super_admin', 'hospital_director', 'pharmacist']);
            $isTriage = $user && $user->hasRole(['super_admin', 'nurse', 'receptionist']);
            $isIntake = $user && $user->hasRole(['super_admin', 'receptionist', 'nurse']);
            $isPatientViewer = $user && $user->hasRole(['super_admin', 'hospital_director', 'doctor', 'nurse', 'receptionist']);
            $isLab = $user && $user->hasRole(['super_admin', 'lab_technician']);
            $isBilling = $user && $user->hasRole(['super_admin', 'cashier']);
        @endphp

        <!-- Sidebar Navigation Menu -->
        <nav class="sidebar-nav">
            <ul class="nav-menu">
                <li class="nav-heading"><span>Core Operations</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" data-sidebar-tooltip="Dashboard">
                        <i class="ph-light ph-squares-four"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-heading"><span>Patients & Intake</span></li>
                @if($isIntake)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.intake.*') ? 'active' : '' }}" href="{{ route('admin.intake.index') }}" data-sidebar-tooltip="Front-Desk Intake">
                            <i class="ph-light ph-sign-in"></i>
                            <span>Front-Desk Intake</span>
                        </a>
                    </li>
                @endif

                @if($isPatientViewer)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}" href="{{ route('admin.appointments.index') }}" data-sidebar-tooltip="Appointments">
                            <i class="ph-light ph-calendar-check"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.patients.*') ? 'active' : '' }}" href="{{ route('admin.patients.index') }}" data-sidebar-tooltip="Patient Directory">
                            <i class="ph-light ph-address-book"></i>
                            <span>Patient Master Index</span>
                        </a>
                    </li>
                @endif

                <li class="nav-heading"><span>Clinical & Care</span></li>
                @if($isTriage)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.triage.*') ? 'active' : '' }}" href="{{ route('admin.triage.index') }}" data-sidebar-tooltip="Triage & Vitals">
                            <i class="ph-light ph-heartbeat"></i>
                            <span>Initial Assessment & Vitals</span>
                        </a>
                    </li>
                @endif

                @if($isDoctor)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.consultations.*') ? 'active' : '' }}" href="{{ route('admin.consultations.index') }}" data-sidebar-tooltip="Doctor Consultations">
                            <i class="ph-light ph-stethoscope"></i>
                            <span>Doctor Consultations</span>
                        </a>
                    </li>
                @endif

                @if($isPharmacist)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.pharmacy.*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.index') }}" data-sidebar-tooltip="Pharmacy Dispensary">
                            <i class="ph-light ph-pill"></i>
                            <span>Pharmacy Dispensary</span>
                        </a>
                    </li>
                @endif

                @if($canViewInventory)
                    <li class="nav-item has-submenu {{ request()->routeIs('admin.inventory.*') ? 'open active' : '' }}">
                        <a class="nav-link" href="#" aria-expanded="{{ request()->routeIs('admin.inventory.*') ? 'true' : 'false' }}" data-sidebar-tooltip="Inventory">
                            <i class="ph-light ph-package"></i>
                            <span>Medicine Inventory</span>
                            <i class="ph-light ph-caret-down nav-arrow"></i>
                        </a>
                        <ul class="nav-submenu">
                            <li><a class="nav-link {{ request()->routeIs('admin.inventory.index') || request()->routeIs('admin.inventory.medicines.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}">Formulary Stock</a></li>
                            <li><a class="nav-link {{ request()->routeIs('admin.inventory.movements') ? 'active' : '' }}" href="{{ route('admin.inventory.movements') }}">Stock Movements</a></li>
                        </ul>
                    </li>
                @endif

                @if($isLab)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.laboratory.*') ? 'active' : '' }}" href="{{ route('admin.laboratory.index') }}" data-sidebar-tooltip="Laboratory">
                            <i class="ph-light ph-flask"></i>
                            <span>Laboratory</span>
                        </a>
                    </li>
                @endif

                <li class="nav-heading"><span>Hospital Administration</span></li>
                @if($isBilling)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}" href="{{ route('admin.billing.index') }}" data-sidebar-tooltip="Billing & Cashier">
                            <i class="ph-light ph-receipt"></i>
                            <span>Billing & Cashier</span>
                        </a>
                    </li>
                @endif

                @if($isDirector)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.doctors.*') ? 'active' : '' }}" href="{{ route('admin.doctors.index') }}" data-sidebar-tooltip="Physician Directory">
                            <i class="ph-light ph-user-circle-gear"></i>
                            <span>Physician Directory</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}" href="{{ route('admin.articles.index') }}" data-sidebar-tooltip="News & Articles">
                            <i class="ph-light ph-newspaper"></i>
                            <span>News & Announcements</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}" data-sidebar-tooltip="Executive Reports">
                            <i class="ph-light ph-chart-bar"></i>
                            <span>Executive Reports</span>
                        </a>
                    </li>
                @endif

                @if($isSuperAdmin)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}" data-sidebar-tooltip="Staff Accounts">
                            <i class="ph-light ph-users-three"></i>
                            <span>Staff Accounts & RBAC</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}" data-sidebar-tooltip="Audit Trail">
                            <i class="ph-light ph-shield-check"></i>
                            <span>Centralized Audit Trail</span>
                        </a>
                    </li>
                @endif

                <li class="nav-heading"><span>Help & Guides</span></li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('guide') }}" target="_blank" data-sidebar-tooltip="User Guide">
                        <i class="ph-light ph-book-open"></i>
                        <span>System User Guide</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Sidebar Overlay (Mobile Tap-to-Close) -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content Container -->
    <main class="main" id="main">
        <div class="main-content">
            <!-- Flash Session Alerts -->
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <span class="fw-semibold">Please review the following input validation errors:</span>
                    </div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-copyright text-muted small">
                    &copy; {{ date('Y') }} <strong>MediCare Hospital Management System</strong>. Production Healthcare Platform.
                </div>
                <div class="footer-links small text-muted">
                    <a href="{{ route('guide') }}" target="_blank">Operations Manual</a>
                    <span>&bull;</span>
                    <a href="{{ route('privacy') }}" target="_blank">Privacy &amp; Data Protection</a>
                    <span>&bull;</span>
                    <a href="{{ route('terms') }}" target="_blank">Terms &amp; Consent</a>
                    <span>&bull;</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-shield-lock-fill me-1"></i> RBAC Enforced
                    </span>
                </div>
            </div>
        </footer>
    </main>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top" title="Back to top" aria-label="Back to top">
        <i class="bi bi-arrow-up"></i>
    </a>

    <!-- Vendor Scripts -->
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin-assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('admin-assets/js/theme.js') }}"></script>
    <script src="{{ asset('admin-assets/js/main.js') }}"></script>

    @stack('scripts')
</body>
</html>
