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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

    <style>
        :root {
            --hms-primary: #1977cc;
            --hms-primary-dark: #125593;
            --hms-sidebar-bg: #1e293b;
            --hms-sidebar-hover: #334155;
            --hms-sidebar-active: #2563eb;
            --hms-topbar-height: 64px;
            --hms-sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        /* Topbar */
        .admin-topbar {
            height: var(--hms-topbar-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
        }

        /* Sidebar */
        .admin-sidebar {
            width: var(--hms-sidebar-width);
            background: var(--hms-sidebar-bg);
            position: fixed;
            top: var(--hms-topbar-height);
            bottom: 0;
            left: 0;
            z-index: 1020;
            overflow-y: auto;
            color: #94a3b8;
            transition: all 0.2s ease;
        }

        .admin-sidebar .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 6px;
            margin: 0.15rem 0.75rem;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .admin-sidebar .nav-link:hover {
            color: #ffffff;
            background-color: var(--hms-sidebar-hover);
        }

        .admin-sidebar .nav-link.active {
            color: #ffffff;
            background-color: var(--hms-sidebar-active);
        }

        .admin-sidebar .nav-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 1rem 1.25rem 0.35rem;
            font-weight: 700;
        }

        /* Main Content Wrapper */
        .admin-main {
            margin-left: var(--hms-sidebar-width);
            margin-top: var(--hms-topbar-height);
            padding: 2rem;
            min-height: calc(100vh - var(--hms-topbar-height));
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
                padding: 1.25rem;
            }
        }

        /* Badges */
        .badge-role-super_admin { background-color: #6366f1; color: #fff; }
        .badge-role-hospital_director { background-color: #0284c7; color: #fff; }
        .badge-role-doctor { background-color: #0d9488; color: #fff; }
        .badge-role-nurse { background-color: #16a34a; color: #fff; }
        .badge-role-pharmacist { background-color: #d97706; color: #fff; }
        .badge-role-lab_technician { background-color: #0891b2; color: #fff; }
        .badge-role-cashier { background-color: #ca8a04; color: #fff; }
        .badge-role-receptionist { background-color: #475569; color: #fff; }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Topbar -->
    <header class="admin-topbar justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none py-1 px-2" type="button" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <i class="bi bi-heart-pulse-fill text-primary fs-4"></i>
                <span class="fw-bold fs-5 text-dark">MediCare <span class="text-primary">HMS</span></span>
                <span class="badge bg-light text-secondary border ms-1 d-none d-sm-inline">Staff Portal</span>
            </a>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('guide') }}" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1" target="_blank">
                <i class="bi bi-question-circle"></i>
                <span>Operations Guide</span>
            </a>

            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center gap-1" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>Public Website</span>
            </a>

            @auth
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border d-flex align-items-center gap-2 dropdown-toggle" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-primary"></i>
                        <span class="fw-medium text-dark">{{ Auth::user()->name }}</span>
                        @php
                            $roleVal = Auth::user()->role instanceof \App\Enums\UserRole ? Auth::user()->role->value : (string) Auth::user()->role;
                            $roleLabel = Auth::user()->role instanceof \App\Enums\UserRole ? Auth::user()->role->label() : ucfirst(str_replace('_', ' ', $roleVal));
                        @endphp
                        <span class="badge badge-role-{{ $roleVal }} ms-1">{{ $roleLabel }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuButton">
                        <li class="px-3 py-2 border-bottom">
                            <div class="small text-muted">Signed in as</div>
                            <div class="fw-semibold text-dark">{{ Auth::user()->email }}</div>
                            <div class="small text-muted mt-1">Role: <span class="badge badge-role-{{ $roleVal }}">{{ $roleLabel }}</span></div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </header>

    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="py-3">
            <div class="nav-section-title">Core Operations</div>
            <nav class="nav flex-column">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </nav>

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

            <div class="nav-section-title">Patients & Front Desk</div>
            <nav class="nav flex-column">
                @if($isIntake)
                    <a href="{{ route('admin.intake.index') }}" class="nav-link {{ request()->routeIs('admin.intake.*') ? 'active' : '' }}">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Front-Desk Intake</span>
                    </a>
                @endif

                @if($isPatientViewer)
                    <a href="{{ route('admin.appointments.index') }}" class="nav-link {{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar2-check"></i>
                        <span>Appointments</span>
                    </a>
                    <a href="{{ route('admin.patients.index') }}" class="nav-link {{ request()->routeIs('admin.patients.*') ? 'active' : '' }}">
                        <i class="bi bi-person-vcard"></i>
                        <span>Patient Directory (PMI)</span>
                    </a>
                @endif
            </nav>

            <div class="nav-section-title">Clinical & Care</div>
            <nav class="nav flex-column">
                @if($isTriage)
                    <a href="{{ route('admin.triage.index') }}" class="nav-link {{ request()->routeIs('admin.triage.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-pulse"></i>
                        <span>Initial Assessment & Vital Signs</span>
                    </a>
                @endif

                @if($isDoctor)
                    <a href="{{ route('admin.consultations.index') }}" class="nav-link {{ request()->routeIs('admin.consultations.*') ? 'active' : '' }}">
                        <i class="bi bi-stethoscope"></i>
                        <span>Doctor Consultations</span>
                    </a>
                @endif

                @if($isPharmacist)
                    <a href="{{ route('admin.pharmacy.index') }}" class="nav-link {{ request()->routeIs('admin.pharmacy.*') ? 'active' : '' }}">
                        <i class="bi bi-capsule"></i>
                        <span>Pharmacy Dispensary</span>
                    </a>
                @endif

                @if($canViewInventory)
                    <a href="{{ route('admin.inventory.index') }}" class="nav-link {{ request()->routeIs('admin.inventory.index') || request()->routeIs('admin.inventory.medicines.*') ? 'active' : '' }}">
                        <i class="bi bi-boxes"></i>
                        <span>Medicine Inventory</span>
                    </a>
                    <a href="{{ route('admin.inventory.movements') }}" class="nav-link {{ request()->routeIs('admin.inventory.movements') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Stock Movements</span>
                    </a>
                @endif

                @if($isLab)
                    <a href="{{ route('admin.laboratory.index') }}" class="nav-link {{ request()->routeIs('admin.laboratory.*') ? 'active' : '' }}">
                        <i class="bi bi-eyedropper"></i>
                        <span>Laboratory</span>
                    </a>
                @endif
            </nav>

            <div class="nav-section-title">Administration</div>
            <nav class="nav flex-column">
                @if($isBilling)
                    <a href="{{ route('admin.billing.index') }}" class="nav-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt"></i>
                        <span>Billing & Cashier</span>
                    </a>
                @endif

                @if($isDirector)
                    <a href="{{ route('admin.doctors.index') }}" class="nav-link {{ request()->routeIs('admin.doctors.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i>
                        <span>Physician Directory</span>
                    </a>

                    <a href="{{ route('admin.articles.index') }}" class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
                        <i class="bi bi-newspaper"></i>
                        <span>News &amp; Announcements</span>
                    </a>

                    <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Executive Reports</span>
                    </a>
                @endif

                @if($isSuperAdmin)
                    <a href="{{ route('admin.staff.index') }}" class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span>Staff Directory</span>
                    </a>

                    <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-lock"></i>
                        <span>Security Audit Log</span>
                    </a>
                @endif

                <div class="px-3 pt-3 mt-3 border-top border-secondary border-opacity-25">
                    <a href="{{ route('guide') }}" class="nav-link text-muted small py-1" target="_blank">
                        <i class="bi bi-book"></i>
                        <span>System User Guide</span>
                    </a>
                </div>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please correct the following errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Vendor JS -->
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.getElementById('sidebarToggle');
            if (toggle && sidebar) {
                toggle.addEventListener('click', function () {
                    sidebar.classList.toggle('show');
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
