@extends('layouts.public')

@section('title', 'Hospital News & Announcements')
@section('meta_description', 'News and announcements published by ' . config('hospital.name', 'MediCare Hospital') . '.')

@section('content')

    <x-page-header
        title="News & Health Advisories"
        subtitle="Official updates and clinical announcements from the hospital"
        :breadcrumbs="[['label' => 'News & Health Advisories']]"
    />

    <section class="py-5 light-background">
        <div class="container">
            @if ($articles->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-newspaper fs-1 text-secondary d-block mb-3"></i>
                    <h2 class="h4 fw-bold">No news has been published yet</h2>
                    <p class="text-muted">Please check back soon, or <a href="{{ route('contact') }}">contact us</a> if you need information now.</p>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($articles as $article)
                        <div class="col-lg-4 col-md-6">
                            <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                                @if ($article->coverUrl())
                                    <img src="{{ $article->coverUrl() }}" alt="" class="w-100" style="height: 200px; object-fit: cover;" loading="lazy">
                                @endif
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                        <span><i class="bi bi-calendar3 me-1 text-primary"></i>{{ $article->published_at->format('F j, Y') }}</span>
                                        <span class="badge bg-light text-primary border">{{ $article->category }}</span>
                                    </div>
                                    <h2 class="h5 fw-bold mb-2">
                                        <a href="{{ route('news.show', $article->slug) }}" class="text-decoration-none text-dark">{{ $article->title }}</a>
                                    </h2>
                                    <p class="text-muted small mb-3">{{ $article->summary }}</p>
                                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="bi bi-person me-1"></i>{{ $article->author_name }}</small>
                                        <a href="{{ route('news.show', $article->slug) }}" class="text-primary small fw-bold text-decoration-none">Read more &rarr;</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $articles->links() }}</div>
            @endif
        </div>
    </section>

@endsection
