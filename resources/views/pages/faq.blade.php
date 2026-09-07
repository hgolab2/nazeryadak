@php
    /* پرسش‌ها در seo_site_faqs() تعریف شده‌اند تا HTML صفحه، اسکیمای
       FAQPage و llms-full.txt همیشه یک متن واحد را نشان دهند. */
    $faqs = seo_site_faqs();
    $faqTitle = 'پرسش‌های متداول درباره خرید لوازم یدکی | ناظر یدک';
    $faqDescription = 'پاسخ کارشناسان ناظر یدک به پرتکرارترین پرسش‌ها درباره اصالت قطعات، پیدا کردن قطعه با کد فنی، زمان و هزینه ارسال، گارانتی و شرایط بازگشت کالا.';
@endphp
@extends('layout.layout', [
    'title' => $faqTitle,
    'metaDescription' => $faqDescription,
    'keywords' => 'سوالات متداول لوازم یدکی, اصالت قطعات ایساکو, گارانتی قطعات خودرو, هزینه ارسال لوازم یدکی, بازگشت کالا',
    'canonical' => seo_url('/faq'),
    'schema' => [
        {{-- نوع WebPage است نه FAQPage؛ خودِ seo_faq_schema موجودیت FAQPage
             را می‌سازد و دو FAQPage در یک صفحه، داده‌ی ساختاریافته را مبهم می‌کند. --}}
        seo_webpage_schema($faqTitle, $faqDescription, seo_url('/faq')),
        seo_faq_schema($faqs),
        seo_breadcrumb_schema([
            ['name' => 'ناظر یدک', 'url' => seo_url()],
            ['name' => 'پرسش‌های متداول', 'url' => null],
        ]),
    ],
])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">پرسش‌های متداول</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content p-4">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size:1.1rem;">پرسش‌های متداول</h1>
                    </div>

                    <div class="accordion" id="faqAccordion">
                        @foreach($faqs as $i => $faq)
                        <div class="accordion-item mb-2" style="border:1px solid var(--border-color); border-radius:var(--radius-sm);">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }} font-13" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq{{$i}}"
                                    style="font-weight:600; border-radius:var(--radius-sm);">
                                    {{ $faq['q'] }}
                                </button>
                            </h2>
                            <div id="faq{{$i}}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                                <div class="accordion-body font-13" style="line-height:2.2; color:var(--text-gray);">
                                    {{ $faq['a'] }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="text-center mt-4 p-3" style="background:var(--primary-lighter); border-radius:var(--radius);">
                        <p class="font-13 mb-2">سوال شما در لیست نبود؟</p>
                        <a href="/contact-us" class="btn btn-info">تماس با پشتیبانی</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
