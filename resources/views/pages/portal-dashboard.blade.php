@extends('layouts.public')

@section('title', 'My Health Portal')
@section('meta_description', 'Your appointments, visits and prescriptions.')

@section('content')
<section class="py-5 light-background">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0">Hello, {{ $patient->first_name }}</h1>
                <div class="text-muted small">Medical Record Number: <code>{{ $patient->mrn }}</code></div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('portal.password.edit') }}" class="btn btn-outline-secondary btn-sm">Change password</a>
                <form method="POST" action="{{ route('portal.logout') }}">@csrf
                    <button class="btn btn-outline-danger btn-sm" type="submit">Sign out</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">Upcoming appointments</h2>
                    @forelse ($appointments as $a)
                        <div class="border-bottom py-2">
                            <strong>{{ $a->appointment_date->format('D, d M Y') }} · {{ $a->appointment_time }}</strong>
                            <div class="small text-muted">{{ $a->department?->name }}@if($a->doctor) · {{ $a->doctor->name ?? '' }}@endif · {{ ucfirst($a->status) }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-2">You have no upcoming appointments.</p>
                    @endforelse
                    <a href="{{ route('appointments.create') }}" class="btn btn-primary btn-sm mt-3 align-self-start">Book an appointment</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">Recent visits</h2>
                    @forelse ($visits as $v)
                        <div class="border-bottom py-2">
                            <strong>{{ optional($v->created_at)->format('d M Y') }}</strong>
                            <div class="small text-muted">{{ $v->department?->name }}@if($v->doctor) · {{ $v->doctor->name ?? '' }}@endif · {{ ucfirst(str_replace('_', ' ', $v->status)) }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No visits recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h2 class="h5 fw-bold mb-3">My prescriptions</h2>
                    @forelse ($prescriptions as $rx)
                        <div class="border-bottom py-3">
                            <div class="fw-semibold">{{ $rx->prescription_number }}
                                <span class="text-muted small fw-normal">· {{ optional($rx->created_at)->format('d M Y') }}</span></div>
                            <ul class="mb-0 small">
                                @foreach ($rx->items as $item)
                                    <li>{{ $item->medication_name }} {{ $item->dosage }} — {{ $item->frequency }} for {{ $item->duration }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No prescriptions yet.</p>
                    @endforelse
                    <p class="small text-muted mt-3 mb-0">This is a summary. Follow your doctor's and pharmacist's instructions, and call {{ config('hospital.phone') }} if something looks wrong.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
