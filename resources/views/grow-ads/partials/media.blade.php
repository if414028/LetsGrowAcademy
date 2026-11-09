@if ($item->media_type === 'video')
    <video controls preload="metadata" playsinline aria-label="Pratinjau {{ $item->title }}" src="{{ $item->media_url }}"></video>
@else
    <img src="{{ $item->media_url }}" alt="{{ $item->title }}" loading="lazy">
@endif
