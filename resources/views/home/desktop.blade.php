@extends('layout.LayoutDesktop', [
    'title' => l('پایگاه اطلاع رسانی استاد حسین انصاریان'),
    'canonical' => env('APP_URL') . '/' . $lang,
    'sidebar' => false,
    'type' => 'home',
    'lang' => $lang
])
@section('head')
<link rel="stylesheet" href="{{env('DOMAIN')}}/home/css/page-1-index.css">
@endsection

@section('header')
@endsection

@section('main_content')


<div class="row columns">
    <div class="news_box ">

        <div class="row">
            <div class="large-6 columns ">
                <div class="box_silder ">
                    <div class="main_article_slider owl-carousel" id="main_article_slider">
                        @foreach($advertisements as $adv)
                        @if(isset($adv->media))
                        <a href="{{$adv->link}}" class="item">
                            <img src="{{$adv->media->getPath()}}" alt="{{$adv->title}}">
                            <div class="caption">{{$adv->title}}</div>
                        </a>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="large-6 columns content">
                <div class="row columns">
                    <div class="title1_wrapper">
                        <div class="title_type1 mb-20">
                            <img src="{{env('DOMAIN')}}/images/icons/img-05.png" alt="" />
                            <span>{{$title1}}</span>
                        </div>
                        <a href="/{{$lang}}/article/lists/{{$id1}}" class="other_link mb-20">{{l('همه مطالب')}}</a>
                    </div>
                </div>
                @foreach ($listArticle1 as $ne)
                <!-- start item -->
                <a class="news_item" href="{{$ne->getUrl()}}">
                    {{$ne->titr}}
                </a>
                <!-- // end item -->
                @endforeach

            </div>

        </div>
    </div>
</div>
<!-- // end section10 اخبار و گزارش ها -->



<!-- start section7  کلیپ های صوتی و تصویری -->
<div class="row columns">
    <div class="multimedia_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1 mb-20">
                    <img src="{{env('DOMAIN')}}/images/icons/img-06.png" alt="" />
                    <span>{{l('کلیپ های صوتی و تصویری')}}</span>
                </div>
                <a href="{{env('APP_URL')}}/{{$lang}}/sound/clip" class="other_link mb-20">{{l('همه کلیپ ها')}}</a>
            </div>
        </div>
        <div class="content">
            <div class="slider_multimedia owl-carousel" id="slider_multimedia">
                @foreach ($clips as $clip)
                <!-- start item -->
                <a href="{{'/'.$lang.'/sound/download/' . $clip->sound_files_id }}" class="item video">
                    <div class="image">
                        <img src="{{$clip->image ? $clip->image->getPath() : env('DOMAIN').'/images/NoImage.png'}}" alt="{{$clip->name}}">
                    </div>
                    <div class="title">{{$clip->name}}</div>
                    <div class="des">
                        <div class="date">{{($clip->size ? round($clip->size/1024, 2).'MB' : '')}}</div>
                    </div>
                </a>
                <!-- // end item -->
                @endforeach

            </div>
        </div>
    </div>
</div>
<!-- // end section7 کلیپ های صوتی و تصویری -->





<!-- start section8  بانک مقالات -->
<div class="row columns">
    <div class="article_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1 mb-20">
                    <img src="{{env('DOMAIN')}}/images/icons/img-11.png" alt="" />
                    <span>{{$title2}}</span>
                </div>
                <a href="/{{$lang}}/article/lists/{{$id2}}" class="other_link mb-20">{{l('همه مطالب')}}</a>
            </div>
        </div>
        <div class="content">
            <div class="slider_article owl-carousel" id="slider_article">
                @foreach ($listArticle2 as $article)
                @if(isset($article->images))
                <!-- start item -->
                <a href="{{$article->getUrl()}}" class="item">
                    <div class="image">
                        <img title="{{$article->titr}}" src="{{$article->images->getPath()}}">
                    </div>
                    <div class="title">{{$article->titr}}</div>
                </a>
                <!-- // end item -->
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- // end section8 بانک مقالات -->




<!-- start section14 پرسش و پاسخ -->
<div class="row columns">
    <div class="article_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1 mb-20">
                    <img src="{{env('DOMAIN')}}/images/icons/img-08.png" alt="" />
                    <span>{{$title3}}</span>
                </div>
                <a href="/{{$lang}}/article/lists/{{$id3}}" class="other_link mb-20">{{l('همه مطالب')}}</a>
            </div>
        </div>
        <div class="content">
            <div class="slider_article owl-carousel" id="slider_questions">
                @foreach ($listArticle3 as $article)
                @if(isset($article->images))
                <a href="{{$article->getUrl()}}" class="item">
                    <div class="image">
                        <img title="{{$article->titr}}" src="{{isset($article->images) ? $article->images->getPath() : env('DOMAIN').'/images/NoImage.png'}}">
                    </div>
                    <div class="title">{{$article->titr}}</div>

                </a>
                @endif
                @endforeach
            </div>
        </div>


    </div>
</div>
<!-- // end section14 پرسش و پاسخ -->
@if(isset($title4))
<!-- start section15 پر بازدید ترین مطالب -->
<div class="columns row">
    <div class="article_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1 mb-20">
                    <img src="{{env('DOMAIN')}}/images/icons/img-07.png" alt="" />
                    <span>{{$title4}}</span>
                </div>
                <a href="/{{$lang}}/article/lists/{{$id4}}" class="other_link mb-20">{{l('همه مطالب')}}</a>
            </div>
        </div>
        <div class="content">
            <div class="slider_article owl-carousel" id="Most_visited_content">
                @foreach ($listArticle4 as $article)
                @if(isset($article->images))
                <a href="{{$article->getUrl()}}" class="item">
                    <div class="image">
                        <img title="{{$article->titr}}" src="{{isset($article->images) ? $article->images->getPath() : env('DOMAIN').'/images/NoImage.png'}}">
                    </div>
                    <div class="title">{{$article->titr}}</div>

                </a>
                @endif
                @endforeach

            </div>
        </div>
    </div>
</div>
@endif
<!-- // end section15 پر بازدید ترین مطالب -->

<style>
    #main_article_slider .item {
        position: relative; /* برای موقعیت‌دهی مطلق دکمه‌ها */
        overflow: hidden;   /* جلوگیری از خروج عناصر از محدوده عکس */
    }

    #main_article_slider .item img {
        width: 100%;
        height: 400px; /* ارتفاع دلخواه */
        object-fit: cover;
    }

    /* کپشن روی عکس */
    #main_article_slider .caption {
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        padding: 10px;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }

    /* تنظیم موقعیت دکمه‌های ناوبری */
    #main_article_slider .owl-nav {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        transform: translateY(-50%); /* مرکز کردن عمودی */
        display: flex;
        justify-content: space-between; /* قرارگیری دکمه‌ها در دو طرف */
        padding: 0 15px; /* فاصله از لبه‌ها */
        pointer-events: none; /* اجازه کلیک فقط روی خود دکمه‌ها */
    }

    /* استایل دکمه‌های قبلی و بعدی */
    #main_article_slider .owl-nav button {
        background: rgba(0, 0, 0, 0.5);
        color: #fff;
        border: none;
        padding: 10px 15px !important;
        font-size: 20px;
        cursor: pointer;
        pointer-events: all; /* فعال کردن کلیک روی دکمه‌ها */
        transition: background 0.3s ease;
    }

    /* افکت هاور روی دکمه‌ها */
    #main_article_slider .owl-nav button:hover {
        background: rgba(0, 0, 0, 0.8);
    }

    #main_article_slider .item img {
        height: 320px; /* ارتفاع دلخواه */
        object-fit: cover; /* برای برش مناسب تصویر */
        width: 100%;
    }
</style>
@endsection
@section('js')
<script>

    jQuery(document).ready(function ($) {

        $("#main_article_slider").owlCarousel({
            rtl: true,
            loop: true,
            nav: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 7000,
            autoplayHoverPause: true,
            items: 1,
            autoHeight: false // چون ارتفاع ثابت کردیم، نیازی به فعال کردن این گزینه نیست
        });

    });

    // پیشنهاد سر دبیر
    $("#editors_suggestion_slider").owlCarousel({
        stagePadding: 10,
        items: 4,
        rtl: true,
        loop: true,
        nav: true,
        dots: false,
        margin: 10,
    });

    // جدیدترین سخنرانی ها
    $("#slider_speeches").owlCarousel({
        stagePadding: 1,
        items: 4,
        rtl: true,
        loop: true,
        margin: 12,
        nav: true,
        dots: false,

    });

    // slider multimedia
    $("#slider_multimedia").owlCarousel({
        stagePadding: 1,
        items: 4,
        rtl: true,
        loop: true,
        margin: 12,
        nav: true,
        dots: false,
    });


    //پر بازدید ترین مطالب روز
    $("#Most_visited_content").owlCarousel({
        stagePadding: 1,
        items: 4,
        rtl: true,
        loop: true,
        margin: 10,
        nav: true,
        dots: false,
    });

    // بانک مقالات
    $("#slider_article").owlCarousel({
        items: 4,
        stagePadding: 1,
        rtl: true,
        loop: true,
        margin: 12,
        nav: true,
        dots: false,
    });

    // slider_questions
    $("#slider_questions").owlCarousel({
        stagePadding: 1,
        items: 4,
        rtl: true,
        loop: true,
        margin: 12,
        nav: true,
        dots: false,
    });



</script>
@endsection
