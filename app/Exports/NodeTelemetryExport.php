<?php

namespace App\Exports;

use App\Models\BEMS\TelemetryLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NodeTelemetryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function query()
    {
        return TelemetryLog::query()
            ->with('node.classroom.building')
            ->latest();
    }

    public function title(): string
    {
        return 'Telemetry Logs';
    }

    public function headings(): array
    {
        return [
            'No',
            'Building',
            'Classroom',
            'Node',
            'Microcontroller',
            'Sensor Reading',
            'Battery',
            'Timestamp',
        ];
    }

    private int $row = 1;

    public function map($log): array
    {
        return [
            $this->row++,
            $log->node?->classroom?->building?->name ?? '-',
            $log->node?->classroom?->name ?? '-',
            $log->node?->name ?? '-',
            $log->node?->microcontroller_chip ?? '-',
            $log->sensor_reading,
            $log->battery,
            $log->created_at->format('d/m/Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '16a34a']],
            ],
        ];
    }
}