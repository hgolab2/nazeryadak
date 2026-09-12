@extends('layout.managmentLayout', [
    'title' => 'حسابداری',
    'menu'  => 'finance',
])

@section('main_content')
@php
    $profitClass = fn (int $value) => $value >= 0 ? 'profit-pos' : 'profit-neg';
    $totalExpenses = $report['cogs'] + $report['order_expenses'] + $report['general_expenses'];
@endphp
<style>
    .fin-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #eee; font-size:.85rem; }
    .fin-row:last-child { border-bottom:0; }
    .fin-row b { font-weight:700; }
    .trend-table td, .trend-table th { font-size:.8rem; white-space:nowrap; }
</style>

<nav class="mb-2 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/dashboardAdmin">داشبورد</a></li>
        <li class="breadcrumb-item active">حسابداری</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h1 class="h4 mb-0">حسابداری — {{ $monthLabel }}</h1>
    <div class="d-flex gap-2">
        <a href="/admin/finance/create?type=expense" class="btn btn-danger btn-sm"><i class="fa fa-minus me-1"></i> ثبت هزینه</a>
        <a href="/admin/finance/create?type=income" class="btn btn-success btn-sm"><i class="fa fa-plus me-1"></i> ثبت درآمد</a>
    </div>
</div>

{{-- انتخاب ماه و فیلتر --}}
<form method="GET" class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label fw-bold mb-1" style="font-size:.8rem;">ماه</label>
                <select class="form-select form-select-sm" onchange="const [y,m]=this.value.split('-'); this.form.year.value=y; this.form.month.value=m; this.form.submit();">
                    @foreach($monthOptions as $option)
                        <option value="{{ $option['year'] }}-{{ $option['month'] }}" {{ $option['year'] == $year && $option['month'] == $month ? 'selected' : '' }}>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-bold mb-1" style="font-size:.8rem;">نوع</label>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">همه</option>
                    @foreach(\App\Models\FinanceTransaction::TYPES as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-bold mb-1" style="font-size:.8rem;">دسته</label>
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">همه</option>
                    @foreach($categories as $type => $list)
                        <optgroup label="{{ \App\Models\FinanceTransaction::TYPES[$type] }}">
                            @foreach($list as $key => $label)
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</form>

{{-- خلاصه‌ی ماه --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e3f2fd; color:#1565c0;"><i class="fas fa-shopping-cart"></i></div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">{{ number_format($report['sales']) }}</div>
                <div class="stat-label">فروش سایت — {{ $report['orders'] }} سفارش / {{ $report['items'] }} قطعه</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff3e0; color:#e65100;"><i class="fas fa-boxes"></i></div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">{{ number_format($report['cogs']) }}</div>
                <div class="stat-label">قیمت خرید کالای فروخته‌شده</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fce4ec; color:#c62828;"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">{{ number_format($report['order_expenses'] + $report['general_expenses']) }}</div>
                <div class="stat-label">هزینه‌های ثبت‌شده — سفارش {{ number_format($report['order_expenses']) }} / عمومی {{ number_format($report['general_expenses']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e8f5e9; color:#2e7d32;"><i class="fas fa-chart-line"></i></div>
            <div>
                <div class="stat-value {{ $profitClass($report['net_profit']) }}" style="font-size:1.1rem;">{{ number_format($report['net_profit']) }}</div>
                <div class="stat-label">سود خالص ماه ({{ $report['margin'] }}٪)</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- صورت سود و زیان ماه --}}
    <div class="col-lg-4">
        <div class="admin-card h-100 mb-0">
            <div class="admin-card-title"><i class="fas fa-calculator"></i> صورت سود و زیان {{ $monthLabel }}</div>
            <div class="fin-row"><span>فروش سایت (تسویه‌شده)</span><b>{{ number_format($report['sales']) }}</b></div>
            @if($report['other_income'] > 0)
                <div class="fin-row"><span>سایر درآمدها</span><b>{{ number_format($report['other_income']) }}</b></div>
            @endif
            <div class="fin-row text-danger"><span>قیمت خرید کالا</span><b>−{{ number_format($report['cogs']) }}</b></div>
            <div class="fin-row"><span>سود ناخالص</span><b class="{{ $profitClass($report['gross_profit']) }}">{{ number_format($report['gross_profit']) }}</b></div>
            <div class="fin-row text-danger"><span>هزینه‌های سفارش‌ها (پست، بسته‌بندی)</span><b>−{{ number_format($report['order_expenses']) }}</b></div>
            <div class="fin-row text-danger"><span>هزینه‌های عمومی (سرور، پیامک، ...)</span><b>−{{ number_format($report['general_expenses']) }}</b></div>
            <div class="fin-row" style="font-size:1rem; border-top:2px solid #ddd; margin-top:4px; padding-top:10px;">
                <span class="fw-bold">سود خالص</span>
                <b class="{{ $profitClass($report['net_profit']) }}">{{ number_format($report['net_profit']) }} تومان</b>
            </div>

            @if($report['expenses_by_category'] !== [])
                <div class="mt-3" style="font-size:.78rem; color:#777;">هزینه‌ها به تفکیک دسته:</div>
                @foreach($report['expenses_by_category'] as $category => $amount)
                    <div class="fin-row" style="font-size:.78rem;"><span>{{ \App\Models\FinanceTransaction::categoryName($category) }}</span><span>{{ number_format($amount) }}</span></div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- روند ۶ ماهه --}}
    <div class="col-lg-5">
        <div class="admin-card h-100 mb-0">
            <div class="admin-card-title"><i class="fas fa-chart-bar"></i> روند ماه‌های اخیر</div>
            <div class="table-responsive">
                <table class="table table-sm trend-table mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ماه</th>
                        <th class="text-center">سفارش</th>
                        <th class="text-end">فروش</th>
                        <th class="text-end">هزینه‌ها</th>
                        <th class="text-end">سود خالص</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($trend as $row)
                        @php $r = $row['report']; @endphp
                        <tr class="{{ $row['year'] == $year && $row['month'] == $month ? 'table-primary' : '' }}">
                            <td><a href="/admin/finance?year={{ $row['year'] }}&month={{ $row['month'] }}">{{ $row['label'] }}</a></td>
                            <td class="text-center">{{ $r['orders'] }}</td>
                            <td class="text-end">{{ number_format($r['sales']) }}</td>
                            <td class="text-end">{{ number_format($r['cogs'] + $r['order_expenses'] + $r['general_expenses']) }}</td>
                            <td class="text-end {{ $profitClass($r['net_profit']) }}">{{ number_format($r['net_profit']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="fw-bold border-top">
                        <td>از ابتدا</td>
                        <td class="text-center">{{ $allTime['orders'] }}</td>
                        <td class="text-end">{{ number_format($allTime['sales']) }}</td>
                        <td class="text-end">{{ number_format($allTime['cogs'] + $allTime['order_expenses'] + $allTime['general_expenses']) }}</td>
                        <td class="text-end {{ $profitClass($allTime['net_profit']) }}">{{ number_format($allTime['net_profit']) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- پرفروش‌های ماه --}}
    <div class="col-lg-3">
        <div class="admin-card h-100 mb-0">
            <div class="admin-card-title"><i class="fas fa-trophy"></i> پرفروش‌های ماه</div>
            @forelse($topProducts as $i => $product)
                <div class="fin-row" style="font-size:.78rem;">
                    <span>{{ $i + 1 }}) {{ \Illuminate\Support\Str::limit($product['title'], 34) }}</span>
                    <span class="text-nowrap">{{ $product['qty'] }} عدد</span>
                </div>
            @empty
                <div class="text-muted" style="font-size:.8rem;">در این ماه فروشی ثبت نشده.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- فهرست تراکنش‌ها --}}
<div class="admin-card">
    <div class="admin-card-title">
        <i class="fas fa-list"></i> تراکنش‌های {{ $monthLabel }}
        <span class="text-muted" style="font-size:.75rem; font-weight:400;">({{ $transactions->total() }} مورد)</span>
        <span style="margin-right:auto; font-size:.72rem; color:#999; font-weight:400;">
            فروش سفارش‌ها خودکار از جدول سفارش‌ها خوانده می‌شود و این‌جا ثبت نمی‌شود.
        </span>
    </div>
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
            <tr>
                <th>تاریخ</th>
                <th>نوع</th>
                <th>دسته</th>
                <th>عنوان</th>
                <th>سفارش</th>
                <th class="text-end">مبلغ</th>
                <th>ثبت‌کننده</th>
                <th style="width:80px;"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td style="white-space:nowrap;">{{ toPersianDate($t->occurred_on, false, true, 'Y/m/d') }}</td>
                    <td>
                        <span class="badge {{ $t->isExpense() ? 'bg-danger' : 'bg-success' }}">{{ $t->typeLabel() }}</span>
                    </td>
                    <td>{{ $t->categoryLabel() }}</td>
                    <td>
                        {{ $t->title }}
                        @if($t->note)<div class="text-muted" style="font-size:.72rem;">{{ \Illuminate\Support\Str::limit($t->note, 80) }}</div>@endif
                    </td>
                    <td>
                        @if($t->order_id)
                            <a href="/admin/order/show/{{ $t->order_id }}">#{{ $t->order_id }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold {{ $t->isExpense() ? 'text-danger' : 'text-success' }}">
                        {{ $t->isExpense() ? '−' : '+' }}{{ number_format($t->amount) }}
                    </td>
                    <td style="font-size:.75rem; color:#777;">{{ $t->creator?->fullname() ?: '—' }}</td>
                    <td class="text-center" style="white-space:nowrap;">
                        <a href="/admin/finance/edit/{{ $t->id }}" class="text-primary me-2" title="ویرایش"><i class="fa fa-edit"></i></a>
                        <form method="POST" action="/admin/finance/{{ $t->id }}" class="d-inline" onsubmit="return confirm('این تراکنش حذف شود؟')">
                            @csrf @method('delete')
                            <button class="btn btn-link p-0 text-danger" title="حذف"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fa fa-inbox d-block mb-2 fs-4"></i>
                        در این ماه تراکنشی ثبت نشده. هزینه‌های ثابت (سرور، پیامک، دامنه) را از دکمه‌ی «ثبت هزینه» وارد کنید.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="mt-3">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
