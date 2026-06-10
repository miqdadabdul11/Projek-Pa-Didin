<?php
use Livewire\Component;
use App\Models\BEMS\Classroom;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component
{
    public string $search = '';

    public function with(): array
    {
        $clientId = Auth::user()->client_id;
        $rooms = Classroom::whereHas('building', fn($q) => $q->where('client_id', $clientId))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with(['building', 'nodes'])
            ->get();
        return compact('rooms');
    }
};
?>
<div>
    <x-header title="Rooms Monitoring" subtitle="All classrooms and their IoT nodes" separator>
        <x-slot:actions>
            <x-input placeholder="Search rooms..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:actions>
    </x-header>
    <x-card>
        <x-table :headers="[
            ['key' => 'name',     'label' => 'Room'],
            ['key' => 'building', 'label' => 'Building'],
            ['key' => 'nodes',    'label' => 'Nodes'],
            ['key' => 'status',   'label' => 'Status'],
        ]" :rows="$rooms" striped>
            @scope('cell_building', $room)
                {{ $room->building->name ?? '-' }}
            @endscope
            @scope('cell_nodes', $room)
                {{ $room->nodes->count() }} nodes
            @endscope
            @scope('cell_status', $room)
                @if($room->nodes->whereNotNull('last_status_at')->count() > 0)
                    <span class="badge badge-success badge-sm">Active</span>
                @else
                    <span class="badge badge-warning badge-sm">Idle</span>
                @endif
            @endscope
        </x-table>
    </x-card>
</div>
