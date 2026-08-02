<div class="card-nav">
        @if(access(33, false) || access(34, false) || access(100, false))
        <a class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2" data-bs-toggle="collapse" href="#collapseExample5" role="button" aria-expanded="{{(isset($menu) && isset($menu) && $menu == 'article')?'true':'false'}}" aria-controls="collapseExample5">
            <i class="fa fa-file-text opacity-60 me-2 "></i>
            <span class="ms-1 d-sm-inline">{{l('مدیریت مطالب')}}</span>
            <i class="fi-chevron-down opacity-60  me-auto"></i>
        </a>
        <div class="collapse {{(isset($menu) && $menu == 'article/create' || isset($menu) && $menu == 'article/list'  || isset($menu) && $menu == 'article/listCategory')?'show':''}}" id="collapseExample5">
            <div class="card card-body shadow-none mb-3 px-3 py-1">
                <ul class=" nav flex-column ms-1">
                    @if(access(33, false))
                    <li class="w-100">
                        <a href="{{env('DOMAIN')}}/admin/article/list"
                            class="nav-link px-0 {{isset($menu) && $menu == 'article/list' ?'active':''}}">
                            <i class="fa fa-file-text  opacity-60 me-2"></i>
                            لیست مطالب
                        </a>
                    </li>
                    @endif
                    @if(access(34, false))
                    <li class="w-100">
                        <a href="{{env('DOMAIN')}}/admin/article/create"
                            class="nav-link px-0 {{isset($menu) && $menu == 'article/create' ?'active':''}}">
                            <i class="fa fa-file  opacity-60 me-2"></i>
                            مطلب جدید
                        </a>
                    </li>
                    @endif

                </ul>
            </div>
        </div>
        @endif

        @if(session('langid') == 1)

        @if(access(89, false))
        <a class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2" data-bs-toggle="collapse" href="#collapseExample4" role="button" aria-expanded="{{(isset($menu) && $menu == 'gallery')?'true':'false'}}" aria-controls="collapseExample4">
            <i class="fa fa fa-image opacity-60 me-2 "></i>
            <span class="ms-1 d-sm-inline">{{l('مدیریت تصاویر')}}</span>
            <i class="fi-chevron-down opacity-60  me-auto"></i>
        </a>
        <div class="collapse {{(isset($menu) && $menu == 'gallery/list' || isset($menu) && $menu == 'gallery')?'show':''}}" id="collapseExample4">
            <div class="card card-body shadow-none mb-3 px-3 py-1">
                <ul class=" nav flex-column ms-1">
                    <li class="w-100">
                        <a href="/admin/gallery/list" class="nav-link px-0 {{isset($menu) && $menu == 'gallery/list' ?'active':''}}">
                            <i class="fa fa-image  opacity-60 me-2"></i>
                            لیست تصاویر
                        </a>
                    </li>
                    @if(access(23, false))
                    <li class="w-100">
                        <a href="{{env('DOMAIN')}}/admin/gallery/list/10537"
                            class="nav-link px-0 {{isset($menu) && $menu == 'article/listCategory' ?'active':''}}">
                            <i class="fa fa-file  opacity-60 me-2"></i>
                            مدیریت مجموعه ها
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
        @endif
        @endif

        @if(access(29, false) || access(83, false))
        <a class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2" data-bs-toggle="collapse" href="#collapseExample7" role="button" aria-expanded="{{(isset($menu) && $menu == 'role' || isset($menu) && $menu == 'user')?'true':'false'}}" aria-controls="collapseExample7">
            <i class="fa fa fa-group opacity-60 me-2 "></i>
            <span class="ms-1 d-sm-inline">{{l('مدیریت سیستم')}}</span>
            <i class="fi-chevron-down opacity-60  me-auto"></i>
        </a>
        <div class="collapse {{(isset($menu) && ($menu == 'user/list' || $menu == 'sms/list'))?'show':''}}" id="collapseExample7">
            <div class="card card-body shadow-none mb-3 px-3 py-1">
                <ul class=" nav flex-column ms-1">
                    @if(access(83, false))
                    <li class="w-100">
                        <a href="/admin/user/list"
                            class="nav-link px-0 {{isset($menu) && $menu == 'user/list' ?'active':''}}">
                            <i class="fa fa-users  opacity-60 me-2"></i>
                            اپراتورهای پایگاه
                        </a>
                    </li>
                    @endif
                    @if(access(29, false))
                    <!--li class="w-100">
                        <a href="{{env('DOMAIN')}}/role/admin/list"
                            class="nav-link px-0 {{isset($menu) && $menu == 'admin/list' && isset($menu) && $menu == 'role' ?'active':''}}">
                            <i class="fa fa-file  opacity-60 me-2"></i>
                            رول بندی
                        </a>
                    </li-->
                    @endif
                    @if(access(83, false))
                    <li class="w-100">
                        <a href="/admin/sms"
                            class="nav-link px-0 {{isset($menu) && $menu == 'sms/list' ?'active':''}}">
                            <i class="fa fa-users  opacity-60 me-2"></i>
                            مدیریت پیامکها
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
        @endif
        @if(access(68, false) || access(13, false) || access(23, false))
        <a class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2" data-bs-toggle="collapse" href="#collapseExample8" role="button" aria-expanded="{{(isset($menu) && $menu == 'cat')?'true':'false'}}" aria-controls="collapseExample8">
            <i class="fa fa fa-group opacity-60 me-2 "></i>
            <span class="ms-1 d-sm-inline">{{l('مدیریت مجموعه ها و تگها')}}</span>
            <i class="fi-chevron-down opacity-60  me-auto"></i>
        </a>
        <div class="collapse {{(isset($menu) && $menu == 'cat')?'show':''}}" id="collapseExample8">
            <div class="card card-body shadow-none mb-3 px-3 py-1">
                <ul class=" nav flex-column ms-1">
                    @if(access(23, false))
                   <li class="w-100">
                        <a href="{{env('DOMAIN')}}/admin/article/listCategory/1"
                            class="nav-link px-0 {{isset($menu) && $menu == 'article/listCategory' ?'active':''}}">
                            <i class="fa fa-file  opacity-60 me-2"></i>
                            مدیریت مجموعه ها
                        </a>
                    </li>
                    @endif

                </ul>
            </div>
        </div>
        @endif


        
        @if(access(121, false))

        <a href="{{env('DOMAIN')}}//backend/admin/setting"
            class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2   {{isset($menu) && $menu == 'backend' ?'active':''}}">
            <i class="nav-icon fa fa-edit"></i>
            تنظیمات
        </a>

        @endif
        @if(access(127, false) || access(95, false) || access(77, false) )
        <a class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2" href="/admin/advertisement/list" >
            <i class="fa fa fa-file-image opacity-60 me-2 "></i>
            <span class="ms-1 d-sm-inline">{{l('مدیریت تبلیغات و بنر')}}</span>
        </a>
        @endif

        @if(access(388, false))
        <a class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2" data-bs-toggle="collapse" href="#collapseExample17" role="button" aria-expanded="{{(isset($menu) && $menu == 'role' || isset($menu) && $menu == 'user')?'true':'false'}}" aria-controls="collapseExample17">
            <i class="fa fa fa-group opacity-60 me-2 "></i>
            <span class="ms-1 d-sm-inline">{{l('مدیریت فروشگاه')}}</span>
            <i class="fi-chevron-down opacity-60  me-auto"></i>
        </a>
        <div class="collapse {{(isset($menu) && ($menu == 'product/list' || $menu == 'order/list' || $menu == 'customer/list' || $menu == 'product/create' || $menu == 'order/create' || $menu == 'customer/create'))?'show':''}}" id="collapseExample17">
            <div class="card card-body shadow-none mb-3 px-3 py-1">
                <ul class=" nav flex-column ms-1">
                    <li class="w-100">
                        <a href="/admin/product/list"
                            class="nav-link px-0 {{isset($menu) && $menu == 'product/list' ?'active':''}}">
                            <i class="fa fa-users  opacity-60 me-2"></i>
                            لیست محصولات
                        </a>
                    </li>
                    <li class="w-100">
                        <a href="{{env('DOMAIN')}}/admin/product/create"
                            class="nav-link px-0 {{isset($menu) && $menu == 'product/create' ?'active':''}}">
                            <i class="fa fa-file  opacity-60 me-2"></i>
                            اضافه کردن محصول
                        </a>
                    </li>
                    <li class="w-100">
                        <a href="/admin/order/list"
                            class="nav-link px-0 {{isset($menu) && $menu == 'order/list' ?'active':''}}">
                            <i class="fa fa-users  opacity-60 me-2"></i>
                            لیست سفارشات
                        </a>
                    </li>
                    <li class="w-100">
                        <a href="{{env('DOMAIN')}}/admin/order/create"
                            class="nav-link px-0 {{isset($menu) && $menu == 'order/create' ?'active':''}}">
                            <i class="fa fa-file  opacity-60 me-2"></i>
                            اضافه کردن سفارش
                        </a>
                    </li>
                    <li class="w-100">
                        <a href="/admin/customer/list"
                            class="nav-link px-0 {{isset($menu) && $menu == 'customer/list' ?'active':''}}">
                            <i class="fa fa-users  opacity-60 me-2"></i>
                            لیست مشتریان
                        </a>
                    </li>
                    <li class="w-100">
                        <a href="{{env('DOMAIN')}}/admin/customer/create"
                            class="nav-link px-0 {{isset($menu) && $menu == 'customer/create' ?'active':''}}">
                            <i class="fa fa-file  opacity-60 me-2"></i>
                            اضافه کردن مشتری
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        @endif
        <a href="/logout" class="card-nav-link  px-0 align-middle rotate d-flex align-items-center gap-2 ">
            <i class="fa fa-sign-out  opacity-60 me-2"></i>

                خروج

        </a>

    </div>
