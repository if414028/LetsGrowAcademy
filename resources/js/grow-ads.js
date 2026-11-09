import gsap from 'gsap';

export function growAdSelection(ids = []) {
    return { selected: Array.isArray(ids) ? ids.map(Number) : [], busy: false };
}

export function growAdBudget(dailyBudget, days, config) {
    return {
        dailyBudget: Number(dailyBudget), days: Number(days), config, busy: false,
        get valid() {
            return Number.isInteger(this.dailyBudget) && this.dailyBudget >= config.min_daily_budget
                && this.dailyBudget <= config.max_daily_budget && Number.isInteger(this.days)
                && this.days >= 1 && this.days <= config.max_duration_days;
        },
        get adBudget() { return Math.max(0, Number(this.dailyBudget) || 0) * Math.max(0, Number(this.days) || 0); },
        get vat() { return Math.ceil(this.adBudget * config.vat_percent / 100); },
        get total() { return this.adBudget + this.vat + config.service_fee; },
        rupiah(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0); },
    };
}

export function growAdPayment(token) {
    return {
        busy: false, message: '',
        pay() {
            this.message = '';
            if (!window.snap) {
                this.message = 'Pembayaran belum dapat dibuka. Muat ulang halaman lalu coba lagi.';
                return;
            }
            this.busy = true;
            const verify = () => document.getElementById('ga-payment-sync')?.requestSubmit();
            try {
                window.snap.pay(token, {
                    onSuccess: verify, onPending: verify, onError: verify,
                    onClose: () => { this.busy = false; this.message = 'Kamu bisa melanjutkan pembayaran atau memeriksa statusnya di halaman ini.'; },
                });
            } catch {
                this.busy = false;
                this.message = 'Pembayaran belum dapat dibuka. Muat ulang halaman lalu coba lagi.';
            }
        },
    };
}

export function growAdCopy() {
    return {
        copied: false, message: '',
        async copy(input) {
            try {
                await navigator.clipboard.writeText(input.value);
                this.copied = true;
                this.message = 'Tautan tujuan iklan berhasil disalin.';
                window.setTimeout(() => { this.copied = false; }, 2500);
            } catch {
                input.focus(); input.select();
                this.message = 'Tautan sudah dipilih. Gunakan perintah salin pada perangkatmu.';
            }
        },
    };
}

export function initGrowAdMotion() {
    const page = document.querySelector('[data-grow-ads]');
    if (!page || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const art = page.querySelector('[data-ga-art]');
    if (art) {
        // Keep the illustration visible throughout scrolling; only animate its entrance.
        gsap.from(art, { y: 12, scale: 0.98, duration: 0.5, ease: 'power2.out', clearProps: 'transform' });
    }
}
