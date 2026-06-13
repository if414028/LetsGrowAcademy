<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fileName }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .toolbar button {
            border: 0;
            border-radius: 8px;
            background: #dc2626;
            color: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 14px;
        }

        .page {
            padding: 10mm;
        }

        h1 {
            margin: 0;
            text-align: center;
            font-size: 16px;
            line-height: 1.2;
        }

        .range {
            margin-top: 4px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .performance-table {
            margin-top: 14px;
        }

        th,
        td {
            border: 1px solid #64748b;
            padding: 4px 5px;
            vertical-align: top;
        }

        th {
            background: #bfdbfe;
            color: #0f172a;
            font-weight: 800;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .summary-title {
            margin-top: 18px;
            background: #1e3a8a;
            border: 1px solid #1e293b;
            color: white;
            font-size: 12px;
            font-weight: 800;
            padding: 6px;
            text-align: center;
        }

        .summary-table td {
            border-color: #94a3b8;
            font-weight: 800;
            padding: 5px;
            text-align: center;
        }

        .summary-key-in {
            background: #e5e7eb;
        }

        .summary-recurring {
            background: #bfdbfe;
        }

        .summary-dijadwalkan {
            background: #fde68a;
        }

        .summary-menunggu {
            background: #fcd34d;
        }

        .summary-pending {
            background: #d8b4fe;
        }

        .summary-install {
            background: #86efac;
        }

        @media print {
            .toolbar {
                display: none;
            }

            .page {
                padding: 10mm;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Save as PDF</button>
    </div>

    <main class="page">
        <h1>{{ $title }}</h1>
        <div class="range">Range: {{ $from }} s/d {{ $to }}</div>

        <table class="performance-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 16%;">Nama HP</th>
                    <th style="width: 14%;">Nama Customer</th>
                    <th style="width: 9%;">Tanggal Key in</th>
                    <th style="width: 8%;">Old Case</th>
                    <th style="width: 8%;">CCP disetujui</th>
                    <th style="width: 7%;">Key-in</th>
                    <th style="width: 8%;">Install/NS</th>
                    <th style="width: 9%;">Status</th>
                    <th style="width: 9%;">Tanggal Instalasi</th>
                    <th style="width: 8%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @forelse ($teamSheetRows as $hpName => $rows)
                    @foreach ($rows as $index => $r)
                        @php
                            $keyIn = $r->key_in_at ? \Carbon\Carbon::parse($r->key_in_at)->format('d-M') : '-';
                            $ccpAppr = $r->ccp_approved_at ? \Carbon\Carbon::parse($r->ccp_approved_at)->format('d-M') : '';
                            $installDate = $r->install_date ? \Carbon\Carbon::parse($r->install_date)->format('d-M') : '';
                            $ns = (int) $r->ns_units;
                        @endphp
                        <tr>
                            @if ($index === 0)
                                <td class="right" rowspan="{{ $rows->count() }}">{{ $no }}</td>
                                <td rowspan="{{ $rows->count() }}">{{ $hpName }}</td>
                            @endif
                            <td>{{ $r->customer_name }}</td>
                            <td class="center">{{ $keyIn }}</td>
                            <td class="center">{{ (int) $r->is_carry_over === 1 ? 'Old Case' : '-' }}</td>
                            <td class="center">{{ $ccpAppr ?: '' }}</td>
                            <td class="center">{{ $ns }}NS</td>
                            <td class="center">{{ ($r->status ?? '') === 'selesai' ? 'OK' : '' }}</td>
                            <td class="center">{{ $r->status ? \Illuminate\Support\Str::of($r->status)->replace('_', ' ')->title() : '-' }}</td>
                            <td class="center">{{ $installDate ?: '' }}</td>
                            <td class="center">{{ $r->remarks ?? '-' }}</td>
                        </tr>
                    @endforeach
                    @php $no++; @endphp
                @empty
                    <tr>
                        <td colspan="11" class="center">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary-title">Summary</div>
        <table class="summary-table">
            <tbody>
                <tr class="summary-key-in">
                    <td>Total Key-In</td>
                    <td>{{ (int) ($summary->total_key_in ?? 0) }}</td>
                </tr>
                <tr class="summary-recurring">
                    <td>Total Recurring</td>
                    <td>{{ (int) ($summary->total_recurring ?? 0) }}</td>
                </tr>
                <tr class="summary-dijadwalkan">
                    <td>Dijadwalkan</td>
                    <td>{{ (int) ($summary->dijadwalkan ?? 0) }}</td>
                </tr>
                <tr class="summary-menunggu">
                    <td>Menunggu Jadwal</td>
                    <td>{{ (int) ($summary->menunggu_jadwal ?? 0) }}</td>
                </tr>
                <tr class="summary-pending">
                    <td>Pending</td>
                    <td>{{ (int) ($summary->pending ?? 0) }}</td>
                </tr>
                <tr class="summary-install">
                    <td>Total sudah install (OK)</td>
                    <td>{{ (int) ($summary->total_sudah_install ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </main>
</body>

</html>
