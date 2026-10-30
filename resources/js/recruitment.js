import gsap from 'gsap';

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
