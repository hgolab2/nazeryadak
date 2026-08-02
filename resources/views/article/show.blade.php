@extends('layout.layout', [
    'title' => $info->titr . ' | مجله یدکی'
])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><a href="/blog" class="breadcrumb-custom">مجله یدکی</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">{{ Str::limit($info->titr, 40) }}</span></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <article class="blog-article">
                    <div class="blog-article-header">
                        <h1 class="blog-article-title">{{ $info->titr }}</h1>
                        <div class="blog-article-meta">
                            <span><i class="far fa-calendar-alt"></i> {{ gregorian_to_jalali2($info->createdate) }}</span>
                            @if($info->source)
                            <span><i class="fas fa-link"></i> {{ $info->source }}</span>
                            @endif
                        </div>
                    </div>

                    @if($info->images != null)
                    <div class="blog-article-image">
                        <img src="{{$info->images->getPath()}}" alt="{{ $info->titr }}">
                    </div>
                    @endif

                    @if($info->sutitr)
                    <div class="blog-article-lead">
                        {{ $info->sutitr }}
                    </div>
                    @endif

                    @if(!empty($link))
                    <div class="blog-toc">
                        <div class="blog-toc-header">
                            <i class="fas fa-list-ul"></i> فهرست مطالب
                        </div>
                        <ul class="blog-toc-list">
                            {!! $link !!}
                        </ul>
                    </div>
                    @endif

                    <div class="blog-article-body">
                        {!! $text !!}
                    </div>

                    <div class="blog-article-share">
                        <span class="blog-share-label"><i class="fas fa-share-alt"></i> اشتراک‌گذاری:</span>
                        <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($info->titr) }}" target="_blank" class="blog-share-btn" style="background:#0088cc;">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($info->titr . ' ' . url()->current()) }}" target="_blank" class="blog-share-btn" style="background:#25d366;">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <button onclick="navigator.clipboard.writeText('{{ url()->current() }}');toast.fire({icon:'success',title:'لینک کپی شد'});" class="blog-share-btn" style="background:var(--text-light); border:none;">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </article>
            </div>

            <div class="col-lg-4">
                <div class="blog-sidebar-sticky">
                    @if(!empty($link))
                    <div class="blog-sidebar-box d-none d-lg-block">
                        <div class="blog-sidebar-title"><i class="fas fa-list-ul"></i> فهرست مطالب</div>
                        <ul class="blog-toc-list blog-toc-list-sidebar">
                            {!! $link !!}
                        </ul>
                    </div>
                    @endif

                    <div class="blog-sidebar-box">
                        <div class="blog-sidebar-title"><i class="fas fa-fire"></i> مطالب پیشنهادی</div>
                        @foreach ($model as $article)
                        <a href="{{$article->getUrl()}}" class="blog-sidebar-item">
                            <div class="blog-sidebar-item-img">
                                @if($article->images != null && $article->image > 0)
                                <img src="{{$article->images->getPath()}}" alt="{{ $article->titr }}">
                                @else
                                <img src="/assets/images/noimage.jpg" alt="{{ $article->titr }}">
                                @endif
                            </div>
                            <div class="blog-sidebar-item-content">
                                <span class="blog-sidebar-item-title">{{ $article->titr }}</span>
                                <span class="blog-sidebar-item-date"><i class="far fa-calendar-alt"></i> {{toPersianDate($article->showdate, true, false)}}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>

                    <div class="blog-sidebar-cta">
                        <i class="fas fa-headset"></i>
                        <h6>مشاوره رایگان</h6>
                        <p>نیاز به راهنمایی در انتخاب قطعه دارید؟</p>
                        <a href="tel:09127471631" class="blog-sidebar-cta-btn">
                            <i class="fas fa-phone-alt me-1"></i> ۰۹۱۲۷۴۷۱۶۳۱
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
