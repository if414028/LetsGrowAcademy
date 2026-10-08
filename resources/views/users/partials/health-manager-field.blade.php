<div id="assigned_hm_field">
    <label for="health_manager_id" class="text-sm font-medium text-gray-700">Health Manager (khusus Health Planner)</label>
    <select name="health_manager_id" id="health_manager_id"
        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
        <option value="">Otomatis dari referrer</option>
        @if (($selectedHealthManager ?? null) && ! $healthManagerOptions->contains('id', $selectedHealthManager->id))
            <option value="{{ $selectedHealthManager->id }}" @selected((int) old('health_manager_id', $user->health_manager_id ?? null) === (int) $selectedHealthManager->id)>
                {{ $selectedHealthManager->name }} (penugasan lama — perlu dialihkan)
            </option>
        @endif
        @foreach ($healthManagerOptions as $manager)
            <option value="{{ $manager->id }}" @selected((int) old('health_manager_id', $user->health_manager_id ?? null) === (int) $manager->id)>
                {{ $manager->name }}{{ $manager->dst_code ? ' — '.$manager->dst_code : '' }}
            </option>
        @endforeach
    </select>
    <p class="mt-2 text-xs text-gray-500">Pilih HM yang menangani HP ini. Penugasan tim dan NS mengikuti HM tersebut; referrer tetap.</p>
    @error('health_manager_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<script>
    (() => {
        const field = document.getElementById('assigned_hm_field');
        const form = field.closest('form');
        const role = form.querySelector('select[name="role"]');
        const manager = field.querySelector('select');
        const update = () => {
            const isPlanner = role.value === 'Health Planner';
            field.classList.toggle('hidden', !isPlanner);
            manager.disabled = !isPlanner;
        };
        role.addEventListener('change', update);
        update();
    })();
</script>
