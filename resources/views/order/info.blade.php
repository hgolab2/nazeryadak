@extends('layout.layout', ['title' => 'اطلاعات حساب | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">اطلاعات حساب</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            @include('layout.sidebar', ['menu' => 'info'])
            <div class="col-lg-9">
                <div class="cart-content p-4 personal-info">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-4 border-bottom">
                        <i class="fas fa-user-edit" style="color:var(--primary);"></i>
                        <h6 class="mb-0 font-13 fw-bold">ویرایش اطلاعات شخصی</h6>
                    </div>
                    <form method="POST" action="{{ route('customer.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <h6 class="font-13 fw-bold mb-3" style="color:var(--primary);"><i class="fas fa-id-card me-1"></i> مشخصات فردی</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-12">نام</label>
                                <input type="text" class="form-control" name="first_name" value="{{ $customer->first_name }}" style="border-radius:var(--radius-sm);">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-12">نام خانوادگی</label>
                                <input type="text" class="form-control" name="last_name" value="{{ $customer->last_name }}" style="border-radius:var(--radius-sm);">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-12">تلفن دریافت‌کننده</label>
                                <input type="text" class="form-control" name="receiver_phone" value="{{ $customer->address?->receiver_phone }}" style="border-radius:var(--radius-sm);">
                            </div>
                        </div>

                        <h6 class="font-13 fw-bold mb-3 mt-3" style="color:var(--primary);"><i class="fas fa-map-marker-alt me-1"></i> آدرس تحویل</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-12">استان</label>
                                <select name="province_id" class="form-select" style="border-radius:var(--radius-sm);" required>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province->id }}" {{ optional($customer->address)->province_id == $province->id ? "selected" : '' }}>{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-12">شهر</label>
                                <input type="text" name="city" value="{{ $customer->address?->city }}" class="form-control" style="border-radius:var(--radius-sm);" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-12">کد پستی</label>
                                <input type="text" name="postal_code" value="{{ $customer->address?->postal_code }}" class="form-control" style="border-radius:var(--radius-sm);">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label font-12">آدرس کامل</label>
                                <textarea name="address_line" class="form-control" rows="3" style="border-radius:var(--radius-sm);" required>{{ $customer->address?->address_line }}</textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-info px-4 font-13">
                                <i class="fas fa-check me-1"></i> ذخیره اطلاعات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
