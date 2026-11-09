@props(['subscription'])
@php($pricing = $subscription->pricing_details)
@if ($pricing)
    <dl class="space-y-2 text-xs leading-5 text-slate-600">
        <div class="flex justify-between gap-4"><dt>Metode</dt><dd class="text-right font-semibold">{{ $pricing['payment_method_label'] }}</dd></div>
        <div class="flex justify-between gap-4"><dt>Harga subscription</dt><dd class="shrink-0">Rp {{ number_format($pricing['subtotal'], 0, ',', '.') }}</dd></div>
        <div class="flex justify-between gap-4"><dt>Biaya transaksi</dt><dd class="shrink-0">Rp {{ number_format($pricing['transaction_fee'], 0, ',', '.') }}</dd></div>
        <div class="flex justify-between gap-4"><dt>PPN {{ $pricing['gateway_vat_percent'] }}% biaya transaksi</dt><dd class="shrink-0">Rp {{ number_format($pricing['gateway_vat_amount'], 0, ',', '.') }}</dd></div>
        <div class="flex justify-between gap-4 border-t border-slate-200 pt-2 font-bold text-slate-900"><dt>Total pembayaran</dt><dd class="shrink-0">Rp {{ number_format($subscription->amount, 0, ',', '.') }}</dd></div>
    </dl>
    @if ($pricing['merchant_covers_fee'])
        <p class="mt-2 text-xs leading-5 text-slate-500">Biaya QRIS dan PPN ditanggung merchant.</p>
    @endif
@endif
