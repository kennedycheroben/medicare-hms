@extends('layouts.public')

@section('title', 'Set your portal password')
@section('meta_description', 'Choose a password for your patient portal account.')

@section('content')
<section class="py-5 light-background">
    <div class="container" style="max-width: 520px;">
        <meta name="referrer" content="no-referrer">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            @if (! $valid)
                <h1 class="h4 fw-bold mb-3">This link cannot be used</h1>
                <p class="text-muted">The link has expired or was already used. For your safety each link works only once.</p>
                <p class="text-muted small">You can request a new one with “Forgot password”, or ask the registration desk to send a fresh link.</p>
                <a href="{{ route('portal.forgot') }}" class="btn btn-primary">Request a new link</a>
            @else
                <h1 class="h4 fw-bold mb-1">{{ $isReset ? 'Reset your password' : 'Create your password' }}</h1>
                <p class="text-muted small mb-4">Use at least 10 characters, with letters and numbers.</p>

                @if ($errors->any())
                    <div class="alert alert-danger small" role="alert">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('portal.activate.store', $token) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-bold">New password</label>
                        <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required minlength="10">
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label small fw-bold">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required minlength="10">
                    </div>
                    <button class="btn btn-primary w-100 fw-bold" type="submit">Save password</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
