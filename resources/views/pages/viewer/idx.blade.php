<?php

use Livewire\Component;
use App\Models\BEMS\Building;
use App\Models\BEMS\Node;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public function with(): array 
    {
        $clientId = Auth::user()->client_id;

        if (!$clientId) {
            return ['latestNode' => null, 'buildings' => collect(), 'clientName' => 'Error: Not Found'];
        }

        $clientName = Auth::user()->client->name ?? 'Error: Not Found';

        $latestNode = Node::whereHas('classroom.building', function($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->whereNotNull('last_status_at')
            ->with(['classroom.building'])
            ->orderByDesc('last_status_at')
            ->first();

        $buildings = Building::where('client_id', $clientId)
            ->with(['classrooms.nodes'])
            ->get()
            ->map(function($building) {
                $building->total_classrooms = $building->classrooms->count();
                $allNodes = $building->classrooms->flatMap->nodes;
                $building->total_nodes = $allNodes->count();
                $latestNode = $allNodes->whereNotNull('last_status_at')->sortByDesc('last_status_at')->first();
                $building->latest_node = $latestNode;
                if ($latestNode) {
                    $logs = $latestNode->telemetryLogs()->latest()->take(12)->get()->reverse();
                    $building->graph_data = $logs->map(function($log) {
                        return floatval($log->sensor_reading);
                    })->values()->toArray();
                } else {
                    $building->graph_data = [];
                }
                return $building;
            });

        return [
            'clientName' => $clientName,
            'latestNode' => $latestNode,
            'buildings' => $buildings,
        ];
    }
};
?>

<div wire:poll.1s>
    
    <x-header title="Monitoring Dashboard" subtitle="IoT Monitoring System: {{ $clientName }}" separator />

    @if($latestNode)
        <div class="bg-base-100 border border-base-300 p-5 rounded-2xl mb-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-primary uppercase tracking-widest flex items-center gap-1">
                    <x-icon name="o-bolt" class="w-4 h-4" /> Live Hardware Feed
                </h2>
                <div class="text-xs text-base-content/50">
                    {{ $latestNode->last_status_at->diffForHumans() }} ({{ $latestNode->last_status_at->format('H:i:s') }})
                </div>
            </div>
            <div class="text-2xl font-bold text-base-content mb-2">
                {{ $latestNode->sensor_reading }}
                <span class="text-sm font-normal text-base-content/50 ml-2">Battery: {{ $latestNode->battery }}</span>
            </div>
            <div class="flex flex-wrap gap-4 text-xs text-base-content/60">
                <span><strong>Building:</strong> {{ $latestNode->classroom->building->name ?? 'Unknown' }}</span>
                <span><strong>Room:</strong> {{ $latestNode->classroom->name ?? 'Unknown' }}</span>
                <span><strong>Node ID:</strong> #{{ $latestNode->id }}</span>
            </div>
        </div>
    @else
        <x-alert title="Waiting for Data" description="System active. No hardware has transmitted data yet." icon="o-wifi" class="alert-warning mb-6 shadow-sm rounded-2xl" />
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($buildings as $building)
            <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 overflow-hidden hover:shadow-md transition-shadow">
                
                <div class="px-5 pt-4 pb-3 border-b border-base-200">
                    <h3 class="font-bold text-base-content">{{ $building->name }}</h3>
                </div>

                <div class="grid grid-cols-2 gap-0 border-b border-base-200">
                    <div class="px-5 py-3 text-center border-r border-base-200">
                        <div class="text-xl font-bold text-base-content">{{ $building->total_classrooms }}</div>
                        <div class="text-[10px] text-base-content/50 uppercase tracking-widest">Rooms</div>
                    </div>
                    <div class="px-5 py-3 text-center">
                        <div class="text-xl font-bold text-primary">{{ $building->total_nodes }}</div>
                        <div class="text-[10px] text-primary/60 uppercase tracking-widest">Active Sensors</div>
                    </div>
                </div>

                <div class="px-5 py-3 border-b border-base-200">
                    <div class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest mb-2">Latest Activity</div>
                    @if($building->latest_node)
                        <div class="text-base font-bold text-base-content flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-success animate-pulse shrink-0"></span>
                            {{ $building->latest_node->sensor_reading }}
                        </div>
                        <div class="text-xs text-base-content/50 mt-1 flex justify-between">
                            <span>{{ $building->latest_node->classroom->name }}</span>
                            <span>{{ $building->latest_node->last_status_at->diffForHumans() }}</span>
                        </div>
                    @else
                        <div class="text-xs text-base-content/40 italic">No data yet.</div>
                    @endif
                </div>

                <div class="px-5 py-3">
                    <div class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest mb-2">History Trend (Real-Time)</div>
                    <div class="flex items-end gap-0.5 h-10 w-full">
                        @if(isset($building->graph_data) && count($building->graph_data) > 0)
                            @php $maxValue = max($building->graph_data) > 0 ? max($building->graph_data) : 1; @endphp
                            @foreach($building->graph_data as $val)
                                @php $heightPercent = ($val / $maxValue) * 100; @endphp
                                <div class="flex-1 bg-primary/50 rounded-t-sm hover:bg-primary transition-all duration-300"
                                     style="height: {{ $heightPercent }}%; min-height: 2px;"
                                     title="{{ $val }}">
                                </div>
                            @endforeach
                        @else
                            <div class="w-full text-center text-xs text-base-content/30">Waiting for history...</div>
                        @endif
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</div>
