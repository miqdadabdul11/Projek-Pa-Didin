<?php
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Imports\ClientBuildingImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithFileUploads, Toast;

    public $file;

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        $client = Auth::user()->client;
        if (!$client) {
            $this->error('Client tidak ditemukan.');
            return;
        }

        $path = $this->file->store('imports', 'local');
        $fullPath = storage_path('app/' . $path);

        $rowCount = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\ClientBuildingImport(0, $client->id), $fullPath);
        $total = count($rowCount[0] ?? []);

        Excel::import(new ClientBuildingImport($total, $client->id), $fullPath);

        $this->success('Data berhasil diimport!');
        $this->file = null;
    }
};
?>

<div>
    <x-header title="Import Building Data" subtitle="Upload CSV atau XLSX" separator back="{{ route('client') }}" />

    <div class="max-w-lg mx-auto mt-6">
        <div class="bg-base-100 rounded-2xl border border-base-200 shadow p-6">

            <form wire:submit.prevent="import">
                <div class="mb-4">
                    <label class="text-sm font-medium mb-2 block">Pilih File (CSV/XLSX)</label>
                    <input type="file" wire:model="file" accept=".csv,.xlsx,.xls"
                        class="file-input file-input-bordered w-full" />
                    @error('file') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4 text-xs text-base-content/50">
                    Format: satu kolom berisi nama gedung per baris.
                </div>

                <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                    <span wire:loading wire:target="import">Mengimport...</span>
                    <span wire:loading.remove wire:target="import">Import Data</span>
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('client.buildings') }}" class="text-sm text-primary hover:underline">
                    Lihat semua buildings →
                </a>
            </div>
        </div>
    </div>
</div>
