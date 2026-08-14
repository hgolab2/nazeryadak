<div class="border rounded p-3 bg-secondary mb-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <p class="m-0">
            <b>{{ l('تعداد نتایج') }}:</b>
            {{ $totalCount }}
        </p>

        {{-- چاپ گروهی برچسب پستی سفارش‌های تیک‌خورده --}}
        <button type="button" class="btn btn-sm btn-outline-dark ms-auto" onclick="printSelectedLabels()">
            <i class="fa fa-tag me-1"></i>
            {{ l('چاپ برچسب پستی انتخاب‌شده‌ها') }}
            <span class="badge bg-dark ms-1" id="label-selected-count">0</span>
        </button>
    </div>
</div>

<div class="table-p">
    <table class="table table-bordered table-striped table-hover">
        <thead class="table-primary">
        <tr>
            <th class="text-center" style="width:38px;">
                <input type="checkbox" class="form-check-input" id="label-check-all"
                       title="{{ l('انتخاب همه') }}">
            </th>
            <th class="text-center">{{ l('ابزار') }}</th>
            <th class="text-center">{{ l('کد سفارش') }}</th>
            <th class="text-center">{{ l('مشتری') }}</th>
            <th class="text-center">{{ l('تعداد اقلام') }}</th>
            <th class="text-center">{{ l('مبلغ نهایی') }}</th>
            <th class="text-center">{{ l('وضعیت') }}</th>
            <th class="text-center">{{ l('تاریخ') }}</th>
        </tr>
        </thead>
        <tbody>

        @forelse($model as $order)
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input label-check"
                           value="{{ $order->id }}">
                </td>

                {{-- ابزار --}}
                <td class="text-center">
                    <button class="btn btn-icon btn-light btn-xs rounded-circle shadow-sm"
                            type="button" data-bs-toggle="dropdown">
                        <i class="fi-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu my-1">
                        <li>
                            <a class="dropdown-item" href="/admin/order/show/{{ $order->id }}">
                                <i class="fa fa-eye me-2"></i>
                                {{ l('مشاهده') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/order/edit/{{ $order->id }}">
                                <i class="fa fa-edit me-2"></i>
                                {{ l('ویرایش') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/order/label/{{ $order->id }}" target="_blank">
                                <i class="fa fa-tag me-2"></i>
                                {{ l('برچسب پستی') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger"
                               onclick="return destroy({{ $order->id }})"
                               style="cursor:pointer">
                                <i class="fa fa-trash me-2"></i>
                                {{ l('حذف') }}
                            </a>
                        </li>
                    </ul>
                </td>

                <td class="text-center">{{ $order->id }}</td>

                <td class="text-center">
                    {{ $order->customer?->fullName() ?? '—' }}
                </td>

                <td class="text-center">
                    {{ $order->items->sum('quantity') }}
                </td>

                <td class="text-center">
                {{ number_format((int) $order->total_price) }}
                @if($order->hasDiscount())
                    {{-- مدیر باید بفهمد این مبلغ چرا از جمع اقلام کمتر است --}}
                    <div class="text-success" style="font-size:0.72rem;">
                        <i class="fas fa-tag"></i> {{ $order->discount_code }}
                        (−{{ number_format((int) $order->discount_amount) }})
                    </div>
                @endif
                </td>



                <td class="text-center">
                    <span class="badge bg-info">
                        {{ $order->status() }}
                    </span>
                </td>

                <td class="text-center">
                    {{$order->created_at != null ? toPersianDate($order->created_at) : ''}}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted">
                    {{ l('موردی یافت نشد') }}
                </td>
            </tr>
        @endforelse

        </tbody>
    </table>
</div>
