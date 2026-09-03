@extends('layout.managmentLayout', [
    'title' => !empty($model) ? 'ویرایش سفارش' : 'ثبت سفارش جدید',
])

@section('main_content')

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">{{ l('خانه') }}</a></li>
        <li class="breadcrumb-item active">
            {{ !empty($model) ? l('ویرایش سفارش') : l('ثبت سفارش جدید') }}
        </li>
    </ol>
</nav>

<div class="mb-4">
    <h2 class="h5 mb-0">
        {{ !empty($model) ? l('ویرایش سفارش') : l('ثبت سفارش جدید') }}
    </h2>
</div>

@if(isset($errors) && $errors->any())
{{-- بدون این بلوک، فرم بعد از خطای اعتبارسنجی بی‌هیچ پیامی برمی‌گشت و
     مدیر فکر می‌کرد ذخیره انجام شده است --}}
<div class="alert alert-danger">
    <p class="mb-2 fw-bold"><i class="fa fa-exclamation-triangle me-1"></i> ذخیره انجام نشد:</p>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST"
      action="{{ !empty($model) ? url('/admin/order/update/'.$model->id) : url('/admin/order/store') }}">
    @csrf
    @if(!empty($model)) @method('put') @endif

    <section class="card card-body shadow-sm p-4 mb-4">
        <div class="row">

            {{-- مشتری --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">{{ l('مشتری') }}</label>
                <select name="customer_id" class="form-control" required>
                    <option value="">{{ l('انتخاب مشتری') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}"
                            {{ ($model->customer_id ?? null) == $customer->id ? 'selected' : '' }}>
                            {{ $customer->fullName() }} ({{ $customer->phone }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- آدرس --}}
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">{{ l('آدرس ارسال') }}</label>
                <select name="address_id" class="form-control">
                    <option value="">{{ l('انتخاب آدرس') }}</option>
                    @if(!empty($addresses))
                        @foreach($addresses as $address)
                            {{-- CustomerAddress ستون title ندارد و همه‌ی
                                 گزینه‌ها «آدرس #x» می‌شدند؛ از روی خود آدرس
                                 نوشته می‌شود تا قابل تشخیص باشند --}}
                            <option value="{{ $address->id }}"
                                {{ old('address_id', $model->address_id ?? null) == $address->id ? 'selected' : '' }}>
                                {{ trim(implode('، ', array_filter([$address->city, $address->address_line]))) ?: 'آدرس #' . $address->id }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- روش ارسال --}}

            {{-- هزینه ارسال --}}
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">{{ l('هزینه ارسال') }}</label>
                <input type="number" name="shipping_price" class="form-control"
                       value="{{ old('shipping_price', $model->shipping_price ?? 0) }}">
            </div>

            {{-- وضعیت --}}
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">{{ l('وضعیت سفارش') }}</label>
                <select name="status" class="form-control" required>
                    @foreach(\App\Models\Order::STATUSES as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('status', $model->status ?? 'pending') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- مبلغ‌ها --}}
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">{{ l('جمع کل اقلام') }}</label>
                <input type="number" name="final_price" class="form-control" min="0" required
                       value="{{ old('final_price', $model->final_price ?? 0) }}">
                <div class="form-text">{{ l('مبلغ قطعات پیش از تخفیف و بدون هزینه ارسال') }}</div>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">{{ l('مبلغ پرداختی') }}</label>
                <input type="text" class="form-control bg-light"
                       value="{{ number_format((int) ($model->total_price ?? 0)) }}" readonly>
                {{-- دستی وارد نمی‌شود؛ هنگام ذخیره از «اقلام − تخفیف + ارسال»
                     ساخته می‌شود تا فاکتور با خودش ناسازگار نشود --}}
                <div class="form-text">{{ l('هنگام ذخیره از روی اقلام، تخفیف و هزینه ارسال محاسبه می‌شود') }}</div>
            </div>

            @if(!empty($model) && $model->hasDiscount())
            {{-- تخفیف فقط برای اطلاع مدیر است و از اینجا ویرایش نمی‌شود؛ عوض
                 کردنش یعنی دست بردن در فاکتوری که مشتری قبلا دیده است. --}}
            <div class="col-12 mb-3">
                <div class="alert alert-success mb-0 py-2">
                    <i class="fas fa-tag me-1"></i>
                    کد تخفیف <bdi class="fw-bold">{{ $model->discount_code }}</bdi> روی این سفارش اعمال شده است:
                    <b>{{ number_format((int) $model->discount_amount) }}</b> تومان.
                    مبلغ‌های بالا شامل همین کسر هستند.
                </div>
            </div>
            @endif

        </div>
    </section>

    <button type="submit" class="btn btn-primary btn-lg">
        {{ !empty($model) ? l('ویرایش سفارش') : l('ثبت سفارش') }}
    </button>

</form>
@endsection
