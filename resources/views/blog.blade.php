@extends('layout.layout', [
    'title' => 'مجله یدکی | ناظر یدک',
    'metaDescription' => 'مقالات و راهنماهای تخصصی لوازم یدکی و تعمیر خودرو در مجله ناظر یدک.',
    'canonical' => seo_url('/blog'),
])
@section('main_content')
    <main><!-- start main -->
        <div class="container">



            <div class="row mt-3"><!-- start title -->
                <div class="col-12 d-flex align-items-center">
                    <h1 class="blog-title-text">جدیدترین  مقالات </h1>
                    <div class="blog-title-line"></div>
                    <a href="blog-category.html" class="blog-title-btn">همه مقالات <i class="fa fa-arrow-left align-middle"></i></a>
                </div>
            </div><!-- end title -->

            <div class="row mt-3"><!-- start new blog posts -->
                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-1.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">آموزش آشپزی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">طرز تهیه رولت کرپ با کرم فندق بسیار آسان و بدون نیاز به فر</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->

                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-2.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">سبک زندگی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">بررسی خردکن سیلور کرست مدل SLMA مناسب کارهای دم‌دستی</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->

                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-3.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">تکنولوژی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">۱۰ اشتباه که کاربران تازه‌ وارد گوشی‌های اندرویدی مرتکب می‌شوند</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->

                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-4.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">تکنولوژی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">راهنمای خرید بهترین گوشی سامسونگ سری A؛ گزینه‌های ایده‌آل خرید</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->
                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-1.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">آموزش آشپزی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">طرز تهیه رولت کرپ با کرم فندق بسیار آسان و بدون نیاز به فر</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->

                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-2.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">سبک زندگی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">بررسی خردکن سیلور کرست مدل SLMA مناسب کارهای دم‌دستی</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->

                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-3.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">تکنولوژی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">۱۰ اشتباه که کاربران تازه‌ وارد گوشی‌های اندرویدی مرتکب می‌شوند</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->

                <div class="col-lg-3 col-sm-6 mb-3"><!-- start item -->
                    <div class="card border-0 custom-blog-card">
                        <div class="sub-layer">
                            <img src="assets/images/mag-4.jpg" class="img-fluid">
                            <div class="over-layer">
                                <a href="blog-category.html" class="image-badge">تکنولوژی</a>
                                <span class="image-like">(4)<i class="far fa-heart ms-1"></i></span>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <a href="blog-detail.html">راهنمای خرید بهترین گوشی سامسونگ سری A؛ گزینه‌های ایده‌آل خرید</a>
                            <p>
                                لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم
                                از صنعت چاپ و با استفاده از طراحان گرافیک است.
                                    چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است.
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <img src="assets/images/person.jpg" class="person-pic">
                            <span class="writer">سارا رضایی</span>
                            <div class="float-end mt-2">
                                <span class="time"><i class="fa fa-clock font-13 me-1"></i>2 ساعت قبل</span>
                            </div>
                        </div>
                    </div>
                </div><!-- end item -->
                <ul class="custom-pagination mt-4"><!-- start pagination -->
                    <li><a href="#"><i class="fa fa-angle-right align-middle"></i></a></li>
                    <li class="active"><a href="#">1</a></li>
                    <li><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#">4</a></li>
                    <li><a href="#"><i class="fa fa-angle-left align-middle"></i></a></li>
                </ul><!-- end pagination -->

            </div><!-- end new blog posts -->



        </div>
    </main><!-- end main -->

    @endsection
