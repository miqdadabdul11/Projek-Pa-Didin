<?php

namespace App\Jobs;

use App\Events\BuildingImportProgress;
use App\Imports\ClientBuildingImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessClientBuildingImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public string $filePath,
        public int $total,
        public int $clientId
    ) {}

    public function handle(): void
    {
        // Run the import using the relative file path on the default disk
        Excel::import(
            new ClientBuildingImport($this->total, $this->clientId),
            $this->filePath
        );

        // Tell Reverb the job is finished
        BuildingImportProgress::dispatch($this->total, $this->total, 'done');
    }

    public function failed(Throwable $exception): void
    {
        BuildingImportProgress::dispatch(0, $this->total, 'failed');
    }
}