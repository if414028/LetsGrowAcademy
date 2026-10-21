<aside class="birthday-preview" aria-label="Pratinjau pesan WhatsApp">
    <div class="birthday-preview__heading">
        <div>
            <h2>Pratinjau WhatsApp</h2>
            <p>Seperti ini ucapan diterima customer.</p>
        </div>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M21 11.5a9 9 0 0 1-13.4 7.8L3 21l1.7-4.6A9 9 0 1 1 21 11.5Z"/><path d="M9 7c-2 3 2 7 5 8l2-2-3-1-1 1-2-2 1-1-2-3Z"/></svg>
    </div>
    <div class="birthday-chat">
        <div class="birthday-chat__header">
            <span class="birthday-chat__avatar" aria-hidden="true">LG</span>
            <div><strong>LetsGrowAcademy</strong><span>{{ $sender }}</span></div>
        </div>
        <div class="birthday-chat__body">
            <span class="birthday-chat__day">Hari ulang tahun</span>
            <div class="birthday-chat__bubble">
                <p x-text="previewMessage">{{ $sampleMessage }}</p>
                <span class="birthday-chat__time">09.00</span>
            </div>
        </div>
    </div>
    <p class="birthday-preview__note">Dikirim dari nomor admin LetsGrowAcademy. Nama, jabatan, dan nomor Anda tercantum di dalam pesan.</p>
    @if ($setting?->enabled)
        <p class="birthday-preview__recipient" aria-live="polite">Contoh untuk <strong x-text="previewName">Nama Customer</strong></p>
    @endif
</aside>
