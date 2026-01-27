import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('teamSheet', () => ({
    dragging: false,
    dragStartY: 0,
    dragY: 0,
    closeThreshold: 120,

    async openSheet(userId) {
        this.sheetOpen = true
        this.loading = true
        this.detail = {}

        try {
            const res = await fetch(`/performance/team/${userId}`, {
                headers: { 'Accept': 'application/json' },
            })

            if (!res.ok) throw new Error('Failed to load')
            this.detail = await res.json()
        } catch (e) {
            this.detail = { name: 'Error', total_units: 0, orders: [] }
        } finally {
            this.loading = false
        }
    },

    closeSheet() {
        this.dragY = window.innerHeight;

        setTimeout(() => {
            this.sheetOpen = false;
            this.loading = false;
            this.detail = {};
            this.dragY = 0;
            this.dragging = false;
        }, 250);
    },

    dragStart(e) {
        if (!this.sheetOpen) return;

        this.dragging = true;
        this.dragStartY = this._getClientY(e);
        this.dragY = 0;

        this._onMove = (ev) => this.dragMove(ev);
        this._onEnd = () => this.dragEnd();

        window.addEventListener('mousemove', this._onMove);
        window.addEventListener('mouseup', this._onEnd);

        window.addEventListener('touchmove', this._onMove, { passive: false });
        window.addEventListener('touchend', this._onEnd);
    },

    dragMove(e) {
        if (!this.dragging) return;

        if (e.cancelable) e.preventDefault();

        const y = this._getClientY(e);
        const delta = y - this.dragStartY;

        this.dragY = Math.max(0, delta);
    },

    dragEnd() {
        if (!this.dragging) return;

        this.dragging = false;

        // unbind listeners
        window.removeEventListener('mousemove', this._onMove);
        window.removeEventListener('mouseup', this._onEnd);
        window.removeEventListener('touchmove', this._onMove);
        window.removeEventListener('touchend', this._onEnd);

        // kalau drag lewat threshold → close
        if (this.dragY > this.closeThreshold) {
            this.closeSheet();
        }

        // snap back
        this.dragY = 0;
    },

    _getClientY(e) {
        return e.touches && e.touches.length ? e.touches[0].clientY : e.clientY;
    },
}))

Alpine.start();
