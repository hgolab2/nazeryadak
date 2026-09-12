@extends('layout.managmentLayout', [
'title' => l('مدیریت سفارشات'),
])
@section('main_content')
<style>
    /* ── چیدمان لیست سفارشات ───────────────────────────────────────
       جدول در دسکتاپ فشرده‌تر شد و در موبایل به کارت تبدیل می‌شود؛
       نسخه‌ی قبلی در موبایل فقط اسکرول افقی داشت و ستون‌های راست
       (کد و مشتری) از دید خارج می‌شدند. */
    #orders-page .filter-card .form-label {
        font-size: .8rem;
        margin-bottom: .25rem;
    }
    #orders-page .filter-card .form-control {
        padding: .35rem .6rem;
        font-size: .875rem;
    }
    #orders-page .orders-toolbar {
        background: #f6f7f9;
        border: 1px solid #e6e8ec;
        border-radius: .5rem;
        padding: .45rem .75rem;
        font-size: .85rem;
    }
    #orders-page .orders-table-wrap {
        border: 1px solid #e6e8ec;
        border-radius: .5rem;
        overflow-x: auto;
    }
    #orders-page .orders-table {
        font-size: .875rem;
    }
    #orders-page .orders-table thead th {
        background: #eef1f6;
        border-bottom: 1px solid #dfe3ea;
        font-weight: 700;
        font-size: .8rem;
        white-space: nowrap;
        padding: .55rem .5rem;
    }
    #orders-page .orders-table tbody td {
        padding: .5rem;
        border-bottom: 1px solid #f0f1f4;
        vertical-align: middle;
    }
    #orders-page .orders-table tbody tr:last-child td {
        border-bottom: 0;
    }
    #orders-page .orders-table tbody tr:hover {
        background: #fafbfc;
    }
    #orders-page .orders-table .cell-sub {
        display: block;
        font-size: .72rem;
        line-height: 1.5;
    }
    #orders-page .orders-table .col-check  { width: 38px; }
    #orders-page .orders-table .col-id     { width: 70px; }
    #orders-page .orders-table .col-qty    { width: 70px; }
    #orders-page .orders-table .col-date   { width: 110px; white-space: nowrap; }
    #orders-page .orders-table .col-tools  { width: 56px; }
    #orders-page .orders-table .col-status { width: 170px; }

    /* موبایل: هر ردیف یک کارت با برچسبِ ستون کنار مقدار */
    @media (max-width: 767.98px) {
        #orders-page .orders-table-wrap {
            border: 0;
            border-radius: 0;
            overflow-x: visible;
        }
        #orders-page .orders-table,
        #orders-page .orders-table tbody,
        #orders-page .orders-table tr,
        #orders-page .orders-table td {
            display: block;
            width: 100%;
        }
        #orders-page .orders-table thead {
            display: none;
        }
        #orders-page .orders-table tbody tr {
            position: relative;
            border: 1px solid #e6e8ec;
            border-radius: .5rem;
            margin-bottom: .6rem;
            padding: .35rem .6rem;
            background: #fff;
        }
        #orders-page .orders-table tbody tr:hover {
            background: #fff;
        }
        #orders-page .orders-table tbody td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            text-align: start !important;
            padding: .4rem 0;
            border-bottom: 1px dashed #f0f1f4;
        }
        #orders-page .orders-table tbody td::before {
            content: attr(data-label);
            flex: 0 0 auto;
            color: #6c757d;
            font-size: .75rem;
            font-weight: 700;
        }
        #orders-page .orders-table tbody td.col-check {
            position: absolute;
            top: .5rem;
            inset-inline-end: .6rem;
            width: auto;
            padding: 0;
            border: 0;
        }
        #orders-page .orders-table tbody td.col-check::before {
            content: none;
        }
        #orders-page .orders-table tbody td.col-id {
            padding-inline-end: 1.75rem;
        }
        #orders-page .orders-table tbody tr td:last-child {
            border-bottom: 0;
        }
        #orders-page .orders-table tbody tr.row-empty,
        #orders-page .orders-table tbody tr.row-empty td {
            border: 0;
            display: block;
        }
        #orders-page .orders-table tbody tr.row-empty td::before {
            content: none;
        }
    }
</style>

<div id="orders-page">

<!-- Breadcrumb-->
<nav class="mb-2 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/">{{l('خانه')}}</a></li>
        <li class="breadcrumb-item active" aria-current="page"> {{l('مدیریت سفارشات')}}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h1 class="h4 mb-0">
        {{l('مدیریت سفارشات')}}
    </h1>
    <a href="/admin/order/create" class="btn btn-primary btn-sm">
        <i class="fa fa-plus me-1"></i>{{l('افزودن')}}
    </a>
</div>

<div class="card shadow-sm filter-card">
    <form id="mySearch">
    <input type="hidden" name="order" id="order" value="id">
    <input type="hidden" name="orderby" id="orderby" value="desc">
    {{-- فیلترها و دکمه‌ی جستجو در یک ردیف؛ پیش‌تر دکمه بلوک جداگانه‌ی
         وسط‌چین بود و یک صفحه‌ی کامل تا خود جدول فاصله می‌افتاد --}}
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-lg-2">
                <label class="form-label fw-bold" for="order_id">{{ l('کد سفارش') }}</label>
                <input type="text" name="order_id" id="order_id" class="form-control">
            </div>

            <div class="col-6 col-lg-3">
                <label class="form-label fw-bold" for="phone">{{ l('موبایل مشتری') }}</label>
                <input type="text" name="phone" id="phone" class="form-control">
            </div>

            <div class="col-6 col-lg-3">
                <label class="form-label fw-bold" for="status">{{ l('وضعیت سفارش') }}</label>
                {{-- فهرست از مدل می‌آید؛ نسخه‌ی دستیِ قبلی «مرجوع شده» و
                     «پرداخت ناموفق» را نداشت و آن سفارش‌ها قابل فیلتر نبودند --}}
                <select name="status" id="status" class="form-control">
                    <option value="">{{ l('همه') }}</option>
                    @foreach(\App\Models\Order::STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label fw-bold" for="showcount">{{l('تعداد نمایش')}}</label>
                <select id="showcount" name="showcount" class="form-control">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <div class="col-12 col-lg-2 d-flex gap-2">
                <button id="form_search" class="btn btn-primary flex-grow-1">
                    <i class="fa fa-search me-1"></i>{{l('جستجو')}}
                </button>
                <button type="button" id="form_reset" class="btn btn-outline-secondary"
                        title="{{ l('حذف فیلترها') }}">
                    <i class="fa fa-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>
    </form>
</div>

<div class="mt-3">
    <div id="user-wrapper"></div>
    <!-- Pagination-->
    <nav class="border-top pb-md-4 pt-3 mt-3" aria-label="Pagination" id="pagination">
    </nav>
</div>

</div>

@endsection
@section('js')

<script src="/js/paging.js"></script>
<link rel="stylesheet" href="/assets/css/sweetalert.css" />
<script src="/assets/js/sweetalert.min.js"></script>
<script src="/js/sweetalert2.all.js"></script>
<script>
    $(document).ready(function(){
        $('#mySearch').on('submit', function(e){
            e.preventDefault();
            return false;
        });
    });
    var CSRF_TOKEN = '{{ csrf_token() }}';

    /* ── برچسب پستی ─────────────────────────────────────────────────
       جدول با ajax جایگزین می‌شود، پس رویدادها باید delegate باشند و
       printSelectedLabels باید سراسری بماند (از onclick داخل جدول صدا زده می‌شود). */
    $(document).on('change', '#label-check-all', function () {
        $('.label-check').prop('checked', this.checked);
        updateLabelCount();
    });

    $(document).on('change', '.label-check', function () {
        var all = $('.label-check').length;
        $('#label-check-all').prop('checked', all > 0 && $('.label-check:checked').length === all);
        updateLabelCount();
    });

    function updateLabelCount() {
        $('#label-selected-count').text($('.label-check:checked').length);
    }

    function printSelectedLabels() {
        var ids = $('.label-check:checked').map(function () {
            return this.value;
        }).get();

        if (!ids.length) {
            toast({type: 'error', title: '{{ l('ابتدا سفارش‌های موردنظر را تیک بزنید.') }}'});
            return;
        }

        window.open('/admin/order/labels?ids=' + ids.join(','), '_blank');
    }

    function destroy(id) {
        swal({
            text: " {{l('آیا از حذف سفارش مورد نظر اطمینان دارید؟')}}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: 'لغو',
            confirmButtonText: 'بله',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return new Promise(function (resolve) {
                    $.ajax({
                                url: '/admin/order/' + id,
                                type: 'DELETE',
                                data: {_token: CSRF_TOKEN},
                                dataType: 'json'
                            })
                            .done(function (response) {
                                swal({
                                    text: '{{l('گزینه مورد نظر با موفقیت حذف شد.')}}',
                                    type: 'success',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    CheckSend();
                            });
                            })
                            .fail(function () {
                                swal('{{l('خطا!')}}', '{{l('حذف با مشکل مواجه شد!')}}', 'error');
                            });
                });
            },
            allowOutsideClick: false
        });
    }

    const toast = swal.mixin({
        toast: true,
        position: 'bottom-left',
        showConfirmButton: false,
        timer: 2500
    });

    var pagin = 1;
    var str = "";
    // فیلتر وضعیت از آدرس (لینک‌های داشبورد: /admin/order/list?status=paid)
    var urlStatus = new URLSearchParams(window.location.search).get('status');
    if (urlStatus) { $('#status').val(urlStatus); }
    CheckSend();
    $("#form_search").click(function() {
        str = "";
        CheckSend();
    });

    // پاک‌کردن فیلترها؛ پیش‌تر باید هر کادر دستی خالی می‌شد
    $("#form_reset").click(function() {
        $("#order_id, #phone").val("");
        $("#status").val("");
        $("#showcount").val("10");
        str = "";
        CheckSend();
    });
    function CheckSend() {
        var array = [];
        if ($("#order_id").val())
            str += "order_id=" + $("#order_id").val() + "&";

        if ($("#phone").val())
            str += "phone=" + $("#phone").val() + "&";

        if ($("#status").val())
            str += "status=" + $("#status").val() + "&";

        str+= "order="+$("#order").val()+"&";
        str+= "orderby="+$("#orderby").val()+"&";
        str+= "showcount="+$("#showcount").val()+"&";

        loadMoreData(1, str);
    };
    function loadMoreData(page, type2) {
        $('.page-loading').addClass('active');
        if (page == 1) {
            $("#user-wrapper").empty();
        }
        $.ajax({
                url: `?page=${page}&&${type2}`,
                type: "get",
                beforeSend: function() {
                    $("#spiner").removeClass("d-none");
                }
            }).done(function(data) {
                if (data.totalCount < $("#showcount").val())
                    hasPage = false;
                else
                    hasPage = data.hasPage;
                $("#spiner").addClass("d-none");
                if (data.length == 0) {
                    return;
                }
                var htmlpage = data.html;
                $("#user-wrapper").html(htmlpage);
                if(data.totalCount>parseInt($("#showcount").val()))
                {
                    var result = Paging(pagin, $("#showcount").val(), data.totalCount, "myClass", "myDisableClass");
                    $("#pagination").html(result);
                }
                else
                {
                    $("#pagination").html("");
                }
                pageflag = true;
                $('.page-loading').removeClass('active');
            })
            .fail(function(jqXHR, ajaxOptions, thrownError) {
                $("#spiner").addClass("d-none");
                $('.page-loading').removeClass('active');
            });
    };
    $("#pagination").on("click", "a", function() {
        pagin = $(this).attr("pn");
        if(pagin>0)
        {
            window.scrollTo(0, 250);
            loadMoreData($(this).attr("pn"), str);
        }
    });
    </script>
@endsection
