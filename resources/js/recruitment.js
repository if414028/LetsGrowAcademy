import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

export function initRecruitmentLanding() {
    const page = document.querySelector('[data-recruitment-landing]');
    if (!page) return;

    const media = gsap.matchMedia();
    media.add('(prefers-reduced-motion: no-preference)', () => {
        page.querySelectorAll('[data-recruitment-reveal]').forEach((block) => {
            gsap.from(block, {
                y: 16,
                opacity: 0,
                duration: 0.45,
                ease: 'power2.out',
                clearProps: 'all',
                scrollTrigger: { trigger: block, start: 'top 94%', once: true },
            });
        });

        page.querySelectorAll('[data-recruitment-photo] img').forEach((photo) => {
            gsap.fromTo(photo, { scale: 1.045 }, {
                scale: 1,
                ease: 'none',
                scrollTrigger: { trigger: photo, start: 'top bottom', end: 'center center', scrub: 0.35 },
            });
        });
    });

    media.add('(min-width: 1100px) and (min-height: 820px) and (prefers-reduced-motion: no-preference)', () => {
        const intro = page.querySelector('[data-recruitment-pin]');
        const layout = intro?.closest('.recruitment-learning-layout');
        const pinTop = 128;

        if (intro && layout && intro.offsetHeight < window.innerHeight - pinTop - 24
            && layout.offsetHeight > intro.offsetHeight + 64) {
            ScrollTrigger.create({
                trigger: layout,
                start: `top ${pinTop}px`,
                end: () => `+=${Math.max(0, layout.offsetHeight - intro.offsetHeight)}`,
                pin: intro,
                pinSpacing: false,
                invalidateOnRefresh: true,
            });
        }

        const stack = page.querySelector('[data-recruitment-stack]');
        const cards = stack ? [...stack.querySelectorAll('article')] : [];
        if (cards.some((card) => card.offsetHeight > window.innerHeight - pinTop - 100)) return;

        cards.forEach((card, index) => {
            gsap.set(card, { zIndex: index + 1 });
            if (index === cards.length - 1) return;

            gsap.to(card, {
                scale: 1 - (cards.length - index - 1) * 0.018,
                ease: 'none',
                scrollTrigger: {
                    trigger: card,
                    start: `top ${pinTop + index * 20}px`,
                    endTrigger: stack,
                    end: () => `bottom ${pinTop + (cards.length - 1) * 20 + card.offsetHeight}px`,
                    pin: true,
                    pinSpacing: false,
                    scrub: true,
                    invalidateOnRefresh: true,
                },
            });
        });
    });
}

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
