/*----------------------- sweetalert2 با بارگذاری تأخیری --------------------*/

/*
 * sweetalert2 حدود ۷۸ کیلوبایت است و در نمای اول هیچ صفحه‌ای لازم نمی‌شود؛
 * از باندل بیرون آمد تا با CSS و تصویر اصلی سر پهنای باند رقابت نکند.
 *
 * تا رسیدن فایل اصلی، همین شیء جای Swal را می‌گیرد: هر فراخوانی، اول فایل را
 * می‌آورد و بعد همان فراخوانی را روی نسخه‌ی واقعی تکرار می‌کند. چون خروجی هم
 * مثل خود Swal یک Promise است، کدهای then(...) بدون تغییر کار می‌کنند.
 */
(function () {
    if (window.Swal) return;

    var pending = null;

    function load() {
        if (!pending) {
            pending = new Promise(function (resolve, reject) {
                var s = document.createElement('script');
                s.src = '/js/sweetalert2.all.js';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        return pending;
    }

    function lazyFire(mixinOptions) {
        return function () {
            var args = arguments;
            return load().then(function () {
                var target = mixinOptions ? window.Swal.mixin(mixinOptions) : window.Swal;
                return target.fire.apply(target, args);
            });
        };
    }

    window.Swal = {
        fire: lazyFire(null),
        mixin: function (options) {
            return { fire: lazyFire(options) };
        }
    };

    // بعد از بارگذاری صفحه بی‌سروصدا آورده می‌شود تا اولین کلیک کاربر منتظر
    // شبکه نماند.
    window.addEventListener('load', function () {
        setTimeout(load, 2000);
    });
})();

/*----------------------- mobile menu --------------------*/

$('.mobile-menu-level-1 > li.has-mobile-submenu > a').on('click', function (e) {
    // href="#" است؛ بدون این، کلیک روی سرشاخه صفحه را به بالا می‌پراند و
    // «#» به آدرس اضافه می‌کند.
    e.preventDefault();

    var _this = $(this).parent();

    if (_this.hasClass('open')) {

        _this.removeClass('open');
        _this.find('.mobile-menu-level-2').slideUp(200);

    } 
    
    else {

        _this.addClass('open');
        _this.find('.mobile-menu-level-2').slideDown(200);
        _this.siblings('li').children('.mobile-menu-level-2').slideUp(200);
        _this.siblings('li').removeClass('open')

    }
})

$('.mobile-menu-level-2 > li.has-mobile-submenu-2 > a').on('click', function (e) {
    e.preventDefault();

    var _this1 = $(this).parent();

    if (_this1.hasClass('open')){

        _this1.removeClass('open');
        _this1.find('.mobile-menu-level-3').slideUp(200);
    }
    
    else {

        _this1.addClass('open');
        _this1.find('.mobile-menu-level-3').slideDown(200);
        _this1.siblings('li').children('.mobile-menu-level-3').slideUp(200);
        _this1.siblings('li').removeClass('open')

    }
})


/*----------------------- owl carousel slider --------------------*/

/*
 * دکمه‌های قبلی/بعدی و نقطه‌های owl را قابل استفاده برای صفحه‌خوان می‌کند.
 *
 * owl خودش روی <button> یک role="presentation" می‌گذارد که همان دکمه را از
 * درخت دسترس‌پذیری بیرون می‌اندازد، و چون محتوایش فقط یک آیکن فونتی است، نام
 * قابل خواندنی هم ندارد. اینجا بعد از ساخته‌شدن nav اصلاح می‌شوند.
 *
 * روی document بسته می‌شود تا هر اسلایدری در هر صفحه‌ای — از جمله آن‌هایی که
 * در <script> انتهای قالب راه‌اندازی می‌شوند — پوشش داده شود.
 */
$(document).on('initialized.owl.carousel refreshed.owl.carousel', function (event) {
    var $carousel = $(event.target);

    $carousel.children('.owl-nav').children('button').each(function () {
        this.removeAttribute('role');
        this.setAttribute('aria-label', this.className.indexOf('owl-prev') !== -1 ? 'قبلی' : 'بعدی');
    });

    $carousel.children('.owl-dots').children('button').each(function (index) {
        this.removeAttribute('role');
        this.setAttribute('aria-label', 'رفتن به اسلاید ' + (index + 1));
    });

    $carousel.children('.owl-nav').find('i').attr('aria-hidden', 'true');
});


/*----------------------- ریل افقی محصولات --------------------*/

/*
 * دکمه‌های چپ/راست ریل. خود ریل یک عنصر با overflow-x است و بدون این کد هم
 * با کشیدن یا چرخ ماوس کار می‌کند؛ این فقط پرش یک صفحه‌ای را اضافه می‌کند و
 * دکمه‌ی بی‌مصرف را محو می‌کند.
 *
 * در RTL مقدار scrollLeft در مرورگرهای امروزی از صفر شروع می‌شود و منفی
 * می‌شود، پس همه جا با قدرمطلق سنجیده می‌شود.
 */
(function () {
    function trackOf(el) {
        var rail = el.closest ? el.closest('.nx-rail') : null;
        return rail ? rail.querySelector('.nx-rail-track') : null;
    }

    function sync(track) {
        var rail = track.parentNode;
        var max = track.scrollWidth - track.clientWidth;
        var pos = Math.abs(track.scrollLeft);
        var prev = rail.querySelector('.nx-rail-prev');
        var next = rail.querySelector('.nx-rail-next');

        if (prev) prev.classList.toggle('is-disabled', pos <= 1);
        if (next) next.classList.toggle('is-disabled', pos >= max - 1);
    }

    $(document).on('click', '.nx-rail-nav', function () {
        var track = trackOf(this);
        if (!track) return;

        // در RTL، «بعدی» یعنی حرکت به سمت چپ
        var step = track.clientWidth * (this.classList.contains('nx-rail-next') ? -1 : 1);
        track.scrollBy({ left: step, behavior: 'smooth' });
    });

    // scroll بابل نمی‌شود، پس واگذاری به document کار نمی‌کند و باید روی خود
    // ریل بست. passive هم لازم است تا اسکرول لمسی منتظر این هندلر نماند.
    $(function () {
        document.querySelectorAll('.nx-rail-track').forEach(function (track) {
            track.addEventListener('scroll', function () { sync(track); }, { passive: true });
            sync(track);
        });
    });
})();


/*----------------------- tooltip --------------------*/

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})

/*----------------------- range slider --------------------*/

$(function () {
    var steps = $('.divv');
    steps.each(function () {
        var self = $(this);
        var step_title = self.attr('data-title')
        var slider = self.parent();
        var title;
        self.hover(function () {
            title = slider.attr('data-title');
            slider.attr('data-title', step_title);
        }, function () {
            slider.attr('data-title', title);
        })

        self.on('click', function () {
            slider.attr('data-title', step_title);
            title = slider.attr('data-title');

            slider.find('.divv').removeClass('is-active');
            self.addClass('is-active');

            var move = parseInt(self.attr('data-value'));

            console.log(move)
            slider.find('.time-line').css({'width': move + '%'});
            slider.find('.slidemove').animate({'right': 'calc('+move+'- ' + 10 + 'px)'}, 200);

        })

    })
})

/*-----------------------  increment & decrement cart --------------------*/

var incrementQty;
var decrementQty;
var plusBtn  = $(".cart-qty-plus");
var minusBtn = $(".cart-qty-minus");

var incrementQty = plusBtn.click(function(){
var $n = $(this)
  .parent(".button-container")
  .find(".qty");
$n.val(Number($n.val())+1 );
});

var decrementQty = minusBtn.click(function(){
    var $n = $(this)
    .parent(".button-container")
    .find(".qty");
  var QtyVal = Number($n.val());
  if (QtyVal > 0) {
    $n.val(QtyVal-1);
  }
});

/*----------------------- scroll to top --------------------*/

$(document).ready(function($){
    var offset = 100;
    var speed = 250;
    var duration = 500;
	   $(window).scroll(function(){
            if ($(this).scrollTop() < offset) {
			     $('.topbutton') .fadeOut(duration);
            } else {
			     $('.topbutton') .fadeIn(duration);
            }
        });
	$('.topbutton').on('click', function(){
		$('html, body').animate({scrollTop:0}, speed);
		return false;
		});
});



