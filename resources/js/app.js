import './bootstrap';

import Alpine from 'alpinejs';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

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

const initDashboardMotion = () => {
    const root = document.querySelector('[data-dashboard-motion]');
    const scroller = document.querySelector('.dashboard-main');

    if (!root || !scroller || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const headingBlock = document.querySelector('.dashboard-content > :first-child');
    const contentBlocks = gsap.utils.toArray('.dashboard-content > section, .dashboard-content > form, .dashboard-content > div:not(:first-child)');

    if (headingBlock) {
        gsap.from(headingBlock, {
            y: 24,
            opacity: 0,
            duration: 0.75,
            ease: 'power3.out',
        });
    }

    contentBlocks.forEach((block, index) => {
        gsap.fromTo(block,
            { y: 36, opacity: 0.2, scale: 0.985 },
            {
                y: 0,
                opacity: 1,
                scale: 1,
                duration: 0.8,
                delay: Math.min(index * 0.035, 0.18),
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: block,
                    scroller,
                    start: 'top 92%',
                    end: 'top 62%',
                    scrub: 0.45,
                },
            });
    });

    const interactiveCards = root.querySelectorAll('main a[class*="rounded"], main button[class*="rounded"], main article');
    interactiveCards.forEach((card) => {
        card.addEventListener('pointermove', (event) => {
            if (event.pointerType === 'touch') return;
            const rect = card.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;
            gsap.to(card, { x: x * 3, y: y * 3, duration: 0.35, ease: 'power2.out' });
        });
        card.addEventListener('pointerleave', () => {
            gsap.to(card, { x: 0, y: 0, duration: 0.55, ease: 'elastic.out(1, 0.45)' });
        });
    });

    window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
};

const initPerformanceMotion = () => {
    const page = document.querySelector('[data-performance-page]');

    if (!page || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const sections = Array.from(page.children).filter((element) => element.tagName !== 'SCRIPT');

    gsap.from(sections, {
        y: 28,
        opacity: 0,
        duration: 0.8,
        stagger: 0.09,
        ease: 'power3.out',
        clearProps: 'transform,opacity',
    });
};

const initPublicMotion = () => {
    const page = document.querySelector('[data-public-motion]');

    if (!page || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const hero = page.querySelector('.public-hero');
    const heroCopy = page.querySelector('.public-hero-copy');

    if (hero && heroCopy) {
        const heroTimeline = gsap.timeline({ defaults: { ease: 'power3.out' } });
        heroTimeline
            .from('.public-floating-nav', { y: -22, opacity: 0, duration: 0.75 })
            .from(heroCopy.children, { y: 34, opacity: 0, duration: 0.85, stagger: 0.08 }, '-=0.35')
            .from(hero.querySelector('img'), { scale: 1.12, opacity: 0.65, duration: 1.4 }, '<');
    }

    const sections = gsap.utils.toArray('[data-public-motion] main > section:not(.public-hero)');
    sections.forEach((section) => {
        gsap.fromTo(section,
            { opacity: 0.35, y: 54 },
            {
                opacity: 1,
                y: 0,
                ease: 'none',
                scrollTrigger: {
                    trigger: section,
                    start: 'top 92%',
                    end: 'top 58%',
                    scrub: 0.55,
                },
            });

        section.querySelectorAll('img').forEach((image) => {
            gsap.fromTo(image,
                { scale: 0.88, opacity: 0.45 },
                {
                    scale: 1,
                    opacity: 1,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: image,
                        start: 'top 96%',
                        end: 'center 62%',
                        scrub: 0.45,
                    },
                });
        });
    });
};

const initAuthMotion = () => {
    const page = document.querySelector('[data-auth-motion]');

    if (!page || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const timeline = gsap.timeline({ defaults: { ease: 'power3.out' } });
    timeline
        .from('.auth-stage', { opacity: 0, scale: 0.985, duration: 0.7 })
        .from('.auth-navigation', { y: -18, opacity: 0, duration: 0.55 }, '-=0.35')
        .from('.auth-story-copy > *', { y: 28, opacity: 0, duration: 0.75, stagger: 0.1 }, '-=0.25')
        .from('.auth-card', { x: 36, opacity: 0, duration: 0.8 }, '-=0.65');
};

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', () => {
        initDashboardMotion();
        initPerformanceMotion();
        initPublicMotion();
        initAuthMotion();
    }, { once: true })
    : (() => {
        initDashboardMotion();
        initPerformanceMotion();
        initPublicMotion();
        initAuthMotion();
    })();
