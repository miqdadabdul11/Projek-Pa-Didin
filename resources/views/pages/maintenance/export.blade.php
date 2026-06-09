<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NodeTelemetryExport;
use App\Jobs\ExportNodeTelemetryPdf;

new class extends Component
{
    use Toast;

    public string $format = 'xlsx';
    public bool   $isProcessing = false;
    public ?string $downloadUrl = null;

    public function getListeners(): array
    {
        return [
            'echo:export.' . auth()->id() . ',.progress' => 'onProgress',
        ];
    }

    public function onProgress(array $data): void
    {
        $this->isProcessing = false;
        $this->downloadUrl  = $data['url'] ?? null;

        if ($data['status'] === 'done') {
            $this->success('Export selesai! Klik tombol download.');
        } else {
            $this->error('Export gagal. Coba lagi.');
        }
    }

public function export(): void
{
    $this->downloadUrl  = null;
    $this->isProcessing = true;

    if ($this->format === 'xlsx') {
        $this->isProcessing = false;
        $this->redirectRoute('maintenance.export.download'); // ← ganti ini
        return;
    }

    ExportNodeTelemetryPdf::dispatch(auth()->id());
}

    public function with(): array
    {
        return [
            'formatOptions' => [
                ['id' => 'xlsx', 'name' => 'Excel (.xlsx)'],
                ['id' => 'pdf',  'name' => 'PDF (.pdf)'],
            ],
        ];
    }
};
?>

<div>
    <x-header title="Export Data Node" subtitle="Download telemetry logs semua node" separator />

    <x-card shadow class="max-w-lg">
        <x-select
            wire:model="format"
            label="Format Export"
            :options="$formatOptions"
            option-value="id"
            option-label="name"
            icon="o-document-arrow-down"
        />

        <div class="mt-4">
            @if($isProcessing)
                <div class="flex items-center gap-3 text-sm text-base-content/60">
                    <span class="loading loading-spinner loading-sm"></span>
                    Generating file, harap tunggu...
                </div>
            @elseif($downloadUrl)
                <a href="{{ $downloadUrl }}" target="_blank"
                   class="btn btn-success border-none shadow-none rounded-xl w-full gap-2">
                    <x-icon name="o-arrow-down-tray" class="w-4 h-4" />
                    Download File
                </a>
            @else
                <x-button
                    wire:click="export"
                    label="Export Sekarang"
                    icon="o-arrow-down-tray"
                    class="btn-success border-none shadow-none rounded-xl w-full"
                    spinner="export"
                    :disabled="$isProcessing"
                />
            @endif
        </div>
    </x-card>
</div>