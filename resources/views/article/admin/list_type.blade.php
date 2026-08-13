<div class="border rounded p-3 bg-white mb-2">
    <b>تعداد نتایج:</b> {{ $totalCount }}
</div>

<div class="table-p">
    <table class="table table-bordered table-striped table-hover admin-table">
        <thead>
            <tr>
                <th class="text-center">ابزار</th>
                <th class="text-center">تصویر</th>
                <th class="text-center">کد</th>
                <th class="text-center">عنوان</th>
                <th class="text-center">تاریخ انتشار</th>
                <th class="text-center">وضعیت</th>
                <th class="text-center">نمایش سایت</th>
            </tr>
        </thead>
        <tbody>
        @forelse($model as $item)
            <tr>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <a class="btn btn-outline-primary" href="/admin/article/edit/{{ $item->articleid }}" title="ویرایش">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-outline-warning" onclick="toggleStatus({{ $item->articleid }})" title="تغییر وضعیت">
                            <i class="fas {{ $item->hidden ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="destroy({{ $item->articleid }})" title="حذف">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
                <td class="text-center">
                    @if($item->images)
                        <img src="{{ $item->images->getPath() }}" style="width:72px;height:54px;object-fit:cover;border-radius:6px;" alt="">
                    @else
                        <span class="text-muted">بدون تصویر</span>
                    @endif
                </td>
                <td class="text-center">{{ $item->articleid }}</td>
                <td>{{ $item->titr }}</td>
                <td class="text-center">{{ $item->showdate }}</td>
                <td class="text-center">
                    @if($item->hidden)
                        <span class="badge bg-secondary">مخفی</span>
                    @else
                        <span class="badge bg-success">منتشر شده</span>
                    @endif
                </td>
                <td class="text-center">
                    <a href="{{ $item->getUrl() }}" target="_blank" class="btn btn-sm btn-light">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">مطلبی ثبت نشده است.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
