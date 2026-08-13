@foreach ($model as $article)
<div class="col-lg-3 col-md-4 col-sm-6 mb-4">
    <div class="blog-card-new blog-card-text">
        <div class="card-body">
            <span class="blog-card-tag"><i class="fas fa-newspaper"></i> مجله یدکی</span>
            <a href="{{$article->getUrl()}}">{{ $article->titr }}</a>
            @if($article->sutitr)
            <p class="blog-card-excerpt">{{ Str::limit($article->sutitr, 130) }}</p>
            @endif
            <div class="blog-card-meta">
                <span><i class="far fa-calendar-alt"></i> {{toPersianDate($article->showdate, true, false)}}</span>
                <a href="{{$article->getUrl()}}" class="blog-card-more">ادامه مطلب <i class="fas fa-chevron-left"></i></a>
            </div>
        </div>
    </div>
</div>
@endforeach
