@props(['labels' => [], 'datasets' => [], 'perHealthManager' => false, 'trend' => 'weekly'])

@php
    $total = collect($datasets)->sum(fn ($dataset) => array_sum($dataset['data']));
    $range = match ($trend) { 'daily' => '30 hari terakhir', 'monthly' => '6 bulan terakhir', default => '8 minggu terakhir' };
@endphp

<section class="sales-trend" aria-labelledby="sales-trend-heading" data-sales-trend>
    <div class="sales-trend__header">
        <div>
            <h2 id="sales-trend-heading" class="sales-trend__heading">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4v16h16M7 14l4-4 4 2 5-7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Sales Trend
            </h2>
            <p class="sales-trend__total">{{ number_format($total, 0, ',', '.') }} <span>unit</span></p>
            <p id="sales-trend-description" class="sales-trend__description">Total penjualan selesai · {{ $range }}</p>
        </div>
        <div class="sales-trend__controls">
            <form method="GET" class="sales-trend__periods" aria-label="Periode Sales Trend">
                @foreach (request()->except('trend') as $key => $value)
                    @if (is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                @foreach (['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => '6 Bulan'] as $value => $label)
                    <button type="submit" name="trend" value="{{ $value }}" aria-pressed="{{ $trend === $value ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </form>
            @if ($perHealthManager && count($datasets) > 1)
                <select class="sales-trend__view" aria-label="Tampilan Sales Trend" data-trend-view>
                    <option value="total">Total penjualan</option>
                    <option value="managers">Per Health Manager</option>
                </select>
            @endif
        </div>
    </div>
    <div class="sales-trend__legend" data-trend-legend aria-label="Seri penjualan"></div>
    @if (empty($datasets))
        <p class="sales-trend__empty">Belum ada Health Manager aktif untuk ditampilkan.</p>
    @else
        <div class="sales-trend__plot">
            <canvas id="salesTrendChart" role="img" aria-label="Grafik tren unit penjualan, {{ $range }}" aria-describedby="sales-trend-description">Data lengkap tersedia pada tabel di bawah grafik.</canvas>
        </div>
        <p class="sales-trend__error" data-trend-error hidden>Grafik belum dapat dimuat. Lihat angka penjualan pada tabel di bawah.</p>
        @if ($total === 0)
            <p class="sales-trend__zero">Belum ada penjualan selesai dalam periode ini.</p>
        @endif
    @endif
    <details class="sales-trend__data">
        <summary>Lihat data penjualan</summary>
        <div class="sales-trend__table-scroll" tabindex="0" role="region" aria-label="Tabel data Sales Trend">
            <table>
                <caption class="sr-only">Unit penjualan selesai · {{ $range }}</caption>
                <thead><tr><th scope="col">Periode</th>@foreach ($datasets as $dataset)<th scope="col">{{ $dataset['label'] }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($labels as $index => $label)
                        <tr><th scope="row">{{ $label }}</th>@foreach ($datasets as $dataset)<td>{{ number_format($dataset['data'][$index] ?? 0, 0, ',', '.') }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
    <script type="application/json" data-trend-config>@json(['labels' => $labels, 'datasets' => $datasets])</script>
</section>
