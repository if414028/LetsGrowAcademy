@php($rupiah = fn ($value) => 'Rp'.number_format($value ?? 0, 0, ',', '.'))
<dl class="ga-cost">
    <div><dt>Budget iklan <small>{{ $item['duration_days'] }} hari × {{ $rupiah($item['daily_budget']) }}</small></dt><dd>{{ $rupiah($item['ad_budget']) }}</dd></div>
    <div><dt>PPN {{ $item['vat_percent'] }}%</dt><dd>{{ $rupiah($item['vat_amount']) }}</dd></div>
    <div><dt>Biaya layanan aplikasi</dt><dd>{{ $rupiah($item['service_fee']) }}</dd></div>
    <div class="ga-cost-total"><dt>Total pembayaran</dt><dd>{{ $rupiah($item['total_amount']) }}</dd></div>
</dl>
