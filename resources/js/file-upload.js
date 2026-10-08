export default function fileUpload({ maxSize, serverError = '' }) {
    return {
        dragging: false,
        dragDepth: 0,
        fileName: '',
        fileSize: '',
        previewUrl: '',
        error: serverError,

        init() {
            this.onFormReset = () => queueMicrotask(() => this.clear());
            this.$refs.input.form?.addEventListener('reset', this.onFormReset);
        },

        destroy() {
            this.$refs.input.form?.removeEventListener('reset', this.onFormReset);
            this.releasePreview();
        },

        dragEnter(event) {
            if (this.$refs.input.disabled || !Array.from(event.dataTransfer?.types || []).includes('Files')) return;
            this.dragDepth += 1;
            this.dragging = true;
        },

        dragOver(event) {
            if (event.dataTransfer) event.dataTransfer.dropEffect = this.$refs.input.disabled ? 'none' : 'copy';
        },

        dragLeave() {
            this.dragDepth = Math.max(0, this.dragDepth - 1);
            this.dragging = this.dragDepth > 0;
        },

        drop(event) {
            this.dragDepth = 0;
            this.dragging = false;
            if (this.$refs.input.disabled) return;

            const files = Array.from(event.dataTransfer?.files || []);
            if (files.length !== 1) {
                this.error = files.length > 1 ? 'Pilih satu file saja untuk field ini.' : 'Seret sebuah file, bukan folder.';
                return;
            }

            if (!this.validate(files[0])) return;

            // Put dropped files on the native input so the existing multipart form submits them.
            const transfer = new DataTransfer();
            transfer.items.add(files[0]);
            this.$refs.input.files = transfer.files;
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        },

        choose(event) {
            const file = event.target.files?.[0];
            if (!file) {
                this.clear();
                return;
            }
            if (!this.validate(file)) {
                const message = this.error;
                this.clear();
                this.error = message;
                return;
            }

            this.releasePreview();
            this.fileName = file.name;
            this.fileSize = file.size >= 1024 * 1024
                ? `${(file.size / (1024 * 1024)).toFixed(1)} MB`
                : `${Math.max(1, Math.ceil(file.size / 1024))} KB`;
            if (file.type.startsWith('image/') && file.type !== 'image/svg+xml') {
                this.previewUrl = URL.createObjectURL(file);
            }
        },

        validate(file) {
            const allowed = this.$refs.input.accept.toLowerCase().split(',').map((type) => type.trim()).filter(Boolean);
            const fileType = file.type.toLowerCase();
            const matches = !allowed.length || allowed.some((type) => {
                if (type.startsWith('.')) return file.name.toLowerCase().endsWith(type);
                if (type.endsWith('/*')) return fileType.startsWith(type.slice(0, -1));
                return fileType === type;
            });

            if (!matches) {
                this.error = 'Format file tidak didukung. Pilih format yang tercantum di atas.';
                return false;
            }
            if (file.size > maxSize) {
                this.error = `Ukuran file terlalu besar. Maksimal ${maxSize / (1024 * 1024)} MB.`;
                return false;
            }

            this.error = '';
            return true;
        },

        releasePreview() {
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = '';
        },

        clear() {
            this.$refs.input.value = '';
            this.fileName = '';
            this.fileSize = '';
            this.error = '';
            this.dragging = false;
            this.dragDepth = 0;
            this.releasePreview();
        },
    };
}
