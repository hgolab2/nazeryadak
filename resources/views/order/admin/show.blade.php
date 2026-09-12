@extends('layout.managmentLayout', [
    'title' => 'سفارش ' . $order->id,
])

@section('main_content')
@php
    /* صفحه‌ی مشاهده‌ی سفارش در پنل.
       تا پیش از این وجود نداشت و لینک «مشاهده» در لیست ۴۰۴ می‌داد؛ مدیر برای
       دیدن اقلام یک سفارش مجبور بود مستقیم به دیتابیس نگاه کند. */

    $address = $order->address;

    // نام و تلفن تحویل‌گیرنده روی آدرس ثبت می‌شود؛ اگر آدرسی نبود، مشخصات
    // خود مشتری ملاک است (همان قاعده‌ی برچسب پستی)
    $receiverName  = $address?->receiver_name  ?: $order->customer?->fullName();
    $receiverPhone = $address?->receiver_phone ?: $order->customer?->phone;

    $addressLine = $address
        ? trim(implode('، ', array_filter([
            optional($address->province)->name,
            $address->city,
            $address->address_line,
          ])), '، ')
        : '';

    // جمع واقعی سطرهای فاکتور؛ اگر با final_price نخواند یعنی مبلغ ذخیره‌شده
    // با اقلام هماهنگ نیست و مدیر باید بداند
    $itemsSum = (int) $order->items->sum(fn ($item) => (int) ($item->total_price ?: $item->unit_price * $item->quantity));
    $mismatch = $itemsSum !== (int) $order->final_price;
@endphp

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">{{ l('خانه') }}</a></li>
        <li class="breadcrumb-item"><a href="/admin/order/list">{{ l('مدیریت سفارشات') }}</a></li>
        <li class="breadcrumb-item active">{{ l('سفارش') }} {{ $order->id }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h1 class="h3 mb-0">
        {{ l('سفارش') }} <bdi>NY-{{ $order->id }}</bdi>
        <span class="badge order-status-badge {{ $order->statusBadgeClass() }} align-middle ms-2" data-order="{{ $order->id }}">{{ $order->status() }}</span>
    </h1>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        {{-- تغییر وضعیت همین‌جا؛ مشتری پیامک می‌گیرد و بله خبردار می‌شود --}}
        @include('order.admin._status', ['order' => $order])
        <a href="/admin/order/edit/{{ $order->id }}" class="btn btn-primary">
            <i class="fa fa-edit me-1"></i> {{ l('ویرایش') }}
        </a>
        <a href="/admin/order/label/{{ $order->id }}" target="_blank" class="btn btn-outline-dark">
            <i class="fa fa-tag me-1"></i> {{ l('برچسب پستی') }}
        </a>
        <a href="/admin/order/list" class="btn btn-light">{{ l('بازگشت') }}</a>
    </div>
</div>

<div class="row g-3 mb-3">

    {{-- خلاصه سفارش --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-bold"><i class="fa fa-receipt me-1"></i> {{ l('مشخصات سفارش') }}</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">{{ l('تاریخ ثبت') }}</span>
                    <span>{{ $order->created_at ? toPersianDate($order->created_at) : '—' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">{{ l('آخرین تغییر') }}</span>
                    <span>{{ $order->updated_at ? toPersianDate($order->updated_at) : '—' }}</span>
                </li>
                @if($order->paid_at)
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">{{ l('تسویه') }}</span>
                    <span>{{ toPersianDate($order->paid_at) }}</span>
                </li>
                @endif
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">{{ l('وضعیت') }}</span>
                    <span class="badge order-status-badge {{ $order->statusBadgeClass() }}" data-order="{{ $order->id }}">{{ $order->status() }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">{{ l('روش ارسال') }}</span>
                    <span>{{ $order->shippingMethod?->title ?? $order->shippingMethod?->name ?? l('ارسال عادی') }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">{{ l('تعداد اقلام') }}</span>
                    <span>{{ (int) $order->items->sum('quantity') }}</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- مشتری --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-bold"><i class="fa fa-user me-1"></i> {{ l('مشتری') }}</div>
            <div class="card-body">
                @if($order->customer)
                    <p class="mb-1 fw-bold">{{ $order->customer->fullName() }}</p>
                    <p class="mb-2"><bdi>{{ $order->customer->phone }}</bdi></p>
                    <a href="/admin/customer/edit/{{ $order->customer->id }}" class="btn btn-sm btn-outline-primary">
                        {{ l('پرونده مشتری') }}
                    </a>
                @else
                    {{-- سفارشِ بی‌مشتری یعنی رکورد مشتری پاک شده است --}}
                    <p class="text-danger mb-0">{{ l('مشتری این سفارش حذف شده است.') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- آدرس تحویل --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-bold"><i class="fa fa-location-dot me-1"></i> {{ l('آدرس تحویل') }}</div>
            <div class="card-body">
                <p class="mb-1">{{ l('تحویل‌گیرنده') }}: <b>{{ $receiverName ?: '—' }}</b></p>
                <p class="mb-1">{{ l('تماس') }}: <bdi>{{ $receiverPhone ?: '—' }}</bdi></p>
                @if($addressLine !== '')
                    <p class="mb-1">{{ $addressLine }}</p>
                    @if($address?->postal_code)
                        <p class="mb-0">{{ l('کد پستی') }}: <bdi>{{ $address->postal_code }}</bdi></p>
                    @endif
                @else
                    {{-- بدون آدرس، برچسب پستی هم خالی چاپ می‌شود --}}
                    <p class="text-danger mb-0">{{ l('برای این سفارش آدرسی ثبت نشده است.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- اقلام --}}
<div class="card shadow-sm mb-3">
    <div class="card-header fw-bold"><i class="fa fa-boxes-stacked me-1"></i> {{ l('اقلام سفارش') }}</div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped mb-0">
            <thead class="table-primary">
            <tr>
                <th class="text-center" style="width:60px;">#</th>
                <th>{{ l('قطعه') }}</th>
                <th class="text-center">{{ l('تعداد') }}</th>
                <th class="text-center">{{ l('قیمت واحد') }}</th>
                <th class="text-center">{{ l('جمع') }}</th>
                <th class="text-center" style="width:190px;">{{ l('قیمت خرید واحد') }}</th>
                <th class="text-center">{{ l('سود قلم') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($order->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>
                        @if($item->product)
                            <a href="/admin/product/edit/{{ $item->product->id }}">{{ $item->product->title }}</a>
                            @if($item->product->sku)
                                <div class="text-muted" style="font-size:.75rem;">
                                    {{ l('کد فنی') }}: <bdi>{{ $item->product->sku }}</bdi>
                                </div>
                            @endif
                        @else
                            {{-- محصول حذف شده ولی قلم فاکتور باید بماند --}}
                            <span class="text-danger">{{ l('قطعه حذف‌شده') }} (#{{ $item->product_id }})</span>
                        @endif
                    </td>
                    <td class="text-center">{{ (int) $item->quantity }}</td>
                    <td class="text-center">{{ number_format((int) $item->unit_price) }}</td>
                    <td class="text-center">
                        {{ number_format((int) ($item->total_price ?: $item->unit_price * $item->quantity)) }}
                    </td>
                    <td class="text-center">
                        {{-- قیمت خرید در لحظه‌ی سفارش قفل شده؛ اگر خالی یا غلط است همین‌جا اصلاح می‌شود --}}
                        <form method="POST" action="/admin/order/{{ $order->id }}/item/{{ $item->id }}/cost" class="d-flex gap-1 justify-content-center">
                            @csrf @method('put')
                            <input type="text" name="unit_cost" class="form-control form-control-sm text-center" style="max-width:120px;"
                                   value="{{ $item->unit_cost !== null ? number_format((int) $item->unit_cost) : '' }}"
                                   placeholder="{{ $item->product ? number_format($item->product->purchaseCost()) : '—' }}"
                                   inputmode="numeric" dir="ltr">
                            <button class="btn btn-sm btn-outline-secondary" title="{{ l('ذخیره قیمت خرید') }}"><i class="fa fa-save"></i></button>
                        </form>
                        @if($item->unit_cost === null && (int) $item->unit_price > 0)
                            <div class="text-warning" style="font-size:.7rem;">{{ l('ثبت نشده — سود برآوردی') }}</div>
                        @endif
                    </td>
                    <td class="text-center {{ $item->profit() >= 0 ? 'profit-pos' : 'profit-neg' }}">
                        {{ number_format($item->profit()) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">{{ l('این سفارش هیچ قلمی ندارد.') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-body border-top">
        @if($mismatch)
            {{-- عدد ذخیره‌شده با سطرهای فاکتور نمی‌خواند؛ معمولا یعنی سفارش
                 دستی ویرایش شده و مبلغش با اقلام هماهنگ نشده است --}}
            <div class="alert alert-warning py-2">
                <i class="fa fa-triangle-exclamation me-1"></i>
                {{ l('جمع سطرهای فاکتور') }} ({{ number_format($itemsSum) }})
                {{ l('با «جمع کل اقلام» ثبت‌شده') }} ({{ number_format((int) $order->final_price) }})
                {{ l('یکی نیست. مبلغ سفارش را در صفحه ویرایش اصلاح کنید.') }}
            </div>
        @endif

        <div class="row justify-content-end">
            <div class="col-md-5">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">{{ l('جمع کل اقلام') }}</td>
                        <td class="text-end">{{ number_format((int) $order->final_price) }} {{ l('تومان') }}</td>
                    </tr>
                    @if($order->hasDiscount())
                        <tr class="text-success">
                            <td>{{ l('تخفیف') }} <bdi>({{ $order->discount_code }})</bdi></td>
                            <td class="text-end">−{{ number_format((int) $order->discount_amount) }} {{ l('تومان') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-muted">{{ l('هزینه ارسال') }}</td>
                        <td class="text-end">
                            {{ (int) $order->shipping_price > 0 ? number_format((int) $order->shipping_price) . ' ' . l('تومان') : l('رایگان') }}
                        </td>
                    </tr>
                    <tr class="fw-bold border-top">
                        <td>{{ l('مبلغ پرداختی') }}</td>
                        <td class="text-end">{{ number_format((int) $order->total_price) }} {{ l('تومان') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- سود و هزینه‌های سفارش --}}
@php
    $cost      = $order->itemsCost();
    $expenses  = $order->expenses;
    $expTotal  = (int) $expenses->sum('amount');
    $profit    = $order->netProfit();
    $expenseCategories = \App\Models\FinanceTransaction::CATEGORIES[\App\Models\FinanceTransaction::TYPE_EXPENSE];
@endphp
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-bold"><i class="fa fa-chart-line me-1"></i> {{ l('سود این سفارش') }}</div>
            <table class="table table-sm mb-0">
                <tr>
                    <td class="text-muted">{{ l('فروش اقلام (پس از تخفیف)') }}</td>
                    <td class="text-end">{{ number_format($order->payableItemsTotal()) }}</td>
                </tr>
                <tr>
                    <td class="text-muted">{{ l('هزینه ارسال دریافتی از مشتری') }}</td>
                    <td class="text-end">{{ number_format((int) $order->shipping_price) }}</td>
                </tr>
                <tr class="fw-bold">
                    <td>{{ l('جمع دریافتی') }}</td>
                    <td class="text-end">{{ number_format((int) $order->total_price) }}</td>
                </tr>
                <tr class="text-danger">
                    <td>{{ l('قیمت خرید اقلام') }}</td>
                    <td class="text-end">−{{ number_format($cost) }}</td>
                </tr>
                <tr class="text-danger">
                    <td>{{ l('هزینه‌های سفارش') }} ({{ $expenses->count() }})</td>
                    <td class="text-end">−{{ number_format($expTotal) }}</td>
                </tr>
                <tr class="fw-bold border-top" style="font-size:1.05rem;">
                    <td>{{ l('سود خالص') }}</td>
                    <td class="text-end {{ $profit >= 0 ? 'profit-pos' : 'profit-neg' }}">
                        {{ number_format($profit) }} {{ l('تومان') }}
                        @if((int) $order->total_price > 0)
                            <small class="text-muted">({{ $order->profitMargin() }}٪)</small>
                        @endif
                    </td>
                </tr>
            </table>
            @if(! $order->hasCompleteCosts())
                <div class="card-body border-top py-2 text-warning" style="font-size:.8rem;">
                    <i class="fa fa-exclamation-triangle me-1"></i>
                    {{ l('برای بعضی اقلام قیمت خرید ثبت نشده؛ سود برآوردی است. در جدول اقلام قیمت خرید را وارد کنید.') }}
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="fa fa-file-invoice-dollar me-1"></i> {{ l('هزینه‌های این سفارش') }}</span>
                <a href="/admin/finance" class="btn btn-sm btn-outline-secondary">{{ l('حسابداری') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ l('تاریخ') }}</th>
                        <th>{{ l('عنوان') }}</th>
                        <th>{{ l('دسته') }}</th>
                        <th class="text-end">{{ l('مبلغ') }}</th>
                        <th style="width:70px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($expenses->sortByDesc('occurred_on') as $expense)
                        <tr>
                            <td style="white-space:nowrap;">{{ toPersianDate($expense->occurred_on, false, true, 'Y/m/d') }}</td>
                            <td>{{ $expense->title }}
                                @if($expense->note)<div class="text-muted" style="font-size:.72rem;">{{ $expense->note }}</div>@endif
                            </td>
                            <td>{{ $expense->categoryLabel() }}</td>
                            <td class="text-end">{{ number_format($expense->amount) }}</td>
                            <td class="text-center">
                                <a href="/admin/finance/edit/{{ $expense->id }}" class="text-primary me-2" title="{{ l('ویرایش') }}"><i class="fa fa-edit"></i></a>
                                <form method="POST" action="/admin/finance/{{ $expense->id }}" class="d-inline" onsubmit="return confirm('{{ l('این هزینه حذف شود؟') }}')">
                                    @csrf @method('delete')
                                    <button class="btn btn-link p-0 text-danger" title="{{ l('حذف') }}"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">{{ l('هزینه‌ای برای این سفارش ثبت نشده (پست، بسته‌بندی، ...).') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{-- ثبت سریع هزینه بدون ترک صفحه --}}
            <div class="card-body border-top">
                <form method="POST" action="/admin/finance" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="type" value="expense">
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <input type="hidden" name="return" value="order">
                    <input type="hidden" name="occurred_on" value="{{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d') }}">
                    <div class="col-md-4">
                        <label class="form-label mb-1" style="font-size:.78rem;">{{ l('عنوان') }}</label>
                        <input type="text" name="title" class="form-control form-control-sm" required placeholder="{{ l('مثلا: هزینه پست پیشتاز') }}" value="{{ old('title') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1" style="font-size:.78rem;">{{ l('دسته') }}</label>
                        <select name="category" class="form-select form-select-sm">
                            @foreach($expenseCategories as $key => $label)
                                <option value="{{ $key }}" {{ old('category', 'shipping') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1" style="font-size:.78rem;">{{ l('مبلغ (تومان)') }}</label>
                        <input type="text" name="amount" class="form-control form-control-sm" required inputmode="numeric" dir="ltr" value="{{ old('amount') }}">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-sm btn-primary text-nowrap"><i class="fa fa-plus me-1"></i>{{ l('ثبت') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- پرداخت‌ها --}}
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-bold"><i class="fa fa-credit-card me-1"></i> {{ l('پرداخت‌ها') }}</span>
        <a href="/admin/payment/list?order_id={{ $order->id }}" class="btn btn-sm btn-outline-primary">
            {{ l('بررسی رسیدها') }}
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead class="table-primary">
            <tr>
                <th class="text-center">{{ l('تاریخ') }}</th>
                <th class="text-center">{{ l('روش') }}</th>
                <th class="text-center">{{ l('مبلغ') }}</th>
                <th class="text-center">{{ l('وضعیت') }}</th>
                <th class="text-center">{{ l('کد پیگیری') }}</th>
                <th class="text-center">{{ l('رسید') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($order->payments->sortByDesc('id') as $payment)
                <tr>
                    <td class="text-center">{{ $payment->created_at ? toPersianDate($payment->created_at) : '—' }}</td>
                    <td class="text-center">{{ $payment->methodLabel() }}</td>
                    <td class="text-center">{{ number_format((int) $payment->amount) }}</td>
                    <td class="text-center">
                        <span class="badge {{ $payment->statusBadgeClass() }}">{{ $payment->statusLabel() }}</span>
                        @if($payment->reviewer)
                            <div class="text-muted" style="font-size:.72rem;">
                                {{ l('بررسی') }}: {{ $payment->reviewer->name }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center"><bdi>{{ $payment->ref_id ?: $payment->reference ?: '—' }}</bdi></td>
                    <td class="text-center">
                        @if($payment->receiptUrl())
                            <a href="{{ $payment->receiptUrl() }}" target="_blank">{{ l('تصویر') }}</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">{{ l('برای این سفارش پرداختی ثبت نشده است.') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
