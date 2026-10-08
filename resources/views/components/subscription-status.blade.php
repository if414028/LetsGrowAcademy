@props(['subscription' => null, 'pendingSubscription' => null, 'expiringSoon' => false, 'showTime' => false])

<section class="subscription-status-card {{ $subscription ? 'is-active' : '' }}" aria-labelledby="subscription-status-heading">
    <div class="subscription-status-card__content">
        <span class="subscription-status-card__icon" aria-hidden="true">
            @if ($subscription)
                <x-diamond-icon class="h-7 w-7" />
            @elseif ($pendingSubscription)
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            @else
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="11" rx="3"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" stroke-linecap="round"/>
                </svg>
            @endif
        </span>
        <div class="subscription-status-card__copy">
            <p class="subscription-status-card__label">Status subscription</p>
            @if ($subscription)
                <h2 id="subscription-status-heading" class="subscription-status-card__title">Subscriber aktif</h2>
                <p class="subscription-status-card__description">Akses premium aktif sampai <span class="subscription-status-card__date">{{ $subscription->ends_at->translatedFormat($showTime ? 'd F Y, H.i' : 'd F Y') }}{{ $showTime ? ' WIB' : '' }}</span>.</p>
                @if ($expiringSoon)
                    <p class="subscription-status-card__warning">
                        Masa aktif akan habis dalam {{ max(0, (int) ceil(now()->diffInDays($subscription->ends_at, false))) }} hari. Segera perpanjang subscription.
                    </p>
                @endif
            @elseif ($pendingSubscription)
                <h2 id="subscription-status-heading" class="subscription-status-card__title">Menunggu pengecekan admin</h2>
                <p class="subscription-status-card__description">Pembayaran kamu sedang diverifikasi secara manual.</p>
            @else
                <h2 id="subscription-status-heading" class="subscription-status-card__title">Belum berlangganan</h2>
                <p class="subscription-status-card__description">Berlangganan untuk membuka Selling Kit dan dokumen premium lainnya.</p>
            @endif
            {{ $details ?? '' }}
        </div>
    </div>
    @isset($actions)
        <div class="subscription-status-card__actions">{{ $actions }}</div>
    @else
        <a href="{{ route('subscriptions.index') }}" class="subscription-status-card__action">
            <span>{{ $subscription ? 'Kelola Subscription' : ($pendingSubscription ? 'Lihat Status' : 'Subscribe Sekarang') }}</span>
        </a>
    @endisset
</section>
