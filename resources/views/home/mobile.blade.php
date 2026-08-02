@extends('layout.LayoutMobile', [
    'title' => l('پایگاه اطلاع رسانی استاد حسین انصاریان'),
    'canonical' => env('APP_URL') . '/' . $lang,
    'sidebar' => false,
    'type' => 'home',
    'lang' => $lang
])
@section('head')
<link rel="stylesheet" href="{{env('DOMAIN')}}/home/css/page-1.css">
<!-- other -->

<!-- استایل های پلیر -->
@endsection
@section('main_content')
<div class="row columns bg_dark" style="background: #eaeaea">
    <div class="social_bar" style="background: #eaeaea">
        <ul class="social">
            <li>
                <a title="{{l('تلگرام')}}" href="https://telegram.me/ansarian_ir" target="_blank" rel="noopener noreferrer" ><img src="{{env('DOMAIN')}}/images/social-icons/social-01.png" alt="تلگرام" /></a>
            </li>
            <li>
                <a title="{{l('اینستاگرام اخلاق و عرفان')}}" href="https://www.instagram.com/ansarian_ir" rel="noopener noreferrer" target="_blank"><img src="{{env('DOMAIN')}}/images/social-icons/social-02.png" alt="اینستاگرام اخلاق و عرفان" /></a>
            </li>
            <li>
                <a title="{{l('آپارات')}}" href="http://aparat.com/ansarian" target="_blank" rel="noopener noreferrer" ><img src="{{env('DOMAIN')}}/images/social-icons/social-03.png" alt="آپارات" /></a>
            </li>
            <li>
                <a title="{{l('بله')}}" href="https://ble.ir/ansarian_ir" target="_blank" rel="noopener noreferrer"><img src="{{env('DOMAIN')}}/images/social-icons/social-04.png" alt="بله" /></a>
            </li>
            <li>
                <a title="{{l('ایتا')}}" href="https://eitaa.com/ansarian_ir"  target="_blank" rel="noopener noreferrer"><img src="{{env('DOMAIN')}}/images/social-icons/social-05.png" alt="ایتا" /></a>
            </li>
            <li>
                <a title="{{l('سروش')}}" href="http://sapp.ir/ansarian_ir" target="_blank" rel="noopener noreferrer"><img src="{{env('DOMAIN')}}/images/social-icons/social-06.png" alt="سروش" /></a>
            </li>
        </ul>
    </div>
</div>
<div class="page_content" style="padding-top: 0px">
    @if(count($advertisements) > 0)
    <!-- page1 slider -->
    <div class="slide_show1">
        <div class="owl-carousel" id="slide_show1">
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
    @endif
    <!-- // end page1 slider -->
    <!-- articles -->
    @if(count($listArticle1) > 0)
    <div class="news_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1">
                    <img src="{{env('DOMAIN')}}/images/icons/img-03.png" alt="" />
                    <span>{{$title1}}</span>
                </div>
                <a href="/{{$lang}}/article/lists/{{$id1}}" class="other_link">{{l('همه مطالب')}}</a>
            </div>
        </div>
        <div class="row columns">
            <div class="content">
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
    @endif
    <!-- // end articles -->
    <!-- articles -->
    @if(count($listArticle2) > 0)
    <div class="slider_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1">
                    <img src="{{env('DOMAIN')}}/images/icons/img-06.png" alt="" />
                    <span>{{$title2}}</span>
                </div>
                <a href="/{{$lang}}/article/lists/{{$id2}}" class="other_link">{{l('همه مطالب')}}</a>
            </div>
        </div>
        <div class="content">
            <div class="slider_article owl-carousel" id="slider">
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
    <!-- // end articles -->
    @endif
    @if(count($clips) > 0)
    <!-- multimedia -->
    <div class="multimedia_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1">
                    <img src="{{env('DOMAIN')}}/images/icons/img-02.png" alt="" />
                    <span>{{l('کلیپ های صوتی و تصویری')}}</span>
                </div>
                <a href="{{env('APP_URL')}}/{{$lang}}/sound/clip" class="other_link">{{l('همه کلیپ ها')}}</a>
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
    <!-- // end multimedia -->
    @endif
    @if(count($listArticle3) > 0)
    <!-- articles -->
    <div class="slider_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1">
                    <img src="{{env('DOMAIN')}}/images/icons/img-08.png" alt="" />
                    <span>{{$title3}}</span>
                </div>
                <a href="/{{$lang}}/article/lists/{{$id3}}" class="other_link">{{l('همه مطالب')}}</a>
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
    <!-- // end articles -->
    @endif

    @if(isset($title4) && count($listArticle4) > 0)
    <!-- articles -->
    <div class="slider_box">
        <div class="row columns">
            <div class="title1_wrapper">
                <div class="title_type1">
                    <img src="{{env('DOMAIN')}}/images/icons/img-07.png" alt="" />
                    <span>{{$title4}}</span>
                </div>
                <a href="/{{$lang}}/article/lists/{{$id4}}" class="other_link">{{l('همه مطالب')}}</a>

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
    <!-- // end articles -->
    @endif
</div>
<style>
    #slide_show1 .item {
        position: relative; /* برای موقعیت‌دهی مطلق دکمه‌ها */
        overflow: hidden;   /* جلوگیری از خروج عناصر از محدوده عکس */
    }



    /* کپشن روی عکس */
    #slide_show1 .caption {
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.3);
        color: #fff;
        padding: 10px;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }

    /* تنظیم موقعیت دکمه‌های ناوبری */
    #slide_show1 .owl-nav {
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
    #slide_show1 .owl-nav button {
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
    #slide_show1 .owl-nav button:hover {
        background: rgba(0, 0, 0, 0.8);
    }


</style>
@endsection
@section('js')
<script>
    // اسلایدر اول صفحه
    $('#slide_show1').owlCarousel({
        stagePadding: 20,
        items: 1,
        rtl: true,
        loop: true,
        margin: 8,
        nav: false,
        dots: true,
        center: true,
        smartSpeed: 500,
        autoplay: true,
        autoplayTimeout: 7000,
        autoplayHoverPause: true,

    });

    // پیشنهاد سردبیر
    $("#site_best_articles").owlCarousel({
        stagePadding: 15,
        items: 1,
        rtl: true,
        loop: true,
        margin: 15,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 2,
            }
        }
    });

    $("#slider_speeches").owlCarousel({
        items: 2,
        rtl: true,
        loop: true,
        margin: 10,
        nav: false,
        dots: false,
        stagePadding: 5,
        responsiveClass: true,
        responsive: {
            641: {
                items: 3,
            }
        }
    });

    // most_visited_prayers
    $("#most_visited_prayers").owlCarousel({
        items: 2,
        rtl: true,
        loop: true,
        margin: 10,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 3,
            }
        }
    });
    // slider multimedia
    $("#slider_multimedia").owlCarousel({
        items: 2,
        rtl: true,
        loop: true,
        margin: 10,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 3,
            }
        }
    });
    $("#slider").owlCarousel({
        items: 2,
        rtl: true,
        loop: true,
        margin: 10,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 3,
            }
        }
    });
    // اسلایدر در محضر استاد
    $("#slider_master_article").owlCarousel({
        stagePadding: 15,
        items: 1,
        rtl: true,
        loop: true,
        margin: 15,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 2,
            }
        }
    });
    // اسلایدر سمت خدا
    $("#slider_towards_God").owlCarousel({
        stagePadding: 15,
        items: 1,
        rtl: true,
        loop: true,
        margin: 15,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 2,
            }
        }
    });
    // slider_questions
    $("#slider_questions").owlCarousel({
        items: 2,
        rtl: true,
        loop: true,
        margin: 10,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 3,
            }
        }
    });
    // Most_visited_content
    $("#Most_visited_content").owlCarousel({
        items: 2,
        rtl: true,
        loop: true,
        margin: 10,
        nav: false,
        dots: false,
        responsiveClass: true,
        responsive: {
            641: {
                items: 3,
            }
        }
    });



</script>
@endsection
