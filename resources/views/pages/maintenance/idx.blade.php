<?php
use Livewire\Component;
use App\Models\BEMS\Node;
use Illuminate\Support\Facades\Auth;
new class extends Component
{
    public function with(): array 
    {
        $clientId = Auth::user()->client_id;
        if (!$clientId) {
            return ['stats' => [], 'offlineNodes' => collect(), 'clientName' => 'Faculty'];
        }
        $clientName = Auth::user()->client->name ?? 'Faculty';
        $allNodes = Node::whereHas('classroom.building', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->with(['classroom.building'])->get();
        $totalNodes = $allNodes->count();
        $offlineThreshold = now()->subMinutes(15);
        $activeNodes = $allNodes->where('last_status_at', '>=', $offlineThreshold)->count();
        $offlineNodes = $allNodes->where('last_status_at', '<', $offlineThreshold);
        $stats = [
            'total' => $totalNodes,
            'active' => $activeNodes,
            'offline' => $offlineNodes->count(),
        ];
        return [
            'clientName' => $clientName,
            'stats' => $stats,
            'offlineNodesList' => $offlineNodes,
        ];
    }
};
?>
<div wire:poll.10s>
    <x-header title="Maintenance Dashboard" subtitle="Hardware Health Status: {{ $clientName }}" separator />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stat title="Total Registered Nodes" value="{{ $stats['total'] }}" icon="o-cpu-chip" class="bg-base-100 shadow rounded-2xl" />
        <x-stat title="Active Nodes (Online)" value="{{ $stats['active'] }}" icon="o-signal" class="bg-base-100 shadow rounded-2xl" />
        <x-stat title="Offline Nodes" value="{{ $stats['offline'] }}" icon="o-exclamation-triangle" class="bg-base-100 shadow rounded-2xl" />
    </div>

    @if($stats['offline'] > 0)
        <x-card title="Warning: Offline / Signal Lost Nodes" shadow class="rounded-2xl">
            <div class="overflow-x-auto">
                <table class="table w-full text-sm">
                    <thead>
                        <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                            <th class="bg-transparent border-none">ID</th>
                            <th class="bg-transparent border-none">Building & Room</th>
                            <th class="bg-transparent border-none">Last Online</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($offlineNodesList as $node)
                            <tr class="border-none">
                                <td class="border-none font-mono font-bold text-error">#{{ $node->id }}</td>
                                <td class="border-none">
                                    <div class="font-semibold">{{ $node->classroom->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-base-content/50">{{ $node->classroom->building->name ?? '' }}</div>
                                </td>
                                <td class="border-none">
                                    {{ $node->last_status_at ? $node->last_status_at->diffForHumans() : 'Never online' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        <x-alert title="System Healthy" description="All sensor nodes are currently operating normally and sending telemetry." icon="o-check-circle" class="alert-success shadow rounded-2xl" />
    @endif
</div>
