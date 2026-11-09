@if ($pixelId)
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
        (window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        const growAdPixelId = @js($pixelId);
        fbq('init', growAdPixelId);
        fbq('trackSingle', growAdPixelId, 'PageView');
        @if ($registration ?? false)
            fbq('trackSingle', growAdPixelId, 'CompleteRegistration');
        @endif
        @if ($purchase ?? null)
            fbq('trackSingle', growAdPixelId, 'Purchase', @js($purchase));
        @endif
        @isset($formClickUrl)
            const growAdFormClickUrl = @js($formClickUrl);
            const growAdCheckoutValue = @js($checkoutValue);
            document.addEventListener('click', (event) => {
                if (!event.target.closest?.('a.recruitment-button[href="#form-minat"]')) return;

                const form = document.querySelector('[data-grow-ad-registration]');
                if (!form) return;

                const payload = new URLSearchParams({
                    _token: form.elements.namedItem('_token').value,
                    grow_ad_token: form.elements.namedItem('grow_ad_token').value,
                });
                fetch(growAdFormClickUrl, { method: 'POST', body: payload, keepalive: true }).catch(() => {});
                fbq('trackSingle', growAdPixelId, 'AddPaymentInfo');
            });
            document.addEventListener('submit', (event) => {
                if (event.target.matches('[data-grow-ad-registration]')) {
                    fbq('trackSingle', growAdPixelId, 'AddToCart', { value: growAdCheckoutValue, currency: 'IDR' });
                }
            });
        @endisset
    </script>
@endif
