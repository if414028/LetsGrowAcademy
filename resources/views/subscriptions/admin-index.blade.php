<x-dashboard-layout>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-amber-600">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Verifikasi Subscription</h1>
            <p class="mt-2 text-sm text-slate-500">Periksa transfer manual lalu aktifkan akses user.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (['' => 'Semua', 'pending' => 'Menunggu', 'active' => 'Aktif', 'rejected' => 'Ditolak'] as $value => $label)
                <a href="{{ route('admin.subscriptions.index', $value ? ['status' => $value] : []) }}"
                    class="rounded-full px-4 py-2 text-sm font-bold {{ $status === $value ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if (session('success'))<div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">{{ session('error') }}</div>@endif

    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr><th class="px-5 py-4">User</th><th class="px-5 py-4">Paket</th><th class="px-5 py-4">Bukti transfer</th><th class="px-5 py-4">Diajukan</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Masa aktif</th><th class="px-5 py-4 text-right">Tindakan</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($subscriptions as $subscription)
                        <tr class="align-top">
                            <td class="px-5 py-4"><a href="{{ route('users.show', $subscription->user) }}" class="font-bold text-slate-900 hover:text-blue-600">{{ $subscription->user->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $subscription->user->email }}</p></td>
                            <td class="px-5 py-4"><p class="font-bold">{{ $subscription->duration_months }} bulan</p><p class="mt-1 text-xs text-slate-500">Rp {{ number_format($subscription->amount, 0, ',', '.') }}</p></td>
                            <td class="px-5 py-4">
                                @if ($subscription->payment_proof)
                                    @php
                                        $proofUrl = asset('storage/'.$subscription->payment_proof);
                                        $proofIsImage = in_array(strtolower(pathinfo($subscription->payment_proof, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']);
                                    @endphp
                                    <a href="{{ $proofUrl }}" target="_blank" rel="noopener" class="group block w-fit">
                                        @if ($proofIsImage)
                                            <img src="{{ $proofUrl }}" alt="Bukti transfer {{ $subscription->user->name }}" class="h-16 w-24 rounded-lg border border-slate-200 object-cover transition group-hover:opacity-80">
                                        @else
                                            <span class="inline-flex min-h-10 items-center rounded-xl border border-blue-200 bg-blue-50 px-3 text-xs font-bold text-blue-700 hover:bg-blue-100">Buka PDF</span>
                                        @endif
                                        <span class="mt-1 block text-xs font-semibold text-blue-600">Lihat bukti</span>
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Tidak tersedia</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ optional($subscription->submitted_at)->translatedFormat('d M Y, H.i') ?? '-' }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $subscription->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($subscription->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ ucfirst($subscription->status) }}</span></td>
                            <td class="px-5 py-4 text-xs text-slate-600">{{ $subscription->starts_at ? $subscription->starts_at->translatedFormat('d M Y').' – '.$subscription->ends_at->translatedFormat('d M Y') : '-' }}</td>
                            <td class="px-5 py-4">
                                @if ($subscription->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.subscriptions.approve', $subscription) }}">@csrf @method('PATCH')<button class="min-h-10 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500">Setujui</button></form>
                                        <form method="POST" action="{{ route('admin.subscriptions.reject', $subscription) }}" onsubmit="return confirm('Tolak permintaan subscription ini?')">@csrf @method('PATCH')<button class="min-h-10 rounded-xl border border-red-200 px-4 text-xs font-bold text-red-600 hover:bg-red-50 focus:ring-2 focus:ring-red-500">Tolak</button></form>
                                    </div>
                                @else
                                    <p class="text-right text-xs text-slate-400">{{ $subscription->reviewer?->name ?? '-' }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center text-slate-500">Belum ada data subscription.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($subscriptions->hasPages())<div class="border-t p-4">{{ $subscriptions->links() }}</div>@endif
    </div>
</x-dashboard-layout>
