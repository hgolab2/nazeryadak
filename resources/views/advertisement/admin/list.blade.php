@extends('layout.managmentLayout', [
'title' => l('مدیریت بنر'),
])
@section('main_content')
<style>
    .table:not(.table-dark) thead:not(.thead-dark) th, .table:not(.table-dark) tbody th {
        min-width: 100px;
    }
</style>

<!-- Breadcrumb-->
<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">{{l('خانه')}}</a></li>
        <li class="breadcrumb-item active" aria-current="page"> {{l('مدیریت بنر')}}</li>
    </ol>
</nav>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h2 mb-0">
        {{l('مدیریت بنر')}}
    </h1>
    <div>
        <a href="/admin/advertisement/create">
            <span class="btn btn-lg btnAdd btn-primary w-100 mb-2">
                {{l('افزودن')}}
            </span>
        </a>
    </div>
</div>
<div class="card shadow-sm">
    <form  id="mySearch">
    <input type="hidden" name="order" id="order" value="advertisementid">
    <input type="hidden" name="orderby" id="orderby" value="desc">
    <div class=" card-body border-0  pb-1 me-lg-1">
        <div class="row">
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="title" id="label_title" class="form-label fw-bold">عبارت</label>
                <input type="text" name="title" id="title" value="" class="form-control">
            </div>
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="stDateFrom" id="label_stDateFrom" class="form-label fw-bold">تاریخ شروع از</label>
                <input type="text" name="stDateFrom" id="stDateFrom" value="" class="form-control">
            </div>
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="endDateFrom" id="label_endDateFrom" class="form-label fw-bold">تاریخ پایان از</label>
                <input type="text" name="endDateFrom" id="endDateFrom" value="" class="form-control">
            </div>

            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="stDateTo" id="label_stDateTo" class="form-label fw-bold">تاریخ شروع تا</label>
                <input type="text" name="stDateTo" id="stDateTo" value="" class="form-control">
            </div>
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="endDateTo" id="label_endDateTo" class="form-label fw-bold">تاریخ پایان تا</label>
                <input type="text" name="endDateTo" id="endDateTo" value="" class="form-control">
            </div>
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="priority" id="label_priority" class="form-label fw-bold">رتبه</label>
                <select name="priority" id="priority" class="form-control">
                    <option value="0">همه موارد</option>
                    <option value="1">0</option>
                    <option value="2">1</option>
                    <option value="3">2</option>
                    <option value="4">3</option>
                    <option value="5">4</option>
                    <option value="6">5</option>
                    <option value="7">6</option>
                    <option value="8">7</option>
                    <option value="9">8</option>
                    <option value="10">9</option>
                    <option value="11">10</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="position" id="label_position" class="form-label fw-bold">مکان</label>
                <select name="position" id="position" class="form-control">
                    <option value="">همه موارد</option>
                    <option value="2">تبلیغات</option>
                    <option value="3">منوی تصاویر</option>
                    <option value="4">بنر بالای مطالب</option>
                    <option value="5">اسلایدر فروشگاه</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label for="display" id="label_display" class="form-label fw-bold">وضعیت نمایش</label>
                <select name="display" id="display" class="form-control">
                    <option value="0">همه موارد</option>
                    <option value="1">آشکار</option>
                    <option value="2">پنهان</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3 col-sm-12 mt-3">
                <label class="form-label fw-bold">{{l('تعداد نمایش')}}</label>
                <select id="showcount" name="showcount" class="form-control " style="width: 100%;" >
                    <option value="10" class="green">10</option>
                    <option value="20" class="orange">20</option>
                    <option value="50" class="orange">50</option>
                    <option value="100" class="orange">100</option>
                </select>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-center my-3">
        <button id="form_search" class="btn btn-primary">
            {{l('جستجو')}}
        </button>
    </div>
    </form>
</div>
<div class="mt-4">
    <div class="overflow-auto  my-4 rounded" id="user-wrapper">
    </div>
    <!-- Pagination-->
    <nav class="border-top pb-md-4 pt-4 mt-2" aria-label="Pagination" id="pagination">
    </nav>
</div>

@endsection
@section('js')
<script src="/vendor/select2/select2.min.js"></script>
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
    function destroy(id) {
        swal({
            text: " {{l('آیا از حذف بنر مورد نظر اطمینان دارید؟')}}",
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
                                url: '/admin/advertisement/' + id,
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

    function display(id) {
        $.get("/admin/advertisement/display/" + id , function (data, status) {
            if (status == 'success') {
                CheckSend()
                toast({
                    type: 'success',
                    title: data.result
                });
            } else {
                toast({
                    type: 'error',
                    title: '{{l('عملیات با مشکل مواجه شد!')}}'
                });
            }
        });
    }
    function first(id) {
        $.get("/admin/advertisement/first/" + id , function (data, status) {
            if (status == 'success') {
                CheckSend()
                toast({
                    type: 'success',
                    title: data.result
                });
            } else {
                toast({
                    type: 'error',
                    title: '{{l('عملیات با مشکل مواجه شد!')}}'
                });
            }
        });
    }
    const toast = swal.mixin({
        toast: true,
        position: 'bottom-left',
        showConfirmButton: false,
        timer: 2500
    });
    $(".select2").select2();
    var pagin = 1;
    var str = "";
    CheckSend();
    $("#form_search").click(function() {
        str = "";
        CheckSend();
    });
    function CheckSend() {
        var array = [];
        if ($("#title").val() != undefined && $("#title").val().length > 0)
            str += "title=" + $("#title").val() + "&&";

        if ($("#display").val().length > 0)
            str += "display=" + $("#display").val() + "&&";
        if ($("#position").val().length > 0)
            str += "position=" + $("#position").val() + "&&";
        if ($("#stDateFrom").val() != undefined && $("#stDateFrom").val().length > 0)
            str += "stDateFrom=" + $("#stDateFrom").val() + "&&";
        if ($("#stDateTo").val() != undefined && $("#stDateTo").val().length > 0)
            str += "stDateTo=" + $("#stDateTo").val() + "&&";
        if ($("#endDateFrom").val() != undefined && $("#endDateFrom").val().length > 0)
            str += "endDateFrom=" + $("#endDateFrom").val() + "&&";
        if ($("#endDateTo").val() != undefined && $("#endDateTo").val().length > 0)
            str += "endDateTo=" + $("#endDateTo").val() + "&&";
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


    <link href="/vendor/date_picker/kamadatepicker.css" rel="stylesheet">
    <script src="/vendor/date_picker/kamadatepicker.js"></script>
    <script>
        var customOptions = {
            placeholder: "{{l('روز / ماه / سال')}}"
            , twodigit: false
            , closeAfterSelect: true
            , nextButtonIcon: "fa fa-angle-right"
            , previousButtonIcon: "fa fa-angle-left"
            , buttonsColor: "#37b5b5"
            , forceFarsiDigits: true
            , markToday: true
            , markHolidays: true
            , highlightSelectedDay: true
            , sync: true
            , gotoToday: true
        };
        kamaDatepicker('stDateFrom', customOptions);
        kamaDatepicker('stDateTo', customOptions);
        kamaDatepicker('endDateFrom', customOptions);
        kamaDatepicker('endDateTo', customOptions);
    </script>

@endsection
