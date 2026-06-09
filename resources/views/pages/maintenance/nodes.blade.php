<?php

use Livewire\Component;
use App\Models\BEMS\Node;
use App\Models\BEMS\Classroom;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    
    public $name;
    public $classroom_id; 
    public $microcontroller_chip;
    public $purpose;
    public $client_id;
    public string $search = '';
    public $showCreateModal = false;

    public $editingNodeId = null;
    public $editName = '';
    public $editClassroomId = null;
    public $editChip = '';
    public $editPurpose = '';

    public function mount() {
        $this->client_id = Auth::user()->client_id; 
        if(!$this->client_id) {
            $this->error('System error: Operator account is not linked to any client.');
        }
    }

    public function saveNode() {
        $this->validate([
            'name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id', 
            'microcontroller_chip' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
        ]);

        $classroom = Classroom::with('building')->find($this->classroom_id);
        
        if ($classroom && $classroom->building->client_id === $this->client_id) {
            Node::create([
                'classroom_id' => $this->classroom_id,
                'name' => $this->name,
                'microcontroller_chip' => $this->microcontroller_chip,
                'purpose' => $this->purpose,
            ]);
            $this->reset(['name', 'microcontroller_chip', 'purpose']);
            $this->showCreateModal = false;
            $this->success('Sensor node registered successfully!');
        } else {
            $this->error('Access denied: Invalid classroom.');
        }
    }

    public function editNode($id) {
        $node = Node::whereHas('classroom.building', function ($query) {
            $query->where('client_id', $this->client_id);
        })->find($id);

        if ($node) {
            $this->editingNodeId = $id;
            $this->editName = $node->name;
            $this->editClassroomId = $node->classroom_id; 
            $this->editChip = $node->microcontroller_chip;
            $this->editPurpose = $node->purpose;
        } else {
            $this->error('Node not found or access denied.');
        }
    }

    public function cancelEdit() {
        $this->reset(['editingNodeId', 'editName', 'editClassroomId', 'editChip', 'editPurpose']);
    }

    public function updateNode() {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editClassroomId' => 'required|exists:classrooms,id',
            'editChip' => 'nullable|string|max:255',
            'editPurpose' => 'nullable|string',
        ]);

        $node = Node::find($this->editingNodeId);
        $classroom = Classroom::with('building')->find($this->editClassroomId);

        if ($node && $classroom && $classroom->building->client_id === $this->client_id) {
            $node->update([
                'name' => $this->editName,
                'classroom_id' => $this->editClassroomId,
                'microcontroller_chip' => $this->editChip,
                'purpose' => $this->editPurpose,
            ]);
            $this->success('Hardware configuration updated successfully!');
        } else {
             $this->error('Access denied: Invalid classroom.');
        }
        $this->cancelEdit(); 
    }

    public function deleteNode($id) {
        $node = Node::whereHas('classroom.building', function ($query) {
            $query->where('client_id', $this->client_id);
        })->find($id);

        if ($node) {
            $node->delete();
            $this->warning('Node removed from system.');
        } else {
            $this->error('Node not found or access denied.');
        }
    }

    public function with(): array {
        $classrooms = Classroom::whereHas('building', function ($query) {
            $query->where('client_id', $this->client_id);
        })->with('building')->get()->map(function($room) {
            $building = $room->building->name ?? 'Unknown';
            $room->dropdown_label = "{$building} - {$room->name}";
            return $room;
        });

        $nodes = Node::whereHas('classroom.building', function ($query) {
            $query->where('client_id', $this->client_id);
        })
        ->with(['classroom.building'])
        ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
            ->orWhere('microcontroller_chip', 'like', "%{$this->search}%")
            ->orWhereHas('classroom', fn($q2) => $q2->where('name', 'like', "%{$this->search}%")))
        ->orderBy('classroom_id')->get();

        return [
            'nodes' => $nodes, 
            'classrooms' => $classrooms
        ];
    }
};
?>

<div>
    <x-header title="Node Management" subtitle="Control and register sensor nodes" separator />

    {{-- Modal Add Node --}}
    <x-modal wire:model="showCreateModal" title="Register New Node" box-class="rounded-2xl max-w-2xl">
        <x-form wire:submit="saveNode">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select
                    label="Classroom Location"
                    wire:model="classroom_id"
                    :options="$classrooms"
                    option-value="id"
                    option-label="dropdown_label"
                    placeholder="Select classroom..."
                    icon="o-map-pin"
                    required
                />
                <x-input wire:model="name" label="Node Name" icon="o-cpu-chip" placeholder="e.g., Temperature Sensor Lab" required />
                <x-input wire:model="microcontroller_chip" label="Microcontroller Chip" icon="o-wrench" placeholder="e.g., ESP8266 Wemos D1" />
                <x-input wire:model="purpose" label="Purpose" icon="o-information-circle" placeholder="e.g., Temperature monitoring" />
            </div>
            <x-slot:actions>
                <x-button label="Cancel" wire:click="$toggle('showCreateModal')" class="btn-ghost border-none" />
                <x-button label="Register Node" type="submit" icon="o-check" class="btn-success border-none shadow-none rounded-xl" spinner="saveNode" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-card shadow wire:poll.1s>
        {{-- Toolbar --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1">
                <x-input
                    wire:model.live.debounce="search"
                    placeholder="Search by node name, chip, or classroom..."
                    icon="o-magnifying-glass"
                    class="w-full bg-base-200 border-none rounded-xl"
                    clearable
                />
            </div>
            <x-button
                wire:click="$toggle('showCreateModal')"
                label="Add Node"
                icon="o-plus"
                class="btn-success border-none shadow-none rounded-xl shrink-0"
            />
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">ID</th>
                        <th class="bg-transparent border-none">Location</th>
                        <th class="bg-transparent border-none">Hardware Info</th>
                        <th class="bg-transparent border-none">Last Telemetry</th>
                        <th class="bg-transparent border-none text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nodes as $node)
                        <tr class="border-none">
                            <td class="border-none font-mono text-xs text-base-content/50">#{{ $node->id }}</td>
                            <td class="border-none">
                                @if($editingNodeId === $node->id)
                                    <x-select wire:model="editClassroomId" :options="$classrooms" option-value="id" option-label="dropdown_label" class="select-sm w-full rounded-xl" />
                                @else
                                    <div class="font-semibold">{{ $node->classroom->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-base-content/50">{{ $node->classroom->building->name ?? '' }}</div>
                                @endif
                            </td>
                            <td class="border-none">
                                @if($editingNodeId === $node->id)
                                    <x-input wire:model="editName" class="input-sm w-full rounded-xl mb-1" placeholder="Name" />
                                    <x-input wire:model="editChip" class="input-sm w-full rounded-xl" placeholder="Chip" />
                                @else
                                    <div class="font-semibold">{{ $node->name }}</div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-base-200 text-base-content/60">{{ $node->microcontroller_chip ?? 'No chip info' }}</span>
                                @endif
                            </td>
                            <td class="border-none">
                                @if($node->last_status_at)
                                    <div class="text-success text-xs font-semibold">
                                        {{ $node->last_status_at->format('H:i:s') }}
                                        <span class="text-base-content/40 font-normal">({{ $node->last_status_at->diffForHumans() }})</span>
                                    </div>
                                    <div class="text-xs mt-1">Data: {{ $node->sensor_reading ?? '--' }}</div>
                                    <div class="text-xs">Battery: {{ $node->battery ?? '--' }}</div>
                                @else
                                    <div class="text-warning text-xs font-semibold">Waiting for data...</div>
                                @endif
                            </td>
                            <td class="border-none text-right">
                                <div class="flex gap-1 justify-end">
                                    @if($editingNodeId === $node->id)
                                        <x-button wire:click="updateNode" icon="o-check" class="btn-ghost btn-sm btn-circle border-none shadow-none text-success" />
                                        <x-button wire:click="cancelEdit" icon="o-x-mark" class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                                    @else
                                        <x-button wire:click="editNode({{ $node->id }})" icon="o-pencil" class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                                        <x-button wire:click="deleteNode({{ $node->id }})" icon="o-trash" class="btn-ghost btn-sm btn-circle border-none shadow-none text-error" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-base-content/50 py-8 border-none">
                                No sensor nodes registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
