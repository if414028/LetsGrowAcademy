@props(['user', 'warning' => false])

<li class="dashboard-notice-user">
    <div class="dashboard-notice-user__identity">
        <div>
            <h4>{{ $user->name }}</h4>
            <p>{{ $user->email }}</p>
        </div>
        <span class="dashboard-notice-user__code"><span>DST</span> {{ $user->dst_code ?? '-' }}</span>
    </div>

    <dl class="dashboard-notice-user__details">
        <div @class(['dashboard-notice-user__manager' => $warning])>
            <dt>Health Manager</dt>
            <dd>{{ $user->health_manager_name ?? '-' }}</dd>
        </div>
        @if ($warning)
            <div>
                <dt>Aktivitas terakhir</dt>
                <dd>{{ $user->last_activity_at->translatedFormat('d M Y') }}</dd>
            </div>
            <div>
                <dt>Perkiraan nonaktif</dt>
                <dd class="dashboard-notice-user__date">{{ $user->deactivate_at->translatedFormat('d M Y') }}</dd>
            </div>
        @else
            <div>
                <dt>Role</dt>
                <dd>{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Tanggal nonaktif</dt>
                <dd>{{ $user->deactivated_at?->translatedFormat('d M Y') ?? 'Belum tercatat' }}</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>Inactive</dd>
            </div>
        @endif
    </dl>
</li>
