@extends('layouts.admin')

@section('title', $article->exists ? 'Edit article' : 'New article')

@section('content')
<div class="container-fluid p-0" style="max-width: 900px;">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.articles.index') }}" class="btn btn-sm btn-outline-secondary">&larr; All articles</a>
        <h1 class="h3 fw-bold mb-0">{{ $article->exists ? 'Edit article' : 'New article' }}</h1>
        @if ($article->exists)
            <span class="badge {{ $article->isPublished() ? 'bg-success' : 'bg-secondary' }}">{{ $article->isPublished() ? 'Published' : 'Draft' }}</span>
        @endif
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
          class="card border-0 shadow-sm rounded-4 p-4">
        @csrf
        @if ($article->exists) @method('PUT') @endif

        <div class="mb-3">
            <label for="title" class="form-label fw-bold">Title <span class="text-danger">*</span></label>
            <input id="title" name="title" class="form-control" maxlength="180" value="{{ old('title', $article->title) }}" required>
            @if ($article->exists)<div class="form-text">Web address: /news/{{ $article->slug }}{{ $article->isPublished() ? ' (fixed once published)' : '' }}</div>@endif
        </div>
        <div class="mb-3">
            <label for="summary" class="form-label fw-bold">Short summary <span class="text-danger">*</span></label>
            <textarea id="summary" name="summary" rows="2" maxlength="400" class="form-control" required>{{ old('summary', $article->summary) }}</textarea>
        </div>
        <div class="mb-3">
            <label for="body" class="form-label fw-bold">Article text <span class="text-danger">*</span></label>
            <textarea id="body" name="body" rows="12" class="form-control" required>{{ old('body', $article->body) }}</textarea>
            <div class="form-text">Plain text. Leave a blank line between paragraphs. HTML is not interpreted.</div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label for="category" class="form-label fw-bold">Category</label>
                <select id="category" name="category" class="form-select">
                    @foreach ($categories as $c)<option @selected(old('category', $article->category) === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="author_name" class="form-label fw-bold">Author / department <span class="text-danger">*</span></label>
                <input id="author_name" name="author_name" class="form-control" maxlength="120" value="{{ old('author_name', $article->author_name ?: auth()->user()->name) }}" required>
            </div>
            @if (($canPublish ?? true))
            <div class="col-md-4">
                <label for="published_at" class="form-label fw-bold">Publication date</label>
                <input id="published_at" type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}">
                <div class="form-text">Optional. Defaults to the moment of publishing; a future date delays visibility.</div>
            </div>
            @endif
        </div>
        <div class="mb-4">
            <label for="cover_image" class="form-label fw-bold">Cover image (optional)</label>
            @if ($article->coverUrl())
                <div class="mb-2"><img src="{{ $article->coverUrl() }}" alt="Current cover" style="max-height:120px" class="rounded">
                    <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="remove_cover"><label class="form-check-label" for="remove_cover">Remove current image</label></div></div>
            @endif
            <input id="cover_image" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="form-control">
            <div class="form-text">JPG, PNG or WebP, up to 2 MB.</div>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-between">
            <div>
                @if ($article->exists)
                    <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Preview</a>
                @endif
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save {{ $article->isPublished() ? 'changes' : 'draft' }}</button>
            </div>
        </div>
    </form>

    @if ($article->exists && ($canPublish ?? false))
        <form method="POST" action="{{ route($article->isPublished() ? 'admin.articles.unpublish' : 'admin.articles.publish', $article) }}" class="mt-3">
            @csrf
            <button class="btn {{ $article->isPublished() ? 'btn-outline-warning' : 'btn-success text-white' }}"
                    onclick="return confirm('{{ $article->isPublished() ? 'Hide this article from the public website?' : 'Publish this article on the public website now?' }}')">
                {{ $article->isPublished() ? 'Unpublish' : 'Publish to website' }}
            </button>
        </form>
    @endif
</div>
@endsection
