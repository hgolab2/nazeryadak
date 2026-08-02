@foreach ($model as $article)
<div class="col-lg-3 col-md-4 col-sm-6 mb-4">
    <div class="blog-card-new">
        <a href="{{$article->getUrl()}}" class="blog-card-img-wrapper">
            @if($article->images != null && $article->image > 0)
                <img src="{{$article->images->getPath()}}" alt="{{ $article->titr }}">
            @else
                <img src="/assets/images/noimage.jpg" alt="{{ $article->titr }}">
            @endif
        </a>
        <div class="card-body">
            <a href="{{$article->getUrl()}}">{{ $article->titr }}</a>
            @if($article->sutitr)
            <p class="blog-card-excerpt">{{ Str::limit($article->sutitr, 80) }}</p>
            @endif
            <div class="blog-card-meta">
                <span><i class="far fa-calendar-alt"></i> {{toPersianDate($article->showdate, true, false)}}</span>
            </div>
        </div>
    </div>
</div>
@endforeach
