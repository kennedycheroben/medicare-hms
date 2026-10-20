<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Sign In | {{ config('app.name', 'MediCare HMS') }}</title>

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
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            max-width: 460px;
            width: 100%;
            overflow: hidden;
        }

        .auth-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }

        .auth-body {
            padding: 2rem;
        }

        .auth-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            font-size: 0.85rem;
            text-align: center;
            color: #64748b;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <main class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                <i class="bi bi-heart-pulse-fill fs-2"></i>
            </div>
            <h1 class="h4 fw-bold text-dark mb-1">MediCare Hospital</h1>
            <p class="text-muted small mb-2">Clinical & Administrative Staff Portal</p>
            <div class="security-badge">
                <i class="bi bi-shield-check text-success"></i>
                <span>Role-Based Access Controlled</span>
            </div>
        </div>

        <div class="auth-body">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show small" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Authentication failed:</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" autocomplete="on">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-dark">Staff Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="staff@medicare.local">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small text-dark">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted" for="remember">
                            Remember on this terminal
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-lock-fill me-1"></i> Authenticate & Enter
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <span class="small text-muted">Are you a patient?</span>
                <a href="{{ route('portal.login') }}" class="small text-decoration-none fw-medium ms-1">Go to Patient Portal &rarr;</a>
            </div>
        </div>

        <div class="auth-footer">
            <div class="mb-1">
                <i class="bi bi-info-circle me-1"></i> Authorized hospital personnel only.
            </div>
            <div class="text-xs text-secondary" style="font-size: 0.75rem;">
                All login attempts, access requests, and terminal IPs are recorded in the immutable audit log for compliance.
            </div>
            <div class="mt-2">
                <a href="{{ route('home') }}" class="text-decoration-none text-muted small">&larr; Return to Public Website</a>
            </div>
        </div>
    </main>

    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
