@props([
    'title' => 'Page Title',
    'subtitle' => null,
    'breadcrumbs' => []
])

<div class="page-title" data-aos="fade">
    <div class="heading">
        <div class="container">
            <div class="row d-flex justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="mb-2">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mb-0 text-muted">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <nav class="breadcrumbs mt-3">
        <div class="container">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bi bi-house-door me-1"></i>Home</a></li>
                @foreach ($breadcrumbs as $item)
                    @if (!empty($item['url']))
                        <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                    @endif
                @endforeach
            </ol>
        </div>
    </nav>
</div>
