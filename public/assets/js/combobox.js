/**
 * کمبوباکس جستجوپذیر روی یک <select> معمولی.
 *
 * خودِ select در DOM می‌ماند و فقط پنهان می‌شود، پس هر کدی که value آن را
 * می‌خواند یا به رویداد change آن گوش می‌دهد، دست‌نخورده کار می‌کند.
 *
 * استفاده:  <div class="nx-combo" data-combo data-placeholder="..."> <select>…</select> </div>
 */
(function () {
    'use strict';

    /*
     * نام خودروها در دیتابیس ترکیبی از حروف عربی و فارسی است («پيكان» با ي و ك
     * عربی، ولی «پراید» با ی فارسی). بدون یکسان‌سازی، کاربری که «پیکان» تایپ
     * می‌کند هیچ نتیجه‌ای نمی‌گیرد.
     */
    var LETTERS = {
        'ي': 'ی', // ي → ی
        'ى': 'ی', // ى → ی
        'ك': 'ک', // ك → ک
        'ة': 'ه', // ة → ه
        'أ': 'ا', // أ → ا
        'إ': 'ا', // إ → ا
        'آ': 'ا', // آ → ا
        'ؤ': 'و', // ؤ → و
        '‌': ' ',      // نیم‌فاصله
        'ـ': ''        // کشیده
    };

    var DIGITS = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9' };

    function normalize(value) {
        var out = String(value == null ? '' : value).toLowerCase();
        out = out.replace(/[يىكةأإآؤ‌ـ]/g, function (ch) {
            return LETTERS[ch];
        });
        out = out.replace(/[۰-۹]/g, function (d) { return DIGITS[d]; });

        return out.replace(/\s+/g, ' ').trim();
    }

    function build(root) {
        var select = root.querySelector('select');
        if (!select || root.dataset.comboReady) {
            return;
        }
        root.dataset.comboReady = '1';

        var options = Array.prototype.map.call(select.options, function (option, index) {
            return { index: index, value: option.value, label: option.textContent.trim(), search: normalize(option.textContent) };
        });

        var control = document.createElement('div');
        control.className = 'nx-combo-control';

        var icon = document.createElement('i');
        icon.className = 'fas fa-magnifying-glass';

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'nx-combo-input';
        input.autocomplete = 'off';
        input.placeholder = root.dataset.placeholder || 'جستجو کنید...';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');

        var clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'nx-combo-clear';
        clear.setAttribute('aria-label', 'حذف انتخاب');
        clear.innerHTML = '&times;';

        var list = document.createElement('ul');
        list.className = 'nx-combo-list';
        list.setAttribute('role', 'listbox');

        control.appendChild(icon);
        control.appendChild(input);
        control.appendChild(clear);
        root.appendChild(control);
        root.appendChild(list);

        var highlighted = -1;
        var visible = [];

        // گزینه‌ی اول select نقش «همه» را دارد و مقدارش خالی است
        function placeholderLabel() {
            return options.length ? options[0].label : '';
        }

        function syncInputToSelect() {
            var selected = options[select.selectedIndex] || options[0];
            input.value = selected && selected.value ? selected.label : '';
            input.placeholder = selected && selected.value ? selected.label : (root.dataset.placeholder || placeholderLabel());
            root.classList.toggle('has-value', !!(selected && selected.value));
        }

        function render(term) {
            var query = normalize(term);
            // با عبارت جستجو، گزینه‌ی «همه» در نتایج نمی‌ماند؛ وگرنه اولین آیتم
            // فهرست بود و Enter به‌جای اولین نتیجه‌ی واقعی، آن را انتخاب می‌کرد.
            // برای برگرداندن حالت «همه»، دکمه‌ی × هست.
            visible = query === ''
                ? options.slice()
                : options.filter(function (option) {
                    return option.search.indexOf(query) !== -1;
                });

            list.innerHTML = '';

            if (!visible.length) {
                var empty = document.createElement('li');
                empty.className = 'is-empty';
                empty.textContent = 'موردی پیدا نشد';
                list.appendChild(empty);
                highlighted = -1;
                return;
            }

            visible.forEach(function (option, position) {
                var item = document.createElement('li');
                item.textContent = option.label;
                item.setAttribute('role', 'option');
                if (option.index === select.selectedIndex) {
                    item.classList.add('is-selected');
                }
                if (position === highlighted) {
                    item.classList.add('is-highlighted');
                }
                item.addEventListener('mousedown', function (event) {
                    // mousedown به‌جای click، چون blur زودتر از click اجرا می‌شود و لیست بسته می‌شود
                    event.preventDefault();
                    choose(option);
                });
                list.appendChild(item);
            });
        }

        function open() {
            root.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');
            highlighted = -1;
            render('');
        }

        function close() {
            root.classList.remove('is-open');
            input.setAttribute('aria-expanded', 'false');
            syncInputToSelect();
        }

        function choose(option) {
            select.selectedIndex = option.index;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        }

        function moveHighlight(step) {
            if (!visible.length) {
                return;
            }
            highlighted = (highlighted + step + visible.length) % visible.length;
            render(input.value);
            var active = list.children[highlighted];
            if (active && active.scrollIntoView) {
                active.scrollIntoView({ block: 'nearest' });
            }
        }

        input.addEventListener('focus', open);
        control.addEventListener('click', function () { input.focus(); });

        input.addEventListener('input', function () {
            root.classList.add('is-open');
            highlighted = -1;
            render(input.value);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveHighlight(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveHighlight(-1);
            } else if (event.key === 'Enter') {
                if (root.classList.contains('is-open')) {
                    event.preventDefault();
                    var pick = visible[highlighted >= 0 ? highlighted : 0];
                    if (pick) {
                        choose(pick);
                    }
                }
            } else if (event.key === 'Escape') {
                close();
                input.blur();
            }
        });

        input.addEventListener('blur', function () {
            // مهلت کوتاه تا کلیک روی آیتم از دست نرود
            window.setTimeout(function () {
                if (!root.contains(document.activeElement)) {
                    close();
                }
            }, 120);
        });

        clear.addEventListener('click', function (event) {
            event.preventDefault();
            if (options.length) {
                choose(options[0]);
            }
        });

        // اگر کد دیگری مقدار select را عوض کرد، ورودی هم به‌روز شود
        select.addEventListener('change', syncInputToSelect);

        syncInputToSelect();
    }

    function init() {
        document.querySelectorAll('[data-combo]').forEach(build);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
