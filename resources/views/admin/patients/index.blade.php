@extends('layouts.admin')

@section('title', 'Patient Master Index')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Patient Master Index (PMI)</h1>
            <p class="text-muted small mb-0">Centralized hospital patient registry, Medical Record Numbers (MRN), and demographic records.</p>
        </div>
        @php
            $canRegister = Auth::user() && Auth::user()->hasRole(['super_admin', 'receptionist']);
        @endphp
        @if($canRegister)
            <div>
                <a href="{{ route('admin.patients.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-person-plus-fill"></i> Register New Patient
                </a>
            </div>
        @endif
    </div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Total Registered Patients</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Active Patient Profiles</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['active']) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card">
                <div class="text-muted small fw-medium">Registered Today</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($stats['registered_today']) }}</div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.patients.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by MRN, Name, Phone, National ID..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <select name="gender" class="form-select">
                        <option value="">All Genders</option>
                        <option value="male" {{ ($filters['gender'] ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ ($filters['gender'] ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ ($filters['gender'] ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="archived" {{ ($filters['status'] ?? '') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Patients Directory Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>MRN</th>
                        <th>Patient Name</th>
                        <th>Age / Sex</th>
                        <th>Contact Phone</th>
                        <th>National ID</th>
                        <th>Blood Group</th>
                        <th>Registered</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td>
                                <code class="fw-bold text-primary">{{ $patient->mrn }}</code>
                            </td>
                            <td>
                                <a href="{{ route('admin.patients.show', $patient->id) }}" class="fw-semibold text-dark text-decoration-none hover-primary">
                                    {{ $patient->full_name }}
                                </a>
                                @if($patient->email)
                                    <div class="text-muted small">{{ $patient->email }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $patient->age ? $patient->age . ' yrs' : 'N/A' }} / {{ ucfirst($patient->gender) }}
                                </span>
                            </td>
                            <td>
                                <code>{{ $patient->phone }}</code>
                            </td>
                            <td class="text-muted small">
                                {{ $patient->national_id ?? '—' }}
                            </td>
                            <td>
                                @if($patient->blood_group)
                                    <span class="badge bg-danger-subtle text-danger">{{ $patient->blood_group }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $patient->created_at ? $patient->created_at->format('Y-m-d') : '—' }}
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.patients.show', $patient->id) }}" class="btn btn-outline-primary" title="View Patient Profile">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if($canRegister)
                                        <a href="{{ route('admin.patients.edit', $patient->id) }}" class="btn btn-outline-secondary" title="Edit Demographics">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-person-vcard fs-2 d-block mb-2 text-secondary"></i>
                                No patient records found matching your search.
                                @if($canRegister)
                                    <div class="mt-2">
                                        <a href="{{ route('admin.patients.create') }}" class="btn btn-sm btn-primary">Register New Patient</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($patients->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $patients->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
