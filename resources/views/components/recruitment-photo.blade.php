@props(['name', 'alt', 'sizes' => '(max-width: 800px) 100vw, 50vw'])
<figure {{ $attributes->merge(['class' => 'recruitment-photo']) }} data-recruitment-photo>
    <img src="{{ asset('images/recruitment/'.$name.'.webp') }}"
         srcset="{{ asset('images/recruitment/'.$name.'-768.webp') }} 768w, {{ asset('images/recruitment/'.$name.'.webp') }} 1536w"
         sizes="{{ $sizes }}" alt="{{ $alt }}" width="1536" height="1024" loading="lazy" decoding="async">
</figure>
