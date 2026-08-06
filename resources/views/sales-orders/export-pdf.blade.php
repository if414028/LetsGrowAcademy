<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fileName }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 9px; }
        .toolbar { display: flex; justify-content: flex-end; padding: 12px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
        .toolbar button { border: 0; border-radius: 8px; background: #dc2626; color: #fff; cursor: pointer; font-size: 13px; font-weight: 700; padding: 9px 14px; }
        main { padding: 10mm; }
        h1 { margin: 0 0 12px; text-align: center; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #94a3b8; padding: 4px 5px; vertical-align: top; }
        th { background: #2563eb; color: #fff; text-align: center; }
        .center { text-align: center; }
        .status-red { background: #fee2e2; }
        .status-yellow { background: #fef3c7; }
        .status-green { background: #dcfce7; }
        .status-gray { background: #f3f4f6; }
        @media print { .toolbar { display: none; } main { padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Save as PDF</button></div>
    <main>
        <h1>Data Penjualan</h1>
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Order Number</th><th>Sales</th><th>Customer</th><th>No. Telepon</th>
                    <th>Key In</th><th>Recurring</th><th>Guarantee Letter</th><th>CCP</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salesOrders as $salesOrder)
                    @php
                        $rowClass = match ($salesOrder->status) {
                            'dibatalkan', 'gagal penelponan' => 'status-red',
                            'ditunda', 'tinjau ulang' => 'status-yellow',
                            'selesai' => 'status-green',
                            default => 'status-gray',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $salesOrder->order_no }}</td>
                        <td>{{ $salesOrder->salesUser?->full_name ?: ($salesOrder->salesUser?->name ?? '-') }}</td>
                        <td>{{ $salesOrder->customer?->full_name ?? '-' }}</td>
                        <td>{{ $salesOrder->customer?->phone_number ?? '-' }}</td>
                        <td class="center">{{ $salesOrder->key_in_at?->format('d M Y') ?? '-' }}</td>
                        <td class="center">{{ $salesOrder->is_recurring ? 'Yes' : 'No' }}</td>
                        <td class="center">{{ $salesOrder->guarantee_letter ? 'Yes' : 'No' }}</td>
                        <td>{{ $salesOrder->ccp_status ?? '-' }}</td>
                        <td>{{ $salesOrder->status ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="center">Belum ada data penjualan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
