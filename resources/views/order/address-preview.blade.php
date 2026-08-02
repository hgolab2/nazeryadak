@if($address)
<div class="row font-13 mt-2" style="line-height:2.2;">
    <div class="col-sm-6">
        <p class="mb-1"><i class="fas fa-user me-2 text-muted" style="width:16px;"></i> گیرنده: <strong>{{ $address->receiver_name }}</strong></p>
        <p class="mb-1"><i class="fas fa-phone me-2 text-muted" style="width:16px;"></i> تلفن: <strong>{{ $address->receiver_phone ?? '—' }}</strong></p>
    </div>
    <div class="col-sm-6">
        <p class="mb-1"><i class="fas fa-mail-bulk me-2 text-muted" style="width:16px;"></i> کد پستی: {{ $address->postal_code ?? '—' }}</p>
    </div>
    <div class="col-12">
        <p class="mb-0 mt-1"><i class="fas fa-map-marker-alt me-2 text-muted" style="width:16px;"></i>
            {{ $address->province?->name }} — {{ $address->city }} — {{ $address->address_line }}
        </p>
    </div>
</div>
@else
<div class="text-center py-3">
    <i class="fas fa-map-marker-alt mb-2" style="font-size:1.5rem; color:var(--border-color);"></i>
    <p class="font-13 text-muted mb-0">هنوز آدرسی ثبت نشده. لطفاً آدرس تحویل را ثبت کنید.</p>
</div>
@endif
