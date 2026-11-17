export default (config) => ({
    paymentMethod: config.selectedMethod,
    paymentError: '',
    get quote() {
        return config.locked ? config.lockedQuote : config.quotes[this.paymentMethod] ?? null;
    },
    get additionalFee() {
        return config.locked ? config.additionalFee : (this.quote?.transaction_fee ?? 0) + (this.quote?.gateway_vat_amount ?? 0);
    },
    get total() {
        return config.classAmount + this.additionalFee;
    },
    money(value) {
        return 'Rp' + Number(value ?? 0).toLocaleString('id-ID');
    },
    init() {
        if (config.autoOpen) this.openSnap();
    },
    openSnap() {
        if (!config.snapToken || !window.snap) {
            this.paymentError = 'Pembayaran belum dapat dibuka. Muat ulang halaman lalu coba lagi.';
            return;
        }
        let returning = false;
        const verifyStatus = () => {
            if (returning) return;
            returning = true;
            window.location.assign(config.statusUrl);
        };
        window.snap.pay(config.snapToken, {
            uiMode: config.snapUiMode,
            onSuccess: verifyStatus,
            onPending: verifyStatus,
            onError: verifyStatus,
            onClose: verifyStatus,
        });
    },
});
