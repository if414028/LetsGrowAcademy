@if ($eventDetails['seminar_name'] || $eventDetails['batch_id'] || $eventDetails['batch_starts_at'])
    <dl class="recruitment-event-details" aria-label="Seminar dan jadwal">
        @if ($eventDetails['seminar_name'])<div><dt>Seminar</dt><dd>{{ $eventDetails['seminar_name'] }}</dd></div>@endif
        @if ($eventDetails['batch_id'])<div><dt>Batch</dt><dd>{{ $eventDetails['batch_id'] }}</dd></div>@endif
        @if ($eventDetails['batch_starts_at'])<div><dt>Jadwal</dt><dd>{{ $eventDetails['batch_starts_at']->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H:i') }} WIB</dd></div>@endif
    </dl>
@endif
