@extends('layouts.public')

@section('title', 'Change your portal password')
@section('meta_description', 'Change your patient portal password.')

@section('content')
<section class="py-5 light-background">
    <div class="container" style="max-width: 520px;">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <h1 class="h4 fw-bold mb-1">Change your password</h1>
            <p class="text-muted small mb-4">
                @if (Auth::guard('patient')->user()->must_change_password)
                    You must choose a new password before you can continue.
                @else
                    Use at least 10 characters, with letters and numbers.
                @endif
            </p>

            @if ($errors->any())
                <div class="alert alert-danger small" role="alert">
                    <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('portal.password.update') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label for="current_password" class="form-label small fw-bold">Current password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label small fw-bold">New password</label>
                    <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required minlength="10">
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label small fw-bold">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required minlength="10">
                </div>
                <button class="btn btn-primary w-100 fw-bold" type="submit">Save new password</button>
            </form>
        </div>
    </div>
</section>
@endsection
