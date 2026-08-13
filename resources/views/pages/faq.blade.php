@php
    /* پرسش‌ها یک‌بار اینجا تعریف می‌شوند تا هم در HTML و هم در FAQPage
       Schema استفاده شوند و این دو هرگز از هم جدا نیفتند. */
    $faqShipping = getShippingRules();
    $faqs = [
        ['q' => 'آیا قطعات شما اورجینال هستند؟', 'a' => 'بله، تمامی قطعات عرضه‌شده در ناظر یدک دارای ضمانت اصالت هستند. ما قطعات را مستقیماً از شرکت‌های معتبر تولیدکننده مانند ایساکو، سایپا یدک و سایر برندهای اصلی تأمین می‌کنیم و هولوگرام اصالت بر روی بسته‌بندی محصولات قابل مشاهده است.'],
        ['q' => 'چگونه می‌توانم قطعه مناسب خودرو خود را پیدا کنم؟', 'a' => 'شما می‌توانید از طریق جستجوی نام قطعه، کد فنی (OEM) یا مدل خودرو، محصول مورد نظر خود را بیابید. همچنین از طریق دسته‌بندی‌های فروشگاه می‌توانید قطعات مربوط به هر بخش از خودرو را مشاهده کنید. در صورت نیاز، کارشناسان ما از طریق تلفن یا واتساپ آماده راهنمایی هستند.'],
        ['q' => 'مدت‌زمان ارسال سفارش چقدر است؟', 'a' => 'سفارش‌های ثبت‌شده تا ساعت ۱۴، همان روز ارسال می‌شوند. مدت تحویل بسته به شهر مقصد بین ۱ تا ۵ روز کاری متغیر است. برای تهران و شهرهای بزرگ معمولاً ۱ تا ۲ روز کاری و برای سایر شهرستان‌ها ۳ تا ۵ روز کاری زمان لازم است.'],
        ['q' => 'آیا امکان بازگشت کالا وجود دارد؟', 'a' => 'بازگشت کالا فقط در صورت داشتن عیب فنی یا مغایرت با سفارش ثبت‌شده امکان‌پذیر است. برای اطلاعات بیشتر با پشتیبانی تماس بگیرید.'],
        ['q' => 'چه روش‌های پرداختی در دسترس است؟', 'a' => 'پرداخت آنلاین از طریق درگاه بانکی امن، پرداخت در محل (هنگام تحویل) و همچنین کارت به کارت از روش‌های پرداخت موجود هستند. درگاه پرداخت آنلاین ما دارای گواهینامه امنیتی SSL است.'],
        ['q' => 'آیا قطعات گارانتی دارند؟', 'a' => 'قطعات ایساکو و سایر برندهای اصلی دارای گارانتی شرکتی هستند. مدت و شرایط گارانتی بسته به نوع قطعه متفاوت است و در صفحه هر محصول قابل مشاهده است. در صورت بروز مشکل، تیم پشتیبانی ما در کنار شماست.'],
        ['q' => 'هزینه ارسال چقدر است؟', 'a' => 'سفارش‌های بالای ' . shippingAmountWords($faqShipping['local_free_threshold']) . ' در ' . $faqShipping['local_province_name'] . ' و بالای ' . shippingAmountWords($faqShipping['national_free_threshold']) . ' در سایر شهرها رایگان ارسال می‌شوند. در ' . $faqShipping['local_province_name'] . ' برای سفارش‌های کمتر از این مبلغ، ' . toPersianNumbers($faqShipping['local_shipping_cost']) . ' تومان هزینه پیک دریافت می‌شود و در سایر شهرها مرسوله با تیپاکس و پسکرایه از گیرنده ارسال می‌گردد. هزینه نهایی در مرحله ثبت سفارش به‌صورت شفاف نمایش داده می‌شود.'],
    ];
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
