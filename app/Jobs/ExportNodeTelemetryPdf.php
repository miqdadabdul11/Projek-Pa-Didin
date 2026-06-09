<?php
namespace App\Jobs;
use App\Events\ExportProgress;
use App\Models\BEMS\TelemetryLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportNodeTelemetryPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 300;
    public function __construct(public int $userId) {}

    public function handle(): void
    {
        $logs = TelemetryLog::with('node.classroom.building')->latest()->get();
        $fileName = 'telemetry-' . now()->format('Ymd-His') . '.pdf';

        Storage::disk('local')->makeDirectory('public/temp-pdf');

        $pdf = Pdf::loadView('pdf.telemetry', ['logs' => $logs])
            ->setPaper('a4', 'landscape');

        $fullPath = storage_path('app/public/temp-pdf/' . $fileName);
        file_put_contents($fullPath, $pdf->output());

        $url = Storage::url('temp-pdf/' . $fileName);
        ExportProgress::dispatch('done', $url, $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        ExportProgress::dispatch('failed', null, $this->userId);
    }
}
