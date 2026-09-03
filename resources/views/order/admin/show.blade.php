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
        <span class="badge {{ $order->statusBadgeClass() }} align-middle ms-2">{{ $order->status() }}</span>
    </h1>
    <div class="d-flex gap-2 flex-wrap">
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
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">{{ l('وضعیت') }}</span>
                    <span class="badge {{ $order->statusBadgeClass() }}">{{ $order->status() }}</span>
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
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">{{ l('این سفارش هیچ قلمی ندارد.') }}</td>
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
