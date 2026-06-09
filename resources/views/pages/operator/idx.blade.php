<?php

use Livewire\Component;
use App\Models\BEMS\Building;
use App\Models\BEMS\Node;
use App\Models\BEMS\NodeRequest;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public function with(): array 
    {
        $clientId = Auth::user()->client_id;

        if (!$clientId) {
            return ['latestNode' => null, 'buildings' => collect(), 'clientName' => 'Error', 'pendingCount' => 0];
        }

        $clientName = Auth::user()->client->name ?? 'Faculty';

        $latestNode = Node::whereHas('classroom.building', fn($q) => $q->where('client_id', $clientId))
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
                    $building->graph_data = $logs->map(fn($log) => floatval($log->sensor_reading))->values()->toArray();
                } else {
                    $building->graph_data = [];
                }
                return $building;
            });

        $pendingCount = NodeRequest::whereHas('node.classroom.building', fn($q) => $q->where('client_id', $clientId))
            ->where('status', 'pending')->count();

        return [
            'clientName'   => $clientName,
            'latestNode'   => $latestNode,
            'buildings'    => $buildings,
            'pendingCount' => $pendingCount,
        ];
    }
};
?>

<div wire:poll.1s>
    <x-header title="Operator Dashboard" subtitle="IoT Monitoring System: {{ $clientName }}" separator />

    {{-- Pending Requests Alert --}}
    @if($pendingCount > 0)
        <a href="{{ route('operator.requests') }}" wire:navigate>
            <x-alert title="{{ $pendingCount }} Pending Request(s)"
                description="There are viewer requests waiting for your approval. Click to review."
                icon="o-bell-alert" class="alert-warning mb-6 shadow-sm rounded-2xl cursor-pointer hover:opacity-80 transition-opacity" />
        </a>
    @endif

    {{-- Live Feed --}}
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
                <span><strong>Building:</strong> {{ $latestNode->classroom->building->name ?? '-' }}</span>
                <span><strong>Room:</strong> {{ $latestNode->classroom->name ?? '-' }}</span>
                <span><strong>Node ID:</strong> #{{ $latestNode->id }}</span>
            </div>
        </div>
    @else
        <x-alert title="Waiting for Data" description="System active. No hardware has transmitted data yet." icon="o-wifi" class="alert-warning mb-6 shadow-sm rounded-2xl" />
    @endif

    {{-- Buildings Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($buildings as $building)
            <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 overflow-hidden hover:shadow-md transition-shadow">
                <div class="px-5 pt-4 pb-3 border-b border-base-200">
                    <h3 class="font-bold">{{ $building->name }}</h3>
                </div>
                <div class="grid grid-cols-2 border-b border-base-200">
                    <div class="px-5 py-3 text-center border-r border-base-200">
                        <div class="text-xl font-bold">{{ $building->total_classrooms }}</div>
                        <div class="text-[10px] text-base-content/50 uppercase tracking-widest">Rooms</div>
                    </div>
                    <div class="px-5 py-3 text-center">
                        <div class="text-xl font-bold text-primary">{{ $building->total_nodes }}</div>
                        <div class="text-[10px] text-primary/60 uppercase tracking-widest">Sensors</div>
                    </div>
                </div>
                <div class="px-5 py-3 border-b border-base-200">
                    <div class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest mb-2">Latest Activity</div>
                    @if($building->latest_node)
                        <div class="font-bold flex items-center gap-2">
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
                    <div class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest mb-2">History Trend</div>
                    <div class="flex items-end gap-0.5 h-10 w-full">
                        @if(count($building->graph_data) > 0)
                            @php $maxVal = max($building->graph_data) ?: 1; @endphp
                            @foreach($building->graph_data as $val)
                                <div class="flex-1 bg-primary/50 rounded-t-sm hover:bg-primary transition-all"
                                     style="height: {{ ($val / $maxVal) * 100 }}%; min-height: 2px;" title="{{ $val }}"></div>
                            @endforeach
                        @else
                            <div class="w-full text-center text-xs text-base-content/30">Waiting for history...</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center text-base-content/40 py-12">No buildings registered yet.</div>
        @endforelse
    </div>
</div>