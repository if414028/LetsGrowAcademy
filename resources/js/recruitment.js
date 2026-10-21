export default () => ({
    copied: false,
    status: '',
    async copy() {
        try {
            await navigator.clipboard.writeText(this.$refs.link.value);
            this.copied = true;
            this.status = 'Link berhasil disalin. Siap dibagikan kepada calon mitra.';
        } catch {
            this.$refs.link.focus();
            this.$refs.link.select();
            this.status = 'Pilih dan salin link dari kolom di atas.';
        }
    },
});
