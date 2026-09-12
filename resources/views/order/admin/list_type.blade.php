{{-- نوار خلاصه‌ی بالای جدول: تعداد نتایج + اقدام گروهی --}}
<div class="orders-toolbar d-flex align-items-center gap-2 flex-wrap mb-2">
    <span class="orders-count">
        <i class="fa fa-list-ul text-muted me-1"></i>
        {{ l('تعداد نتایج') }}:
        <b>{{ number_format($totalCount) }}</b>
    </span>

    {{-- چاپ گروهی برچسب پستی سفارش‌های تیک‌خورده --}}
    <button type="button" class="btn btn-sm btn-outline-dark ms-auto" onclick="printSelectedLabels()">
        <i class="fa fa-tag me-1"></i>
        {{ l('چاپ برچسب پستی انتخاب‌شده‌ها') }}
        <span class="badge bg-dark ms-1" id="label-selected-count">0</span>
    </button>
</div>

<div class="orders-table-wrap">
    <table class="table orders-table align-middle mb-0">
        <thead>
        <tr>
            <th class="text-center col-check">
                <input type="checkbox" class="form-check-input" id="label-check-all"
                       title="{{ l('انتخاب همه') }}">
            </th>
            <th class="text-center col-id">{{ l('کد') }}</th>
            <th>{{ l('مشتری') }}</th>
            <th class="text-center col-qty">{{ l('اقلام') }}</th>
            <th class="text-center col-price">{{ l('مبلغ پرداختی') }}</th>
            <th class="text-center col-profit">{{ l('سود') }}</th>
            <th class="text-center col-status">{{ l('وضعیت') }}</th>
            <th class="text-center col-date">{{ l('تاریخ') }}</th>
            <th class="text-center col-tools">{{ l('ابزار') }}</th>
        </tr>
        </thead>
        <tbody>

        @forelse($model as $order)
            <tr>
                <td class="text-center col-check">
                    <input type="checkbox" class="form-check-input label-check"
                           value="{{ $order->id }}">
                </td>

                <td class="text-center col-id" data-label="{{ l('کد سفارش') }}">
                    <a href="/admin/order/show/{{ $order->id }}" class="fw-bold text-decoration-none">
                        {{ $order->id }}
                    </a>
                </td>

                <td data-label="{{ l('مشتری') }}">
                    {{-- نام مشتری هم به صفحه‌ی سفارش می‌رود؛ قبلا فقط شماره و منوی ابزار لینک داشتند --}}
                    <a href="/admin/order/show/{{ $order->id }}" class="d-block" style="color:inherit;">{{ $order->customer?->fullName() ?: '—' }}</a>
                    @if($order->customer?->phone)
                        <span class="cell-sub" dir="ltr">{{ $order->customer->phone }}</span>
                    @endif
                </td>

                <td class="text-center col-qty" data-label="{{ l('تعداد اقلام') }}">
                    {{ $order->items->sum('quantity') }}
                </td>

                <td class="text-center col-price" data-label="{{ l('مبلغ پرداختی') }}">
                    <b>{{ number_format((int) $order->total_price) }}</b>
                    @if($order->hasDiscount())
                        {{-- مدیر باید بفهمد این مبلغ چرا از جمع اقلام کمتر است --}}
                        <span class="cell-sub text-success">
                            <i class="fas fa-tag"></i> {{ $order->discount_code }}
                            (−{{ number_format((int) $order->discount_amount) }})
                        </span>
                    @endif
                    @if((int) $order->shipping_price > 0)
                        <span class="cell-sub text-muted">
                            {{ l('اقلام') }}: {{ number_format((int) $order->final_price) }}
                            + {{ l('ارسال') }}: {{ number_format((int) $order->shipping_price) }}
                        </span>
                    @endif
                </td>

                <td class="text-center col-profit" data-label="{{ l('سود') }}">
                    @php $profit = $order->netProfit(); @endphp
                    <span class="{{ $profit >= 0 ? 'profit-pos' : 'profit-neg' }} fw-bold">{{ number_format($profit) }}</span>
                    @if(! $order->hasCompleteCosts())
                        {{-- قلمی بدون قیمت خرید دارد؛ عدد برآوردی است --}}
                        <span class="cell-sub text-muted" title="{{ l('قیمت خرید بعضی اقلام ثبت نشده') }}">{{ l('برآوردی') }}</span>
                    @elseif((int) $order->total_price > 0)
                        <span class="cell-sub text-muted">{{ $order->profitMargin() }}٪</span>
                    @endif
                </td>

                <td class="text-center col-status" data-label="{{ l('وضعیت') }}">
                    {{-- وضعیت همین‌جا عوض می‌شود؛ قبلا باید به صفحه‌ی ویرایش می‌رفتید --}}
                    @include('order.admin._status', ['order' => $order])
                    @if($order->pendingReceipt)
                        {{-- رسید دستیِ تعیین‌تکلیف‌نشده؛ کاری که روی زمین مانده --}}
                        <a href="/admin/payment/list?status=pending&order_id={{ $order->id }}"
                           class="badge bg-warning text-dark d-inline-block mt-1 text-decoration-none">
                            <i class="fa fa-receipt me-1"></i>{{ l('رسید در انتظار بررسی') }}
                        </a>
                    @endif
                </td>

                <td class="text-center col-date" data-label="{{ l('تاریخ') }}">
                    {{ $order->created_at != null ? toPersianDate($order->created_at) : '' }}
                </td>

                {{-- ابزار به انتهای ردیف رفت تا ستون‌های اطلاعاتی زودتر خوانده شوند --}}
                <td class="text-center col-tools" data-label="{{ l('ابزار') }}">
                    <button class="btn btn-icon btn-light btn-xs rounded-circle shadow-sm"
                            type="button" data-bs-toggle="dropdown">
                        <i class="fa fa-ellipsis-v"></i>
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
            </tr>
        @empty
            <tr class="row-empty">
                <td colspan="9" class="text-center text-muted py-4">
                    <i class="fa fa-inbox d-block mb-2 fs-4"></i>
                    {{ l('موردی یافت نشد') }}
                </td>
            </tr>
        @endforelse

        </tbody>
    </table>
</div>
