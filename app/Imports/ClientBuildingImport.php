<?php

namespace App\Imports;

use App\Models\BEMS\Building;
use App\Events\BuildingImportProgress;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterChunk;

class ClientBuildingImport implements ToModel, WithChunkReading, WithEvents
{
    private int $processed = 0;
    private int $total;
    private int $clientId;

    public function __construct(int $total, int $clientId)
    {
        $this->total = $total;
        $this->clientId = $clientId;
    }

    public function model(array $row)
    {
        // Join all potential columns back into a single string, just in case!
        $fullName = trim(implode(' ', $row));

        // Skip completely empty lines
        if (empty($fullName)) {
            return null;
        }

        $this->processed++;

        return new Building([
            'client_id' => $this->clientId,
            'name'      => $fullName,
        ]);
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function registerEvents(): array
    {
        return [
            AfterChunk::class => function (AfterChunk $event) {
                BuildingImportProgress::dispatch(
                    $this->processed,
                    $this->total,
                    'processing'
                );
            },
        ];
    }
}