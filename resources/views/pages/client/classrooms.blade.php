<?php

use Livewire\Component;
use App\Models\BEMS\Classroom;
use App\Models\BEMS\Building;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Jobs\ProcessClientClassroomImport;
use PhpOffice\PhpSpreadsheet\IOFactory;

new class extends Component
{
    use Toast, WithFileUploads;
    
    public $name;
    public $building_id;
    public $client_id;
    public string $search = '';
    public $showCreateModal = false;

    public $editingClassroomId = null;
    public $editName = '';
    public $editBuildingId = null;

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

    public function saveClassroom() {
        $this->validate([
            'name' => 'required|string|max:255',
            'building_id' => 'required|exists:buildings,id', 
        ]);

        $building = Building::find($this->building_id);
        if ($building && $building->client_id === $this->client_id) {
            Classroom::create(['building_id' => $this->building_id, 'name' => $this->name]);
            $this->name = '';
            $this->showCreateModal = false;
            $this->success('Classroom added successfully!');
        } else {
            $this->error('Access denied: Invalid building.');
        }
    }

    public function editClassroom($id) {
        $classroom = Classroom::find($id);
        $this->editingClassroomId = $id;
        $this->editName = $classroom->name;
        $this->editBuildingId = $classroom->building_id; 
    }

    public function cancelEdit() {
        $this->editingClassroomId = null;
        $this->editName = '';
        $this->editBuildingId = null;
    }

    public function updateClassroom() {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editBuildingId' => 'required|exists:buildings,id',
        ]);

        $classroom = Classroom::find($this->editingClassroomId);
        $building = Building::find($this->editBuildingId);

        if ($classroom && $building && $building->client_id === $this->client_id) {
            $classroom->update(['name' => $this->editName, 'building_id' => $this->editBuildingId]);
            $this->success('Classroom updated successfully!');
        } else {
            $this->error('Access denied: Invalid building.');
        }
        $this->cancelEdit(); 
    }

    public function deleteClassroom($id) {
        Classroom::find($id)->delete();
        $this->warning('Classroom deleted.');
    }

    public function importClassrooms() {
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

        ProcessClientClassroomImport::dispatch($savedPath, $this->total, $this->client_id);
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

    #[On('echo:classroom-import,.progress')]
    public function onProgress(array $data) {
        $this->processed = $data['processed'];
        $this->total = $data['total'];
        $this->importStatus = $data['status'];
        $this->progress = $this->total > 0 ? (int) round(($this->processed / $this->total) * 100) : 0;

        if ($this->importStatus === 'done') {
            $this->success("Successfully imported {$this->total} classrooms!");
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
            'buildings' => Building::where('client_id', $this->client_id)->get(),
            'classrooms' => Classroom::whereHas('building', function ($query) {
                $query->where('client_id', $this->client_id);
            })
            ->with('building')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhereHas('building', fn($q2) => $q2->where('name', 'like', "%{$this->search}%")))
            ->orderBy('building_id')->latest()->get()
        ];
    }
};
?>

<div wire:poll.10s>
    <x-header title="Classroom Management" subtitle="Manage classrooms for each building" separator />

    {{-- Modal Add Classroom --}}
    <x-modal wire:model="showCreateModal" title="Add New Classroom" box-class="rounded-2xl max-w-2xl">
        <x-form wire:submit="saveClassroom">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select
                    wire:model="building_id"
                    label="Building"
                    :options="$buildings"
                    option-value="id"
                    option-label="name"
                    placeholder="Select building..."
                    icon="o-building-office"
                    required
                />
                <x-input wire:model="name" label="Classroom Name" icon="o-squares-plus" placeholder="e.g., Lab Telekomunikasi" required />
            </div>

            {{-- Bulk Import --}}
            <div class="mt-4">
                <div class="text-xs font-bold text-base-content/50 uppercase tracking-widest mb-2">Bulk Import (.CSV / .XLSX)</div>
                <p class="text-xs text-base-content/50 mb-2">Format: Building Name, Classroom Name</p>
                @if($importStatus === 'idle')
                    <div class="flex gap-2 items-end">
                        <x-file wire:model="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="w-full" />
                        <x-button wire:click="importClassrooms" icon="o-arrow-up-tray" class="btn-success border-none shadow-none rounded-xl" spinner="importClassrooms" />
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
                <x-button label="Save Classroom" type="submit" icon="o-check" class="btn-success border-none shadow-none rounded-xl" spinner="saveClassroom" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-card shadow>
        {{-- Toolbar --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1">
                <x-input
                    wire:model.live.debounce="search"
                    placeholder="Search by classroom or building..."
                    icon="o-magnifying-glass"
                    class="w-full bg-base-200 border-none rounded-xl"
                    clearable
                />
            </div>
            <x-button
                wire:click="$toggle('showCreateModal')"
                label="Add Classroom"
                icon="o-plus"
                class="btn-success border-none shadow-none rounded-xl shrink-0"
            />
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Building</th>
                        <th class="bg-transparent border-none">Classroom Name</th>
                        <th class="bg-transparent border-none text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classrooms as $room)
                        <tr class="border-none">
                            <td class="border-none">
                                @if($editingClassroomId === $room->id)
                                    <x-select wire:model="editBuildingId" :options="$buildings" option-value="id" option-label="name" class="select-sm w-full rounded-xl" />
                                @else
                                    <span class="text-base-content/60">{{ $room->building->name ?? 'Unknown' }}</span>
                                @endif
                            </td>
                            <td class="border-none">
                                @if($editingClassroomId === $room->id)
                                    <x-input wire:model="editName" class="input-sm w-full rounded-xl" />
                                @else
                                    <span class="font-semibold">{{ $room->name }}</span>
                                @endif
                            </td>
                            <td class="border-none text-right">
                                <div class="flex gap-1 justify-end">
                                    @if($editingClassroomId === $room->id)
                                        <x-button wire:click="updateClassroom" icon="o-check" class="btn-ghost btn-sm btn-circle border-none shadow-none text-success" />
                                        <x-button wire:click="cancelEdit" icon="o-x-mark" class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                                    @else
                                        <x-button wire:click="editClassroom({{ $room->id }})" icon="o-pencil" class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                                        <x-button wire:click="deleteClassroom({{ $room->id }})" icon="o-trash" class="btn-ghost btn-sm btn-circle border-none shadow-none text-error" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-base-content/50 py-8 border-none">
                                No classrooms registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
