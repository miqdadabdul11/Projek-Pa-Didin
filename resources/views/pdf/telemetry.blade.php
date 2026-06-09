<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.sub { font-size: 10px; color: #666; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #16a34a; color: white; }
        th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .mono { font-family: monospace; }
    </style>
</head>
<body>
    <h1>Node Telemetry Export</h1>
    <p class="sub">Generated at: {{ now()->format('d/m/Y H:i:s') }} &nbsp;|&nbsp; Total: {{ $logs->count() }} records</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Building</th>
                <th>Classroom</th>
                <th>Node</th>
                <th>Sensor Reading</th>
                <th>Battery</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $i => $log)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $log->node?->classroom?->building?->name ?? '-' }}</td>
                    <td>{{ $log->node?->classroom?->name ?? '-' }}</td>
                    <td>{{ $log->node?->name ?? '-' }}</td>
                    <td class="mono">{{ $log->sensor_reading }}</td>
                    <td class="mono">{{ $log->battery }}</td>
                    <td class="mono">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#999;">No data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>