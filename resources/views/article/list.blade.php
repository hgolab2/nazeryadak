@extends('layout.layout', [
    'title' => "مجله یدکی | ناظر یدک"
])
@section('main_content')
<main>
    <div class="blog-hero">
        <div class="container">
            <div class="text-center">
                <h1 class="blog-hero-title">مجله یدکی</h1>
                <p class="blog-hero-subtitle">آخرین مقالات و راهنماهای تخصصی دنیای خودرو و لوازم یدکی</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">مجله یدکی</span></li>
                </ul>
            </div>
        </div>

        <div class="row" id="content-wrapper">
            @include('article.list_type', compact('model'))
        </div>

        <div class="d-flex justify-content-center my-4">
            <ul class="pagination mb-20" aria-label="Pagination" id="pagination"></ul>
        </div>
    </div>
</main>
@endsection
@section('js')
<script src="/js/paging.js"></script>
<script>
    var pagin = 1;
    var str="";
    function createQuery()
    {
        var str = "";
        return str;
    }
    function CheckSend()
    {
        str = createQuery();
        loadMoreData(1,str)
    }
    function loadMoreData(page,type)
    {
        $('.page-loading').addClass('active');
        type1=type;
        if(page==1){
            $(".tab-content1").html("");
        }
        $.ajax({
                url: `/blog?ajaxi=1&page=${page}&${type}`,
                type: "get",
                beforeSend: function() {
                }
            })
            .done(function(data) {
                $('.page-loading').removeClass('active');
                if (data.length == 0) {
                    return;
                }
                $("#content-wrapper").html(data.html);
                var result = Paging(pagin ,20,data.totalCount, "myClass", "myDisableClass");
                $("#pagination").html(result);
                var target = $(this.hash);
                target = target.length ? target : $('[name=content]');
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top-70
                    }, 1000);
                }
            })
    }

    $(document).ready(function() {
        type1 = "";
        var result = Paging(1 ,20,{{$totalCount}}, "myClass", "myDisableClass");
        $("#pagination").html(result);
        $("#pagination").on("click", "a", function () {
            pagin=$(this).attr("pn");
            if(pagin>0){
                str = createQuery();
                loadMoreData($(this).attr("pn"),str);
            }
        });
    });
</script>
@endsection
