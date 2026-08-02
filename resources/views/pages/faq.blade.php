@extends('layout.layout', ['title' => 'پرسش‌های متداول | ناظر یدک'])
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
                        @php
                        $faqs = [
                            ['q' => 'آیا قطعات شما اورجینال هستند؟', 'a' => 'بله، تمامی قطعات عرضه‌شده در ناظر یدک دارای ضمانت اصالت هستند. ما قطعات را مستقیماً از شرکت‌های معتبر تولیدکننده مانند ایساکو، سایپا یدک و سایر برندهای اصلی تأمین می‌کنیم و هولوگرام اصالت بر روی بسته‌بندی محصولات قابل مشاهده است.'],
                            ['q' => 'چگونه می‌توانم قطعه مناسب خودرو خود را پیدا کنم؟', 'a' => 'شما می‌توانید از طریق جستجوی نام قطعه، کد فنی (OEM) یا مدل خودرو، محصول مورد نظر خود را بیابید. همچنین از طریق دسته‌بندی‌های فروشگاه می‌توانید قطعات مربوط به هر بخش از خودرو را مشاهده کنید. در صورت نیاز، کارشناسان ما از طریق تلفن یا واتساپ آماده راهنمایی هستند.'],
                            ['q' => 'مدت‌زمان ارسال سفارش چقدر است؟', 'a' => 'سفارش‌های ثبت‌شده تا ساعت ۱۴، همان روز ارسال می‌شوند. مدت تحویل بسته به شهر مقصد بین ۱ تا ۵ روز کاری متغیر است. برای تهران و شهرهای بزرگ معمولاً ۱ تا ۲ روز کاری و برای سایر شهرستان‌ها ۳ تا ۵ روز کاری زمان لازم است.'],
                            ['q' => 'آیا امکان بازگشت کالا وجود دارد؟', 'a' => 'بازگشت کالا فقط در صورت داشتن عیب فنی یا مغایرت با سفارش ثبت‌شده امکان‌پذیر است. برای اطلاعات بیشتر با پشتیبانی تماس بگیرید.'],
                            ['q' => 'چه روش‌های پرداختی در دسترس است؟', 'a' => 'پرداخت آنلاین از طریق درگاه بانکی امن، پرداخت در محل (هنگام تحویل) و همچنین کارت به کارت از روش‌های پرداخت موجود هستند. درگاه پرداخت آنلاین ما دارای گواهینامه امنیتی SSL است.'],
                            ['q' => 'آیا قطعات گارانتی دارند؟', 'a' => 'قطعات ایساکو و سایر برندهای اصلی دارای گارانتی شرکتی هستند. مدت و شرایط گارانتی بسته به نوع قطعه متفاوت است و در صفحه هر محصول قابل مشاهده است. در صورت بروز مشکل، تیم پشتیبانی ما در کنار شماست.'],
                            ['q' => 'هزینه ارسال چقدر است؟', 'a' => 'هزینه ارسال بر اساس وزن مرسوله و شهر مقصد محاسبه می‌شود. در مرحله نهایی ثبت سفارش، هزینه ارسال به‌صورت شفاف نمایش داده می‌شود. برای سفارش‌های بالای مبلغ مشخص، ارسال رایگان خواهد بود.'],
                        ];
                        @endphp

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
