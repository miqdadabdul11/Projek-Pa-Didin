<?php

use Livewire\Component;
use App\Models\BEMS\Building;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Jobs\ProcessClientBuildingImport;
use PhpOffice\PhpSpreadsheet\IOFactory;

new class extends Component
{
    use Toast, WithFileUploads;
    
    public $name;
    public $client_id;
    public string $search = '';
    public $showCreateModal = false;

    public $editingBuildingId = null;
    public $editName = '';

    public $file = null;
    public $progress = 0;
    public $total = 0;
    public $importStatus = 'idle'; 
    public $importMessage = '';
    public $processed = 0;

    public function mount() {
        $client = Auth::user()->client; 
        if($client) {
            $this->client_id = $client->id;
        }
    }

    public function saveBuilding() {
        $this->validate(['name' => 'required|string|max:255']);
        Building::create(['client_id' => $this->client_id, 'name' => $this->name]);
        $this->name = '';
        $this->showCreateModal = false;
        $this->success('Building added successfully!');
    }

    public function editBuilding($id) {
        $building = Building::find($id);
        $this->editingBuildingId = $id; 
        $this->editName = $building->name; 
    }

    public function cancelEdit() {
        $this->editingBuildingId = null;
        $this->editName = '';
    }

    public function updateBuilding() {
        $this->validate(['editName' => 'required|string|max:255']);
        $building = Building::find($this->editingBuildingId);
        if ($building) {
            $building->update(['name' => $this->editName]);
            $this->success('Building name updated successfully!');
        }
        $this->cancelEdit(); 
    }

    public function deleteBuilding($id) {
        Building::find($id)->delete();
        $this->warning('Building deleted.');
    }

    public function importBuildings() {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $this->total = $this->countRows($this->file->getRealPath());

        if ($this->total === 0) {
            $this->error('File is empty or format is incorrect.');
            return;
        }

        $savedPath = $this->file->store('imports');
        
        $this->importStatus = 'processing';
        $this->importMessage = "Processing {$this->total} records...";
        $this->progress = 0;

        ProcessClientBuildingImport::dispatch($savedPath, $this->total, $this->client_id);
    }

    private function countRows($path) {
        $extension = $this->file->getClientOriginalExtension();

        if (in_array(strtolower($extension), ['csv', 'txt'])) {
            $rows = 0;
            if (($h = fopen($path, 'r')) !== false) {
                while (fgetcsv($h) !== false) $rows++;
                fclose($h);
            }
            return $rows;
        }

        $spreadsheet = IOFactory::load($path);
        return $spreadsheet->getActiveSheet()->getHighestDataRow();
    }

    #[On('echo:building-import,.progress')]
    public function onProgress(array $data) {
        $this->processed = $data['processed'];
        $this->total = $data['total'];
        $this->importStatus = $data['status'];
        
        $this->progress = $this->total > 0 ? (int) round(($this->processed / $this->total) * 100) : 0;

        if ($this->importStatus === 'done') {
            $this->success("Successfully imported {$this->total} buildings!");
            $this->resetImport();
        } elseif ($this->importStatus === 'failed') {
            $this->error('Import failed. Check system logs.');
            $this->resetImport();
        }
    }

    public function resetImport() {
        $this->reset(['file', 'progress', 'total', 'importStatus', 'importMessage', 'processed']);
    }

    public function with(): array {
        return [
            'buildings' => Building::where('client_id', $this->client_id)
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest()->get()
        ];
    }
};
?>

<div wire:poll.10s>
    <x-header title="Building Management" subtitle="Manage your faculty buildings" separator />

    {{-- Modal Add Building --}}
    <x-modal wire:model="showCreateModal" title="Add New Building" box-class="rounded-2xl max-w-2xl">
        <x-form wire:submit="saveBuilding">
            <x-input wire:model="name" label="Building Name" icon="o-building-office" placeholder="e.g., Faculty Building A" required />

            {{-- Bulk Import --}}
            <div class="mt-4">
                <div class="text-xs font-bold text-base-content/50 uppercase tracking-widest mb-2">Bulk Import (.CSV / .XLSX)</div>
                <p class="text-xs text-base-content/50 mb-2">Format: Building Name</p>
                @if($importStatus === 'idle')
                    <div class="flex gap-2 items-end">
                        <x-file wire:model="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="w-full" />
                        <x-button wire:click="importBuildings" icon="o-arrow-up-tray" class="btn-success border-none shadow-none rounded-xl" spinner="importBuildings" />
                    </div>
                @else
                    <div class="w-full pt-2">
                        <div class="flex justify-between text-xs mb-1 font-bold">
                            <span>{{ $importMessage }}</span>
                            <span class="text-primary">{{ $progress }}%</span>
                        </div>
                        <progress class="progress progress-primary w-full" value="{{ $progress }}" max="100"></progress>
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$toggle('showCreateModal')" class="btn-ghost border-none" />
                <x-button label="Save Building" type="submit" icon="o-check" class="btn-success border-none shadow-none rounded-xl" spinner="saveBuilding" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-card shadow>
        {{-- Toolbar --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1">
                <x-input
                    wire:model.live.debounce="search"
                    placeholder="Search buildings..."
                    icon="o-magnifying-glass"
                    class="w-full bg-base-200 border-none rounded-xl"
                    clearable
                />
            </div>
            <x-button
                wire:click="$toggle('showCreateModal')"
                label="Add Building"
                icon="o-plus"
                class="btn-success border-none shadow-none rounded-xl shrink-0"
            />
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Building Name</th>
                        <th class="bg-transparent border-none text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buildings as $building)
                        <tr class="border-none">
                            <td class="border-none">
                                @if($editingBuildingId === $building->id)
                                    <x-input wire:model="editName" class="input-sm w-full rounded-xl" />
                                @else
                                    <span class="font-semibold">{{ $building->name }}</span>
                                @endif
                            </td>
                            <td class="border-none text-right">
                                <div class="flex gap-1 justify-end">
                                    @if($editingBuildingId === $building->id)
                                        <x-button wire:click="updateBuilding" icon="o-check" class="btn-ghost btn-sm btn-circle border-none shadow-none text-success" />
                                        <x-button wire:click="cancelEdit" icon="o-x-mark" class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                                    @else
                                        <x-button wire:click="editBuilding({{ $building->id }})" icon="o-pencil" class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                                        <x-button wire:click="deleteBuilding({{ $building->id }})" icon="o-trash" class="btn-ghost btn-sm btn-circle border-none shadow-none text-error" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-base-content/50 py-8 border-none">
                                No buildings registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
