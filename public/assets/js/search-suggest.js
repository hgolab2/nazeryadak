/**
 * پیشنهاد زنده‌ی جعبه‌ی جستجو (هدر دسکتاپ، نوار موبایل و شیت جستجو).
 *
 * با هر حرفی که کاربر تایپ می‌کند همان عبارت به /search-suggest فرستاده
 * می‌شود و نتیجه زیر فیلد باز می‌شود. جستجوی سرور دقیقا همان
 * scopeSearchText صفحه‌ی /shop است، پس چیزی که اینجا پیشنهاد می‌شود با
 * نتیجه‌ی زدن دکمه‌ی «جستجو» یکی است.
 *
 * استفاده: <input data-search-suggest> داخل یک <form action="/shop">
 */
(function () {
    'use strict';

    var MIN_CHARS = 2;
    var DEBOUNCE_MS = 220;
    var RECENT_KEY = 'nx_recent_searches';
    var RECENT_MAX = 6;

    /* پاسخ‌های سرور در همین صفحه کش می‌شوند؛ پاک کردن یک حرف و تایپ دوباره‌ی
       آن نباید درخواست تازه بزند. */
    var cache = {};

    var LETTERS = {
        'ي': 'ی', 'ى': 'ی', 'ك': 'ک', 'ة': 'ه',
        'أ': 'ا', 'إ': 'ا', 'آ': 'ا', 'ؤ': 'و',
        '‌': ' ', 'ـ': ''
    };
    var DIGITS = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9' };

    function normalize(value) {
        var out = String(value == null ? '' : value).toLowerCase();
        out = out.replace(/[يىكةأإآؤ‌ـ]/g, function (ch) { return LETTERS[ch]; });
        out = out.replace(/[۰-۹]/g, function (d) { return DIGITS[d]; });

        return out.replace(/\s+/g, ' ').trim();
    }

    /** ارقام در بقیه‌ی سایت فارسی نمایش داده می‌شوند؛ شمارنده هم باید هم‌شکل باشد. */
    function toPersianDigits(value) {
        return String(value).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /**
     * پررنگ کردن همان تکه‌ای از عنوان که کاربر تایپ کرده. مقایسه روی متن
     * یکسان‌سازی‌شده انجام می‌شود ولی برش روی متن اصلی، تا «ي» عربیِ عنوان
     * هم با «ی» فارسیِ تایپ‌شده پیدا شود و شکل نمایش عوض نشود.
     */
    function highlight(text, term) {
        var source = String(text == null ? '' : text);
        var words = normalize(term).split(' ').filter(Boolean);
        if (!words.length) {
            return escapeHtml(source);
        }

        /* نگاشت حرف‌به‌حرف: نیم‌فاصله و کشیده طول را تغییر می‌دهند، پس
           موقعیت‌ها را از روی همین نگاشت برمی‌گردانیم. */
        var flat = '';
        var index = [];
        for (var i = 0; i < source.length; i++) {
            /* فاصله عمدا حذف نمی‌شود تا یک کلمه‌ی کوتاه، تصادفی روی مرزِ
               دو کلمه‌ی عنوان هایلایت نشود. */
            var mapped = /\s/.test(source[i]) ? ' ' : normalize(source[i]);
            for (var j = 0; j < mapped.length; j++) {
                flat += mapped[j];
                index.push(i);
            }
        }

        var marks = new Array(source.length).fill(false);
        words.forEach(function (word) {
            var from = flat.indexOf(word);
            while (from !== -1) {
                for (var k = from; k < from + word.length && k < index.length; k++) {
                    marks[index[k]] = true;
                }
                from = flat.indexOf(word, from + word.length);
            }
        });

        var html = '';
        var open = false;
        for (var p = 0; p < source.length; p++) {
            if (marks[p] && !open) { html += '<mark>'; open = true; }
            if (!marks[p] && open) { html += '</mark>'; open = false; }
            html += escapeHtml(source[p]);
        }

        return html + (open ? '</mark>' : '');
    }

    function readRecent() {
        try {
            var stored = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
            return Array.isArray(stored) ? stored.filter(function (item) { return typeof item === 'string' && item.trim(); }) : [];
        } catch (e) {
            return [];
        }
    }

    function rememberSearch(term) {
        var value = String(term || '').trim();
        if (value.length < MIN_CHARS) {
            return;
        }
        var key = normalize(value);
        var list = readRecent().filter(function (item) { return normalize(item) !== key; });
        list.unshift(value);
        try {
            localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_MAX)));
        } catch (e) { /* حالت مرور خصوصی؛ ذخیره نشدن تاریخچه مهم نیست */ }
    }

    function clearRecent() {
        try { localStorage.removeItem(RECENT_KEY); } catch (e) { /* بی‌اهمیت */ }
    }

    function Suggest(input) {
        this.input = input;
        this.form = input.form || input.closest('form');
        this.box = input.parentElement;
        this.timer = null;
        this.controller = null;
        this.items = [];
        this.active = -1;
        this.open = false;

        this.panel = document.createElement('div');
        this.panel.className = 'nx-suggest';
        this.panel.setAttribute('role', 'listbox');
        this.panel.hidden = true;

        if (getComputedStyle(this.box).position === 'static') {
            this.box.style.position = 'relative';
        }
        this.box.appendChild(this.panel);

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-autocomplete', 'list');

        this.bind();
    }

    Suggest.prototype.bind = function () {
        var self = this;

        this.input.addEventListener('input', function () {
            self.request(self.input.value);
        });

        this.input.addEventListener('focus', function () {
            if (self.input.value.trim().length >= MIN_CHARS) {
                self.request(self.input.value);
            } else {
                self.renderRecent();
            }
        });

        this.input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                self.close();
                return;
            }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                if (!self.open || !self.items.length) {
                    return;
                }
                event.preventDefault();
                self.move(event.key === 'ArrowDown' ? 1 : -1);
                return;
            }
            if (event.key === 'Enter' && self.open && self.active > -1) {
                event.preventDefault();
                self.go(self.items[self.active]);
            }
        });

        /* mousedown به‌جای click: بستن پنل روی blur نباید کلیک را ببلعد */
        this.panel.addEventListener('mousedown', function (event) {
            var row = event.target.closest('[data-url]');
            if (!row) {
                return;
            }
            event.preventDefault();
            self.go(row);
        });

        this.panel.addEventListener('click', function (event) {
            if (event.target.closest('[data-clear-recent]')) {
                event.preventDefault();
                clearRecent();
                self.close();
            }
        });

        document.addEventListener('click', function (event) {
            if (!self.box.contains(event.target)) {
                self.close();
            }
        });

        if (this.form) {
            this.form.addEventListener('submit', function () {
                rememberSearch(self.input.value);
            });
        }
    };

    Suggest.prototype.request = function (value) {
        var self = this;
        var term = String(value || '').trim();

        clearTimeout(this.timer);
        if (term.length < MIN_CHARS) {
            this.renderRecent();
            return;
        }

        var key = normalize(term);
        if (cache[key]) {
            this.render(cache[key], term);
            return;
        }

        this.timer = setTimeout(function () {
            if (self.controller) {
                self.controller.abort();
            }
            self.controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

            fetch('/search-suggest?q=' + encodeURIComponent(term), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: self.controller ? self.controller.signal : undefined
            })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (data) {
                    if (!data) {
                        return;
                    }
                    cache[key] = data;
                    /* ممکن است کاربر در این فاصله چیز دیگری تایپ کرده باشد */
                    if (normalize(self.input.value) === key) {
                        self.render(data, term);
                    }
                })
                .catch(function () { /* قطعی شبکه: جعبه‌ی جستجو مثل قبل کار می‌کند */ });
        }, DEBOUNCE_MS);
    };

    Suggest.prototype.renderRecent = function () {
        var recent = readRecent();
        if (!recent.length) {
            this.close();
            return;
        }

        var html = '<div class="nx-suggest__head">'
            + '<span>جستجوهای اخیر</span>'
            + '<button type="button" data-clear-recent>پاک کردن</button>'
            + '</div>';

        recent.forEach(function (item) {
            html += '<a class="nx-suggest__term" data-url="/shop?title=' + encodeURIComponent(item) + '"'
                + ' href="/shop?title=' + encodeURIComponent(item) + '" data-term="' + escapeHtml(item) + '" role="option">'
                + '<i class="fas fa-history"></i><span>' + escapeHtml(item) + '</span></a>';
        });

        this.show(html);
    };

    Suggest.prototype.render = function (data, term) {
        var products = data.products || [];
        var terms = data.terms || [];

        if (!products.length && !terms.length) {
            this.show(
                '<div class="nx-suggest__empty">'
                + '<i class="fas fa-search"></i>'
                + '<p>چیزی با «' + escapeHtml(term) + '» پیدا نشد.</p>'
                + '<span>نام کوتاه‌تر یا کد فنی قطعه را امتحان کنید.</span>'
                + '</div>'
            );
            return;
        }

        var html = '';

        if (terms.length) {
            terms.forEach(function (row) {
                html += '<a class="nx-suggest__term" data-url="' + escapeHtml(row.url) + '" href="' + escapeHtml(row.url) + '" role="option">'
                    + '<i class="fas ' + escapeHtml(row.icon || 'fa-search') + '"></i>'
                    + '<span>' + highlight(row.label, term) + '</span></a>';
            });
        }

        if (products.length) {
            html += '<div class="nx-suggest__head"><span>محصولات</span></div>';
            products.forEach(function (product) {
                html += '<a class="nx-suggest__item" data-url="' + escapeHtml(product.url) + '" href="' + escapeHtml(product.url) + '" role="option">'
                    + '<img src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.title) + '" loading="lazy" decoding="async" width="44" height="44"'
                    + ' onerror="this.onerror=null;this.src=\'/images/no-image.svg\';">'
                    + '<span class="nx-suggest__body">'
                    + '<span class="nx-suggest__title">' + highlight(product.title, term) + '</span>'
                    + (product.sku ? '<span class="nx-suggest__meta"><i class="fas fa-barcode"></i> <bdi>' + escapeHtml(product.sku) + '</bdi></span>' : '')
                    + '</span>'
                    + (product.price ? '<span class="nx-suggest__price">' + escapeHtml(product.price) + '</span>' : '')
                    + '</a>';
            });
        }

        if (data.url) {
            html += '<a class="nx-suggest__all" data-url="' + escapeHtml(data.url) + '" href="' + escapeHtml(data.url) + '" role="option">'
                + 'مشاهده همه‌ی نتایج «' + escapeHtml(term) + '»'
                + (data.total ? ' <b>(' + toPersianDigits(parseInt(data.total, 10) || 0) + ' قطعه)</b>' : '')
                + '</a>';
        }

        this.show(html);
    };

    Suggest.prototype.show = function (html) {
        this.panel.innerHTML = html;
        this.panel.hidden = false;
        this.items = Array.prototype.slice.call(this.panel.querySelectorAll('[data-url]'));
        this.active = -1;
        this.open = true;
        this.input.setAttribute('aria-expanded', 'true');
    };

    Suggest.prototype.close = function () {
        clearTimeout(this.timer);
        this.panel.hidden = true;
        this.open = false;
        this.items = [];
        this.active = -1;
        this.input.setAttribute('aria-expanded', 'false');
    };

    Suggest.prototype.move = function (step) {
        if (this.active > -1 && this.items[this.active]) {
            this.items[this.active].classList.remove('is-active');
        }
        this.active = (this.active + step + this.items.length) % this.items.length;
        var row = this.items[this.active];
        row.classList.add('is-active');
        row.scrollIntoView({ block: 'nearest' });
    };

    Suggest.prototype.go = function (row) {
        if (!row) {
            return;
        }
        rememberSearch(row.getAttribute('data-term') || this.input.value);
        window.location.href = row.getAttribute('data-url');
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[data-search-suggest]').forEach(function (input) {
            new Suggest(input);
        });
    });
})();
