@extends('layout.managmentLayout', [
    'title' => 'مدیریت بلاگ',
    'menu' => 'article/list',
])

@section('main_content')
<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">مدیریت بلاگ</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">مدیریت بلاگ</h1>
    <a href="/admin/article/create" class="btn btn-primary">
        <i class="fas fa-plus ms-1"></i> مطلب جدید
    </a>
</div>

<div class="admin-card">
    <form id="mySearch">
        <input type="hidden" name="order" id="order" value="articleid">
        <input type="hidden" name="orderby" id="orderby" value="desc">
        <div class="row align-items-end">
            <div class="col-md-5 mb-3">
                <label class="form-label fw-bold">عنوان</label>
                <input type="text" name="title" id="title" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">وضعیت</label>
                <select name="status" id="status" class="form-control">
                    <option value="">همه</option>
                    <option value="visible">منتشر شده</option>
                    <option value="hidden">مخفی</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-bold">تعداد</label>
                <select id="showcount" name="showcount" class="form-control">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <button id="form_search" class="btn btn-primary w-100">جستجو</button>
            </div>
        </div>
    </form>
</div>

<div class="mt-4">
    <div class="overflow-auto my-4 rounded" id="article-wrapper"></div>
    <nav class="border-top pb-md-4 pt-4 mt-2" id="pagination"></nav>
</div>
@endsection

@section('js')
<script src="/js/paging.js"></script>
<script>
var CSRF_TOKEN = '{{ csrf_token() }}';
var pagin = 1;
var str = '';

$('#mySearch').on('submit', function(e) {
    e.preventDefault();
    str = '';
    CheckSend();
});

$('#form_search').on('click', function(e) {
    e.preventDefault();
    str = '';
    CheckSend();
});

function destroy(id) {
    if (!confirm('آیا از حذف این مطلب مطمئن هستید؟')) return;
    $.ajax({
        url: '/admin/article/' + id,
        type: 'DELETE',
        data: {_token: CSRF_TOKEN},
        dataType: 'json'
    }).done(function() {
        CheckSend();
    }).fail(function() {
        alert('حذف با مشکل مواجه شد.');
    });
}

function toggleStatus(id) {
    $.get('/admin/article/status/' + id).done(function() {
        CheckSend();
    }).fail(function() {
        alert('تغییر وضعیت با مشکل مواجه شد.');
    });
}

function CheckSend() {
    str = '';
    if ($('#title').val()) str += 'title=' + encodeURIComponent($('#title').val()) + '&';
    if ($('#status').val()) str += 'status=' + encodeURIComponent($('#status').val()) + '&';
    str += 'order=' + $('#order').val() + '&';
    str += 'orderby=' + $('#orderby').val() + '&';
    str += 'showcount=' + $('#showcount').val() + '&';
    loadMoreData(1, str);
}

function loadMoreData(page, query) {
    if (page == 1) $('#article-wrapper').empty();
    $.ajax({
        url: '?page=' + page + '&' + query,
        type: 'get'
    }).done(function(data) {
        $('#article-wrapper').html(data.html);
        if (data.totalCount > parseInt($('#showcount').val())) {
            $('#pagination').html(Paging(pagin, $('#showcount').val(), data.totalCount, 'myClass', 'myDisableClass'));
        } else {
            $('#pagination').html('');
        }
    });
}

$('#pagination').on('click', 'a', function() {
    pagin = $(this).attr('pn');
    if (pagin > 0) loadMoreData(pagin, str);
});

CheckSend();
</script>
@endsection
