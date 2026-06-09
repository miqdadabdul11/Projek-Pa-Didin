<?php

namespace App\Imports;

use App\Models\BEMS\Building;
use App\Models\BEMS\Classroom;
use App\Events\ClassroomImportProgress; // You'll need to duplicate your previous Event for classrooms!
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterChunk;

class ClientClassroomImport implements ToModel, WithChunkReading, WithEvents
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
        // Skip rows that don't have both columns filled out
        if (empty(trim($row[0] ?? '')) || empty(trim($row[1] ?? ''))) {
            return null;
        }

        $buildingName = trim($row[0]);
        $roomName = trim($row[1]);

        $this->processed++;

        // THE MAGIC: Find the building for this client, or create it if it's missing!
        $building = Building::firstOrCreate(
            ['client_id' => $this->clientId, 'name' => $buildingName]
        );

        // Now attach the new classroom to the building we just found/created
        return new Classroom([
            'building_id' => $building->id,
            'name'      => $roomName,
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
                ClassroomImportProgress::dispatch($this->processed, $this->total, 'processing');
            },
        ];
    }
}