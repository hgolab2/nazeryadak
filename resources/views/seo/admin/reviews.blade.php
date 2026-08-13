@extends('layout.managmentLayout', [
    'title' => 'نظرات محصولات',
    'menu' => 'seo/reviews',
])

@section('main_content')
<style>
.review-stars{color:#f5a623;font-size:.8rem}
.review-comment{max-width:520px;font-size:.85rem;line-height:1.9}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">نظرات محصولات</li>
    </ol>
</nav>

<h1 class="h4 mb-3">نظرات محصولات</h1>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="alert alert-light border">
    نظر تأییدشده هم روی صفحه‌ی محصول دیده می‌شود و هم وارد <code>aggregateRating</code> می‌شود —
    همان چیزی که ستاره‌ی نتیجه‌ی گوگل را فعال می‌کند. میانگین امتیاز محصول با هر تأیید یا حذف، خودکار به‌روز می‌شود.
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    @foreach(\App\Models\ProductReview::STATUSES as $key => $label)
        <a href="/admin/seo/reviews?status={{ $key }}"
           class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }} <span class="badge bg-light text-dark">{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
    <a href="/admin/seo/reviews?status=all" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">همه</a>
</div>

<section class="card card-body shadow-sm p-4">
    @if($model->count())
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr><th>محصول</th><th>نویسنده</th><th>امتیاز</th><th>نظر</th><th>تاریخ</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($model as $review)
                <tr>
                    <td style="max-width:180px">
                        @if($review->product)
                            <a href="{{ $review->product->url() }}#reviews" target="_blank" style="font-size:.85rem">{{ $review->product->title }}</a>
                        @else
                            <span class="text-muted">محصول حذف شده</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        {{ $review->name }}
                        @if($review->is_buyer)<span class="badge bg-success">خریدار</span>@endif
                    </td>
                    <td class="text-nowrap review-stars">
                        @for($i = 1; $i <= 5; $i++)<i class="fa{{ $i <= $review->rating ? 's' : 'r' }} fa-star"></i>@endfor
                    </td>
                    <td class="review-comment">
                        @if($review->title)<b>{{ $review->title }}</b><br>@endif
                        {{ $review->comment }}
                    </td>
                    <td class="text-nowrap" style="font-size:12px">{{ $review->created_at?->diffForHumans() }}</td>
                    <td class="text-nowrap">
                        @if($review->status !== \App\Models\ProductReview::STATUS_APPROVED)
                        <form method="POST" action="/admin/seo/reviews/{{ $review->id }}/status" class="d-inline">
                            @csrf @method('put')
                            <input type="hidden" name="status" value="approved">
                            <button class="btn btn-sm btn-success" title="تأیید"><i class="fas fa-check"></i></button>
                        </form>
                        @endif
                        @if($review->status !== \App\Models\ProductReview::STATUS_REJECTED)
                        <form method="POST" action="/admin/seo/reviews/{{ $review->id }}/status" class="d-inline">
                            @csrf @method('put')
                            <input type="hidden" name="status" value="rejected">
                            <button class="btn btn-sm btn-outline-warning" title="رد"><i class="fas fa-ban"></i></button>
                        </form>
                        @endif
                        <form method="POST" action="/admin/seo/reviews/{{ $review->id }}" class="d-inline" onsubmit="return confirm('این نظر حذف شود؟')">
                            @csrf @method('delete')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $model->links() }}</div>
    @else
        <p class="text-muted mb-0">نظری در این وضعیت وجود ندارد.</p>
    @endif
</section>
@endsection
