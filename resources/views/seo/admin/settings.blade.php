@extends('layout.managmentLayout', [
    'title' => 'تنظیمات سئو',
    'menu' => 'seo/settings',
])

@section('main_content')
<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">تنظیمات سئو</li>
    </ol>
</nav>

<h1 class="h4 mb-3">تنظیمات سئو</h1>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="alert alert-light border">
    این مقادیر بر <code>config/seo.php</code> و فایل <code>.env</code> اولویت دارند.
    هر فیلد را خالی بگذارید، همان مقدار فایل کانفیگ استفاده می‌شود — مقدار فعلی به‌عنوان راهنما داخل هر فیلد نشان داده شده است.
</div>

<form method="POST" action="/admin/seo/settings">
    @csrf
    @method('put')

    @foreach($groups as $groupTitle => $fields)
    <section class="card card-body shadow-sm p-4 mb-4">
        <h6 class="border-bottom pb-2 mb-3">{{ $groupTitle }}</h6>
        <div class="row">
            @foreach($fields as $key => $field)
                @php $formKey = str_replace('.', '_', $key); @endphp
                <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }} mb-3">
                    <label class="form-label">{{ $field['label'] }}</label>
                    @if($field['type'] === 'textarea')
                        <textarea name="seo[{{ $formKey }}]" rows="3" class="form-control"
                                  placeholder="{{ $values[$key]['fallback'] }}">{{ $values[$key]['saved'] }}</textarea>
                    @else
                        <input type="text" name="seo[{{ $formKey }}]" class="form-control"
                               @if(!empty($field['ltr'])) dir="ltr" @endif
                               value="{{ $values[$key]['saved'] }}"
                               placeholder="{{ $values[$key]['fallback'] ?: 'تنظیم نشده' }}">
                    @endif
                </div>
            @endforeach
        </div>
    </section>
    @endforeach

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary btn-lg">ذخیره تنظیمات</button>
    </div>
</form>
@endsection
