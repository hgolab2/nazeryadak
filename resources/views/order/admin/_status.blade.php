{{-- منوی کشویی تغییر وضعیت سفارش.
     با تغییر، همان‌جا ذخیره می‌شود (اسکریپت در layout.managmentLayout)؛ در
     لیست، داشبورد و صفحه‌ی مشاهده یکی است. --}}
<select class="order-status-select st-{{ $order->status }} {{ $class ?? '' }}"
        data-order="{{ $order->id }}" data-current="{{ $order->status }}"
        title="تغییر وضعیت سفارش #{{ $order->id }}">
    @foreach(\App\Models\Order::STATUSES as $key => $label)
        <option value="{{ $key }}" {{ (string) $order->status === $key ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
    @if(! isset(\App\Models\Order::STATUSES[(string) $order->status]))
        {{-- وضعیت قدیمی/ناشناخته (مثل draft) باید انتخاب‌شده بماند نه این‌که بی‌صدا عوض شود --}}
        <option value="{{ $order->status }}" selected>{{ $order->status() }}</option>
    @endif
</select>
