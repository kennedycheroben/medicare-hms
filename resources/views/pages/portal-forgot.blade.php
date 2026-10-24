@extends('layouts.public')

@section('title', 'Forgot your portal password')
@section('meta_description', 'Request a link to reset your patient portal password.')

@section('content')
<section class="py-5 light-background">
    <div class="container" style="max-width: 520px;">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <h1 class="h4 fw-bold mb-1">Forgot your password?</h1>
            <p class="text-muted small mb-4">Enter the email address on your patient record. If it has a portal account, we will email you a link that works once for 60 minutes.</p>

            @if (session('status'))
                <div class="alert alert-success small" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('portal.forgot.send') }}">
                @csrf
                <div class="mb-4">
                    <label for="email" class="form-label small fw-bold">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="email" required>
                </div>
                <button class="btn btn-primary w-100 fw-bold" type="submit">Email me a reset link</button>
            </form>
            <p class="small text-muted mt-3 mb-0">No email on file? Ask the registration desk: {{ config('hospital.phone') }}.
                <a href="{{ route('portal.login') }}">Back to sign in</a></p>
        </div>
    </div>
</section>
@endsection
