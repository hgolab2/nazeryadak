@php
    /* برچسب پستی: صفحه‌ای که مستقیم روی برچسب چاپ و روی جعبه چسبانده می‌شود.
       عمداً از قالب مدیریت ارث نمی‌برد؛ سربرگ، منو و اسکریپت‌های پنل روی
       کاغذ فقط دردسر می‌سازند و اندازه‌ی صفحه باید دقیقاً کنترل شود.

       دو اندازه پشتیبانی می‌شود:
       - 10x15 : رول برچسب پستی؛ هر سفارش یک برگه
       - a4    : چهار برچسب روی یک A4 برای وقتی چاپگر برچسب در دسترس نیست */

    $isA4 = $size === 'a4';

    $senderAddress    = shopAddress();
    $senderPostalCode = shopPostalCode();
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>برچسب پستی{{ $orders->count() === 1 ? ' سفارش NY-' . $orders->first()->id : '' }}</title>
    <style>
        @font-face {
            font-family: label-fa;
            src: url('/assets/font/IRANSans/IRANSansWeb(FaNum).woff') format('woff');
            font-display: swap;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: label-fa, Tahoma, sans-serif; }

        body { background: #eceff1; padding: 20px 0; color: #000; }

        /* ── نوار ابزار؛ فقط روی صفحه ───────────────────────────── */
        .toolbar {
            max-width: 720px;
            margin: 0 auto 20px;
            background: #fff;
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            font-size: .82rem;
        }
        .toolbar .count { margin-inline-end: auto; color: #555; }
        .toolbar a, .toolbar button {
            border: 1px solid #cfd8dc;
            background: #fff;
            color: #263238;
            border-radius: 6px;
            padding: 7px 14px;
            font-size: .8rem;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar .is-active { background: #263238; border-color: #263238; color: #fff; }
        .toolbar .primary   { background: #1565c0; border-color: #1565c0; color: #fff; font-weight: 700; }
        .toolbar .hint { flex: 0 0 100%; color: #78909c; font-size: .72rem; line-height: 1.9; }

        /* ── ورق چاپ ───────────────────────────────────────────── */
        .sheet {
            background: #fff;
            margin: 0 auto 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,.12);
        }
        .sheet-10x15 { width: 100mm; padding: 4mm; }
        .sheet-a4    { width: 210mm; padding: 8mm; display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }

        /* ── خود برچسب ─────────────────────────────────────────── */
        .label {
            border: 1.5pt solid #000;
            padding: 3mm;
            display: flex;
            flex-direction: column;
            gap: 2mm;
            line-height: 1.75;
        }
        /* ارتفاع کمی کمتر از فضای واقعی کاغذ تا گِردکردن چاپگر یک صفحه‌ی خالی اضافه نکند */
        .sheet-10x15 .label { min-height: 138mm; }
        .sheet-a4 .label    { min-height: 128mm; }

        .lbl-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3mm;
            border-bottom: 1pt solid #000;
            padding-bottom: 2mm;
        }
        .lbl-brand      { display: flex; align-items: center; gap: 2mm; }
        .lbl-brand img  { width: 22mm; height: auto; }
        .lbl-brand b    { font-size: 10pt; }
        .lbl-code       { text-align: left; }
        .lbl-code b     { font-size: 13pt; letter-spacing: .5px; display: block; }
        .lbl-code span  { font-size: 8pt; }

        /* گیرنده: بزرگ‌ترین بخش برچسب، چون مأمور پست همین را می‌خواند */
        .lbl-to {
            border: 1pt solid #000;
            padding: 2.5mm;
            flex: 1;
        }
        .lbl-title {
            display: inline-block;
            background: #000;
            color: #fff;
            font-size: 8pt;
            padding: .6mm 2mm;
            margin-bottom: 1.5mm;
        }
        .lbl-name  { font-size: 13pt; font-weight: 700; }
        .lbl-phone { font-size: 12pt; font-weight: 700; direction: ltr; text-align: right; unicode-bidi: embed; }
        .lbl-addr  { font-size: 11pt; margin-top: 1mm; }
        .lbl-post  { font-size: 11.5pt; font-weight: 700; margin-top: 1.5mm; letter-spacing: 1px; }

        /* فرستنده: کوچک، فقط برای مرجوعی */
        .lbl-from { border: 1pt dashed #555; padding: 2mm; font-size: 8.5pt; }
        .lbl-from .lbl-title { background: #555; }

        .lbl-meta {
            display: flex;
            justify-content: space-between;
            gap: 2mm;
            font-size: 8.5pt;
            border-top: 1pt solid #000;
            padding-top: 1.5mm;
        }
        .lbl-cod { font-weight: 700; }

        .lbl-barcode { text-align: center; }
        .lbl-barcode svg  { max-width: 100%; height: auto; }
        .sheet-a4 .lbl-barcode svg { height: 12mm; }
        .lbl-barcode span { display: block; font-size: 8pt; letter-spacing: 2px; direction: ltr; }

        .empty-addr { color: #b71c1c; font-weight: 700; font-size: 10pt; }

        /* ── چاپ ───────────────────────────────────────────────── */
        @media print {
            @page { size: {{ $isA4 ? 'A4 portrait' : '100mm 150mm' }}; margin: {{ $isA4 ? '8mm' : '0' }}; }

            body   { background: #fff; padding: 0; }
            .toolbar { display: none !important; }

            .sheet {
                box-shadow: none;
                margin: 0;
                width: auto;
                padding: {{ $isA4 ? '0' : '4mm' }};
                break-after: page;
            }
            .sheet:last-child { break-after: auto; }

            .label { break-inside: avoid; }

            /* پس‌زمینه‌ی تیره‌ی عنوان‌ها باید روی کاغذ هم بیاید */
            .lbl-title { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button type="button" class="primary" onclick="window.print()">🖨 چاپ برچسب</button>
    <a href="{{ request()->fullUrlWithQuery(['size' => '10x15']) }}" class="{{ $isA4 ? '' : 'is-active' }}">رول ۱۰×۱۵</a>
    <a href="{{ request()->fullUrlWithQuery(['size' => 'a4']) }}" class="{{ $isA4 ? 'is-active' : '' }}">چهارتایی روی A4</a>
    <a href="/admin/order/list">بازگشت به لیست</a>
    <span class="count">{{ toPersianNumbers($orders->count(), false) }} برچسب</span>
    <span class="hint">
        در پنجره‌ی چاپ، «حاشیه» را روی «هیچ/None» و «مقیاس» را روی ۱۰۰٪ بگذارید و گزینه‌ی چاپ پس‌زمینه را فعال کنید تا اندازه‌ی برچسب دقیق دربیاید.
    </span>
</div>

@php
    // در حالت A4 هر ورق چهار برچسب می‌گیرد؛ در حالت رول هر برچسب یک ورق است
    $sheets = $isA4 ? $orders->chunk(4) : $orders->chunk(1);
@endphp

@foreach($sheets as $sheet)
<div class="sheet {{ $isA4 ? 'sheet-a4' : 'sheet-10x15' }}">
    @foreach($sheet as $order)
        @php
            $address = $order->address;
            $code    = 'NY-' . $order->id;

            $receiverName  = $address?->receiver_name  ?: $order->customer?->fullName();
            $receiverPhone = $address?->receiver_phone ?: $order->customer?->phone;

            $addressLine = $address
                ? trim(implode('، ', array_filter([
                    optional($address->province)->name,
                    $address->city,
                    $address->address_line,
                  ])), '، ')
                : '';

            $itemCount = (int) $order->items->sum('quantity');

            // مبلغ فقط وقتی روی برچسب می‌آید که هنوز پرداخت نشده باشد (پس‌کرایه)
            $unpaid = in_array($order->status, ['pending', 'awaiting_call', 'processing'], true);
        @endphp
        <div class="label">

            <div class="lbl-top">
                <div class="lbl-brand">
                    <img src="/assets/images/logo.png" alt="{{ seo_site_name() }}">
                    <b>{{ seo_site_name() }}</b>
                </div>
                <div class="lbl-code">
                    <b>{{ $code }}</b>
                    <span>{{ toPersianDate($order->created_at) }}</span>
                </div>
            </div>

            {{-- گیرنده --}}
            <div class="lbl-to">
                <span class="lbl-title">گیرنده</span>
                <div class="lbl-name">{{ $receiverName ?: '—' }}</div>
                <div class="lbl-phone">{{ $receiverPhone ? toPersianNumbers($receiverPhone, false) : '—' }}</div>

                @if($addressLine !== '')
                    <div class="lbl-addr">{{ $addressLine }}</div>
                @else
                    <div class="lbl-addr empty-addr">آدرس تحویل برای این سفارش ثبت نشده است.</div>
                @endif

                @if($address?->postal_code)
                    <div class="lbl-post">کد پستی: {{ toPersianNumbers($address->postal_code, false) }}</div>
                @endif
            </div>

            {{-- فرستنده --}}
            <div class="lbl-from">
                <span class="lbl-title">فرستنده</span>
                <div>
                    <b>{{ seo_site_name() }}</b>
                    @if(shopContactPhoneDisplay())
                        — {{ shopContactPhoneDisplay() }}
                    @endif
                </div>
                @if($senderAddress !== '')
                    <div>{{ $senderAddress }}@if($senderPostalCode !== '') — کد پستی: {{ toPersianNumbers($senderPostalCode, false) }}@endif</div>
                @endif
            </div>

            <div class="lbl-meta">
                <span>تعداد اقلام: {{ toPersianNumbers($itemCount, false) }}</span>
                <span>وضعیت: {{ $order->status() }}</span>
                @if($unpaid)
                    <span class="lbl-cod">پرداخت درب منزل: {{ toPersianNumbers(number_format((int) $order->final_price)) }} تومان</span>
                @else
                    <span class="lbl-cod">پرداخت‌شده</span>
                @endif
            </div>

            <div class="lbl-barcode">
                {!! barcode_code128_svg($code, $isA4 ? 34 : 45, $isA4 ? 1.3 : 1.7) !!}
                <span>{{ $code }}</span>
            </div>

        </div>
    @endforeach
</div>
@endforeach

</body>
</html>
