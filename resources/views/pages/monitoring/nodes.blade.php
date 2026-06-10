<?php
use Livewire\Component;
use App\Models\BEMS\Node;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component
{
    public string $search = '';

    public function with(): array
    {
        $clientId = Auth::user()->client_id;
        $nodes = Node::whereHas('classroom.building', fn($q) => $q->where('client_id', $clientId))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with(['classroom.building'])
            ->latest('last_status_at')
            ->get();
        return compact('nodes');
    }
};
?>
<div wire:poll.10s>
    <x-header title="Nodes Monitoring" subtitle="Real-time IoT sensor data" separator>
        <x-slot:actions>
            <x-input placeholder="Search nodes..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:actions>
    </x-header>
    <x-card>
        <x-table :headers="[
            ['key' => 'name',           'label' => 'Node'],
            ['key' => 'location',       'label' => 'Location'],
            ['key' => 'sensor_reading', 'label' => 'Reading'],
            ['key' => 'battery',        'label' => 'Battery'],
            ['key' => 'uptime',         'label' => 'Uptime'],
            ['key' => 'last_status_at', 'label' => 'Last Update'],
        ]" :rows="$nodes" striped>
            @scope('cell_location', $node)
                <div class="text-xs">
                    <div class="font-medium">{{ $node->classroom->name ?? '-' }}</div>
                    <div class="text-base-content/50">{{ $node->classroom->building->name ?? '-' }}</div>
                </div>
            @endscope
            @scope('cell_sensor_reading', $node)
                <span class="font-mono text-sm text-success">{{ $node->sensor_reading ?? '-' }}</span>
            @endscope
            @scope('cell_battery', $node)
                @php $bat = intval($node->battery); @endphp
                <div class="flex items-center gap-1">
                    <div class="w-12 bg-base-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $bat > 50 ? 'bg-success' : ($bat > 20 ? 'bg-warning' : 'bg-error') }}" style="width:{{ $bat }}%"></div>
                    </div>
                    <span class="text-xs">{{ $node->battery }}</span>
                </div>
            @endscope
            @scope('cell_last_status_at', $node)
                <span class="text-xs text-base-content/50">{{ $node->last_status_at ? $node->last_status_at->diffForHumans() : 'Never' }}</span>
            @endscope
        </x-table>
    </x-card>
</div>
