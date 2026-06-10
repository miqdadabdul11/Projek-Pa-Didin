<?php
use Livewire\Component;
use App\Models\BEMS\Building;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component
{
    public string $search = '';

    public function with(): array
    {
        $clientId = Auth::user()->client_id;
        $buildings = Building::where('client_id', $clientId)
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('classrooms')
            ->with('classrooms.nodes')
            ->get()
            ->map(function($b) {
                $nodes = $b->classrooms->flatMap->nodes;
                $b->total_nodes = $nodes->count();
                $b->active_nodes = $nodes->whereNotNull('last_status_at')->count();
                return $b;
            });
        return compact('buildings');
    }
};
?>
<div>
    <x-header title="Buildings Monitoring" subtitle="Overview of all buildings and their status" separator>
        <x-slot:actions>
            <x-input placeholder="Search buildings..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:actions>
    </x-header>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($buildings as $building)
            <div class="card bg-base-100 shadow border border-base-200">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="card-title text-base">{{ $building->name }}</h3>
                        @if($building->active_nodes > 0)
                            <span class="badge badge-success badge-sm">Active</span>
                        @else
                            <span class="badge badge-warning badge-sm">Idle</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="bg-base-200 rounded-lg p-2 text-center">
                            <div class="font-bold text-lg">{{ $building->classrooms_count }}</div>
                            <div class="text-xs text-base-content/50">Rooms</div>
                        </div>
                        <div class="bg-base-200 rounded-lg p-2 text-center">
                            <div class="font-bold text-lg">{{ $building->total_nodes }}</div>
                            <div class="text-xs text-base-content/50">Nodes</div>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-base-content/50">
                        {{ $building->active_nodes }} / {{ $building->total_nodes }} nodes active
                    </div>
                    <div class="w-full bg-base-200 rounded-full h-1.5 mt-1">
                        <div class="bg-success h-1.5 rounded-full" style="width: {{ $building->total_nodes > 0 ? ($building->active_nodes / $building->total_nodes * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 text-base-content/40">No buildings found.</div>
        @endforelse
    </div>
</div>
