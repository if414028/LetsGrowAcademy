<x-dashboard-layout>
    <section class="birthday-page" data-birthday-page
        x-data="birthdayGreetings(@js($customers), @js(old('customer_ids', $customers->where('selected', true)->pluck('id')->all()) ?? []), @js($sampleMessage))">
        <header class="birthday-page__header">
            <div>
                <p class="birthday-eyebrow">Hubungan baik, sepanjang tahun</p>
                <h1>Birthday Greetings</h1>
                <p class="birthday-page__description">Satu ucapan kecil. Perhatian yang berarti untuk customer Anda.</p>
            </div>
            <span class="birthday-status {{ $setting?->enabled ? 'birthday-status--on' : '' }}">
                <span aria-hidden="true"></span>{{ $setting?->enabled ? 'Fitur aktif' : 'Belum diaktifkan' }}
            </span>
        </header>

        @if (session('success'))
            <p class="birthday-feedback" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="birthday-feedback birthday-feedback--error" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        @if (!$setting?->enabled)
            <div class="birthday-intro">
                <div class="birthday-intro__art">@include('birthday-greetings.illustration')</div>
                <h2>Hadir di hari istimewa mereka.</h2>
                <p>Kirim ucapan selamat ulang tahun secara otomatis melalui WhatsApp. Tetap hangat dan personal, tanpa perlu mengingat setiap tanggalnya.</p>
                <div class="birthday-intro__actions">
                    @if ($profileReady)
                        <form action="{{ route('birthday-greetings.activate') }}" method="POST" x-data="{ busy: false }" @submit="busy = true">
                            @csrf
                            <button class="birthday-button" type="submit" :disabled="busy" x-text="busy ? 'Mengaktifkan…' : 'Aktifkan Birthday Greetings'">Aktifkan Birthday Greetings</button>
                        </form>
                    @else
                        <a class="birthday-button" href="{{ route('profile') }}">Lengkapi nomor WhatsApp Anda</a>
                    @endif
                    <a class="birthday-button birthday-button--subtle" href="#birthday-example">Lihat contoh pesan <span aria-hidden="true">↓</span></a>
                </div>
                <p class="birthday-intro__hint">Anda memilih sendiri penerimanya setelah mengaktifkan fitur ini.</p>
            </div>

            <div class="birthday-steps" aria-label="Cara kerja Birthday Greetings">
                @foreach ([
                    ['Pilih customer Anda', 'Tentukan siapa saja yang akan menerima ucapan. Hanya customer milik Anda yang tersedia.'],
                    ['Kami ingat tanggalnya', 'Ucapan dijadwalkan setiap tahun pukul 09.00 WIB, tepat di hari ulang tahun customer.'],
                    ['Terkirim atas nama Anda', 'Nomor admin mengirim ucapan dengan nama, jabatan HP/HM, dan nomor WhatsApp Anda.'],
                ] as [$title, $copy])
                    <details class="birthday-step" open>
                        <summary>{{ $title }}<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
                        <p>{{ $copy }}</p>
                    </details>
                @endforeach
            </div>

            <div id="birthday-example" class="birthday-example">
                <div class="birthday-example__copy">
                    <span class="birthday-eyebrow">Hangat, dengan sentuhan personal</span>
                    <h2>Ucapan sederhana,<br>langsung ke WhatsApp.</h2>
                    <p>Nama customer akan terisi otomatis. Identitas pengirim ucapan mengikuti data profil Anda sebagai {{ auth()->user()->hasRole('Health Manager') ? 'Health Manager' : 'Health Planner' }} Coway.</p>
                    <div class="birthday-sender"><span>Nomor pengirim</span><strong>{{ $sender }}</strong><span>Admin LetsGrowAcademy</span></div>
                    @if (!$gatewayReady)
                        <p class="birthday-service-note"><strong>Layanan WhatsApp sedang disiapkan.</strong> Anda dapat mengaktifkan fitur dan memilih penerima sekarang. Pesan mulai dikirim setelah layanan terhubung.</p>
                    @endif
                    <p class="birthday-small">Untuk customer yang lahir pada 29 Februari, ucapan dijadwalkan pada 28 Februari saat bukan tahun kabisat.</p>
                </div>
                @include('birthday-greetings.preview')
            </div>
        @else
            @if (!$gatewayReady || !$profileReady)
                <div class="birthday-service-banner" role="status">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <div><strong>{{ !$profileReady ? 'Lengkapi nomor WhatsApp Anda' : 'Menunggu layanan WhatsApp' }}</strong><p>{{ !$profileReady ? 'Pengiriman dijeda sampai nomor WhatsApp pada profil Anda valid.' : 'Pilihan customer tetap tersimpan. Pengiriman otomatis mulai berjalan setelah nomor admin terhubung.' }}</p></div>
                    @if (!$profileReady)<a href="{{ route('profile') }}" class="birthday-text-button">Buka profil</a>@endif
                </div>
            @endif

            <div class="birthday-workspace">
                <div class="birthday-recipients">
                    <div class="birthday-recipients__heading">
                        <div><h2 x-text="editing ? 'Pilih customer penerima' : 'Customer penerima'">Customer penerima</h2><p><strong x-text="selected.length">0</strong> customer dipilih <span aria-hidden="true">·</span> Setiap tahun, 09.00 WIB</p></div>
                        <button x-ref="editButton" type="button" class="birthday-button birthday-button--secondary" x-show="!editing" @click="startEditing()">Ubah penerima</button>
                    </div>
                    <form action="{{ route('birthday-greetings.update') }}" method="POST" @submit="saving = true">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="customer_ids" value="">
                        <template x-for="id in selected" :key="id"><input type="hidden" name="customer_ids[]" :value="id"></template>
                        <div class="birthday-list-tools">
                            <label class="birthday-search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg><span class="sr-only">Cari nama atau nomor customer</span><input x-ref="search" type="search" x-model="query" placeholder="Cari nama atau nomor customer"></label>
                            <div class="birthday-selection-tools" x-show="editing" x-cloak><button type="button" class="birthday-text-button" @click="selectVisible()">Pilih semua hasil</button><button type="button" class="birthday-text-button" @click="clearVisible()">Hapus pilihan hasil</button></div>
                        </div>
                        <p class="birthday-list-hint" x-show="editing" x-cloak>Hanya customer milik Anda. Tanggal lahir dan nomor WhatsApp harus lengkap untuk dipilih.</p>
                        <div class="birthday-customer-list" aria-label="Daftar customer">
                            <template x-for="customer in visibleCustomers" :key="customer.id">
                                <div class="birthday-customer" :class="{ 'is-selected': selected.includes(customer.id), 'is-unavailable': !customer.eligible }">
                                    <label class="birthday-customer__choice" x-show="editing"><input type="checkbox" :value="customer.id" :checked="selected.includes(customer.id)" :disabled="!customer.eligible && !selected.includes(customer.id)" @change="toggle(customer.id)" :aria-label="'Pilih ' + customer.name"></label>
                                    <span class="birthday-customer__avatar" x-text="initials(customer.name)" aria-hidden="true"></span>
                                    <div class="birthday-customer__info"><strong x-text="customer.name"></strong><span x-text="customer.phone || 'Nomor belum diisi'"></span><span class="birthday-customer__reason" x-show="!customer.eligible" x-text="customer.reason"></span></div>
                                    <div class="birthday-customer__date"><strong x-text="customer.birthday || 'Belum diisi'"></strong><span x-text="customer.eligible ? 'Ulang tahun' : 'Data belum lengkap'"></span></div>
                                    <button type="button" class="birthday-preview-button" @click="preview(customer)" :aria-label="'Lihat contoh pesan untuk ' + customer.name" :aria-pressed="previewId === customer.id">Lihat pesan</button>
                                </div>
                            </template>
                            <div class="birthday-empty" x-show="visibleCustomers.length === 0">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-11 5h4"/></svg>
                                <h3 x-text="query ? 'Customer tidak ditemukan' : (editing ? 'Belum ada customer' : 'Belum ada penerima dipilih')"></h3>
                                <p x-text="query ? 'Coba nama atau nomor yang lain.' : (editing ? 'Tambahkan customer milik Anda terlebih dahulu.' : 'Pilih customer untuk mulai menyiapkan ucapan otomatis.')"></p>
                                <button x-show="!query && !editing && customers.length > 0" type="button" class="birthday-button" @click="startEditing()">Pilih customer</button>
                                <a x-show="!query && customers.length === 0" class="birthday-button birthday-button--secondary" href="{{ route('customers.create') }}">Tambah customer</a>
                            </div>
                        </div>
                        <div class="birthday-save-bar" x-show="editing" x-cloak>
                            <p aria-live="polite"><strong x-text="selected.length"></strong> customer dipilih</p>
                            <div><button type="button" class="birthday-button birthday-button--subtle" @click="cancelEditing()" :disabled="saving">Batal</button><button type="submit" class="birthday-button" :disabled="saving" x-text="saving ? 'Menyimpan…' : 'Simpan penerima'">Simpan penerima</button></div>
                        </div>
                    </form>
                    <p class="birthday-small birthday-recipients__footnote">Customer yang belum lengkap datanya atau berpindah pemilik tidak akan dikirimi ucapan. Customer baru perlu dipilih secara manual.</p>
                </div>
                <div x-ref="previewPanel" tabindex="-1" role="region" aria-label="Contoh pesan untuk customer">@include('birthday-greetings.preview')</div>
            </div>
            <footer class="birthday-settings-footer">
                <div><strong>Anda tetap memegang kendali.</strong><p>Nonaktifkan kapan saja untuk menghentikan pengiriman. Pilihan customer tetap tersimpan.</p></div>
                <form action="{{ route('birthday-greetings.deactivate') }}" method="POST">@csrf<button type="submit" class="birthday-button birthday-button--secondary">Nonaktifkan fitur</button></form>
            </footer>
        @endif
    </section>
</x-dashboard-layout>
