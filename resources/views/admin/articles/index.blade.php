@extends('layouts.admin')

@section('title', 'News & Announcements')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">News &amp; Announcements</h1>
            <p class="text-muted small mb-0">Write articles as drafts, preview them, then publish to the public News page.</p>
        </div>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New article</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="status">{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5"><input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search by title" aria-label="Search by title"></div>
        <div class="col-md-3">
            <select name="status" class="form-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="draft" @selected($status === 'draft')>Drafts</option>
                <option value="published" @selected($status === 'published')>Published</option>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
    </form>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                    <tr><th class="ps-4">Title</th><th>Category</th><th>Status</th><th>Published</th><th class="pe-4 text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($articles as $a)
                    <tr>
                        <td class="ps-4"><div class="fw-bold">{{ $a->title }}</div><div class="small text-muted">By {{ $a->author_name }}</div></td>
                        <td>{{ $a->category }}</td>
                        <td>
                            @if ($a->isPublished())
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Draft</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $a->published_at?->format('M j, Y H:i') ?? '—' }}</td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('admin.articles.edit', $a) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <a href="{{ route('admin.articles.preview', $a) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Preview</a>
                                @if ($canPublish)
                                    <form method="POST" action="{{ route($a->isPublished() ? 'admin.articles.unpublish' : 'admin.articles.publish', $a) }}">
                                        @csrf
                                        <button class="btn btn-sm {{ $a->isPublished() ? 'btn-outline-warning' : 'btn-success text-white' }}">
                                            {{ $a->isPublished() ? 'Unpublish' : 'Publish' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No articles yet. Click “New article” to write the first one.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($articles->hasPages())<div class="card-footer bg-white">{{ $articles->links() }}</div>@endif
    </div>
</div>
@endsection
