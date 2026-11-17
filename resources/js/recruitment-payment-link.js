export default (ready = false) => ({
    copied: false,
    manualCopy: false,
    copyStatus: ready ? 'Link pembayaran siap disalin.' : '',
    async copy() {
        this.copied = false;
        try {
            await navigator.clipboard.writeText(this.$refs.paymentLink.value);
            this.copied = true;
            this.manualCopy = false;
            this.copyStatus = 'Link pembayaran berhasil disalin.';
        } catch {
            this.manualCopy = true;
            this.copyStatus = 'Pilih dan salin link dari kolom berikut.';
            this.$nextTick(() => {
                this.$refs.paymentLink.focus();
                this.$refs.paymentLink.select();
            });
        }
    },
});
