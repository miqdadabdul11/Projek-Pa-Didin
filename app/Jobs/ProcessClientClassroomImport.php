<?php

namespace App\Jobs;

use App\Events\ClassroomImportProgress;
use App\Imports\ClientClassroomImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessClientClassroomImport implements ShouldQueue
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
        // Run the import
        Excel::import(
            new ClientClassroomImport($this->total, $this->clientId),
            $this->filePath
        );

        // Tell Reverb the job is finished
        ClassroomImportProgress::dispatch($this->total, $this->total, 'done');
    }

    public function failed(Throwable $exception): void
    {
        // Tell Reverb the job crashed
        ClassroomImportProgress::dispatch(0, $this->total, 'failed');
    }
}