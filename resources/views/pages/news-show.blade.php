@extends('layouts.public')

@section('title', $article->title)
@section('meta_description', $article->summary)

@section('content')

    <x-page-header
        :title="$article->title"
        :subtitle="$article->category"
        :breadcrumbs="[['label' => 'News & Announcements', 'url' => route('news')], ['label' => 'Article']]"
    />

    <section class="py-5 bg-white">
        <div class="container" style="max-width: 820px;">
            @if (!empty($isPreview))
                <div class="alert alert-warning border-0 shadow-sm" role="status">
                    <i class="bi bi-eye me-1"></i>
                    <strong>Staff preview.</strong>
                    Status: {{ $article->isPublished() ? 'Published' : 'Draft (not visible to the public)' }}.
                    <a href="{{ route('admin.articles.edit', $article) }}" class="alert-link">Back to editor</a>
                </div>
            @endif

            <article>
                @if ($article->coverUrl())
                    <img src="{{ $article->coverUrl() }}" alt="{{ $article->title }}" class="img-fluid rounded-4 mb-4 w-100" style="max-height: 420px; object-fit: cover;" onerror="this.onerror=null; this.style.display='none';">
                @endif
                <p class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>{{ optional($article->published_at)->format('F j, Y') ?? 'Not yet published' }}
                    &middot; <i class="bi bi-person me-1"></i>{{ $article->author_name }}
                </p>
                <p class="lead">{{ $article->summary }}</p>
                {{-- Body is plain text: escaped, with blank lines turned into paragraphs. No raw HTML is ever rendered. --}}
                @foreach (preg_split('/\R{2,}/', trim($article->body)) as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </article>

            @if ($related->isNotEmpty())
                <hr class="my-5">
                <h2 class="h5 fw-bold mb-3">More news</h2>
                <ul class="list-unstyled">
                    @foreach ($related as $item)
                        <li class="mb-2"><a href="{{ route('news.show', $item->slug) }}">{{ $item->title }}</a>
                            <small class="text-muted">— {{ $item->published_at->format('M j, Y') }}</small></li>
                    @endforeach
                </ul>
            @endif

            <a href="{{ route('news') }}" class="btn btn-outline-primary mt-3">&larr; All news</a>
        </div>
    </section>

@endsection
