<?php

use Livewire\Component;
use App\Models\BEMS\Client;
use App\Models\BEMS\Node;
use App\Models\User;
use App\Models\BEMS\Building;

new class extends Component
{
    public $lastUpdateTracker = null;

    public function checkNewData() {
        $latest = Node::whereNotNull('last_status_at')->orderByDesc('last_status_at')->first();
        if ($latest) {
            $currentTime = $latest->last_status_at->timestamp;
            if ($this->lastUpdateTracker !== null && $currentTime > $this->lastUpdateTracker) {
                $this->dispatch('play-chime');
            }
            $this->lastUpdateTracker = $currentTime;
        }
    }

    public function with(): array
    {
        $this->checkNewData();

        $latestGlobalNode = Node::whereNotNull('last_status_at')
            ->with(['classroom.building.client'])
            ->orderByDesc('last_status_at')
            ->first();

        $clients = Client::with(['buildings.classrooms.nodes'])->get()->map(function($client) {
            $client->total_buildings  = $client->buildings->count();
            $allClassrooms            = $client->buildings->flatMap->classrooms;
            $client->total_classrooms = $allClassrooms->count();
            $allNodes                 = $allClassrooms->flatMap->nodes;
            $client->total_nodes      = $allNodes->count();
            $latestNode               = $allNodes->whereNotNull('last_status_at')->sortByDesc('last_status_at')->first();
            $client->latest_node      = $latestNode;
            if ($latestNode) {
                $logs = $latestNode->telemetryLogs()->latest()->take(12)->get()->reverse();
                $client->graph_data = $logs->map(fn($log) => floatval($log->sensor_reading))->values()->toArray();
            } else {
                $client->graph_data = [];
            }
            return $client;
        });

        $totalClients  = Client::count();
        $pendingClients = Client::whereHas('user', fn($q) => $q->where('is_approved', false))->count();
        $totalUsers    = User::count();
        $totalBuildings = Building::count();
        $totalNodes    = Node::count();

        $userDistribution = [
            ['role' => 'Admin',       'count' => User::role('admin')->count(),       'color' => '#6366f1'],
            ['role' => 'Client',      'count' => User::role('client')->count(),      'color' => '#22c55e'],
            ['role' => 'Operator',    'count' => User::role('operator')->count(),    'color' => '#f97316'],
            ['role' => 'Maintenance', 'count' => User::role('maintenance')->count(), 'color' => '#a855f7'],
            ['role' => 'Viewer',      'count' => User::role('viewer')->count(),      'color' => '#06b6d4'],
        ];

        $pendingApprovals = Client::with('user')
            ->whereHas('user', fn($q) => $q->where('is_approved', false))
            ->latest()->take(5)->get();

        $alerts = collect([
            ['priority' => 'Critical', 'time' => now()->subHours(2)->format('H:i'), 'desc' => 'Node offline detected', 'color' => 'badge-error'],
            ['priority' => 'Warning',  'time' => now()->subHours(5)->format('H:i'), 'desc' => 'Sensor out of range',   'color' => 'badge-warning'],
        ]);

        return compact(
            'latestGlobalNode', 'clients', 'totalClients', 'pendingClients',
            'totalUsers', 'totalBuildings', 'totalNodes', 'userDistribution',
            'pendingApprovals', 'alerts'
        );
    }
};
?>

<div wire:poll.5s x-data @play-chime.window="new Audio('/chime.mp3').play().catch(()=>{})">

    <x-header title="Admin Dashboard" subtitle="System-wide IoT Monitoring" separator />

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase tracking-widest mb-1">Total Clients</div>
            <div class="text-3xl font-bold text-primary">{{ $totalClients }}</div>
            @if($pendingClients > 0)
                <div class="text-xs text-warning mt-1">{{ $pendingClients }} pending</div>
            @endif
        </div>
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase tracking-widest mb-1">Total Users</div>
            <div class="text-3xl font-bold">{{ $totalUsers }}</div>
        </div>
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase tracking-widest mb-1">Buildings</div>
            <div class="text-3xl font-bold text-accent">{{ $totalBuildings }}</div>
        </div>
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase tracking-widest mb-1">IoT Nodes</div>
            <div class="text-3xl font-bold text-info">{{ $totalNodes }}</div>
        </div>
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase tracking-widest mb-1">MQTT Status</div>
            <div class="text-sm font-bold text-success mt-2">● Running</div>
        </div>
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase tracking-widest mb-1">System Health</div>
            <div class="text-2xl font-bold text-success">99.8%</div>
            <div class="text-xs text-base-content/40">Uptime</div>
        </div>
    </div>

    {{-- Global Live Feed --}}
    @if($latestGlobalNode)
        <div class="bg-primary/10 border-l-4 border-primary p-5 rounded-2xl mb-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xs font-bold text-primary uppercase tracking-widest flex items-center gap-1">
                    <x-icon name="o-bolt" class="w-4 h-4" /> Latest Global Packet
                </h2>
                <div class="text-xs text-base-content/50">
                    {{ $latestGlobalNode->last_status_at->diffForHumans() }}
                    ({{ $latestGlobalNode->last_status_at->format('H:i:s') }})
                </div>
            </div>
            <div class="text-2xl font-bold mb-2">
                {{ $latestGlobalNode->sensor_reading }}
                <span class="text-sm font-normal text-base-content/50 ml-2">Battery: {{ $latestGlobalNode->battery }}</span>
            </div>
            <div class="flex flex-wrap gap-4 text-xs text-base-content/60">
                <span><strong>Faculty:</strong> {{ $latestGlobalNode->classroom->building->client->name ?? '-' }}</span>
                <span><strong>Building:</strong> {{ $latestGlobalNode->classroom->building->name ?? '-' }}</span>
                <span><strong>Room:</strong> {{ $latestGlobalNode->classroom->name ?? '-' }}</span>
                <span><strong>Node:</strong> #{{ $latestGlobalNode->id }}</span>
            </div>
        </div>
    @else
        <x-alert title="No Data Yet" description="No hardware has transmitted data to the system." icon="o-wifi" class="alert-warning mb-6 shadow-sm rounded-2xl" />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Pending Approvals --}}
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold">Pending Client Approvals</h3>
                <a href="{{ route('admin.client') }}" class="text-xs text-primary hover:underline">View all</a>
            </div>
            @forelse($pendingApprovals as $client)
                <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                    <div>
                        <div class="font-semibold text-sm">{{ $client->name }}</div>
                        <div class="text-xs text-base-content/50">{{ $client->user?->email }}</div>
                        <div class="text-xs text-base-content/40">{{ $client->created_at->format('d/m/Y') }}</div>
                    </div>
                    <span class="badge badge-warning text-white text-xs animate-pulse">Pending</span>
                </div>
            @empty
                <div class="text-center text-base-content/40 py-6 text-sm">No pending approvals 🎉</div>
            @endforelse
        </div>

        {{-- User Distribution --}}
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4">
            <h3 class="font-bold mb-4">User Distribution</h3>
            <div class="flex gap-6 items-center">
                @php
                    $total = collect($userDistribution)->sum('count') ?: 1;
                    $offset = 0;
                    $circumference = 2 * pi() * 40;
                @endphp
                <div class="relative w-32 h-32 shrink-0">
                    <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="18"/>
                        @foreach($userDistribution as $item)
                            @php
                                $pct = $item['count'] / $total;
                                $dash = $pct * $circumference;
                                $gap = $circumference - $dash;
                            @endphp
                            <circle cx="50" cy="50" r="40" fill="none"
                                stroke="{{ $item['color'] }}" stroke-width="18"
                                stroke-dasharray="{{ $dash }} {{ $gap }}"
                                stroke-dashoffset="-{{ $offset }}"
                            />
                            @php $offset += $dash; @endphp
                        @endforeach
                    </svg>
                </div>
                <div class="space-y-2">
                    @foreach($userDistribution as $item)
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-3 h-3 rounded-full shrink-0" style="background:{{ $item['color'] }}"></span>
                            <span>{{ $item['role'] }}</span>
                            <span class="font-bold ml-4">{{ $item['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- System Health --}}
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4">
            <h3 class="font-bold mb-4">System Health</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach(['API', 'Database', 'IoT Nodes', 'MQTT'] as $service)
                    <div class="bg-base-200 rounded-xl p-3 flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                        <span class="text-sm font-medium">{{ $service }}</span>
                        <span class="text-xs text-success ml-auto">Online</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Active Alerts --}}
        <div class="bg-base-100 rounded-2xl shadow border border-base-200 p-4">
            <h3 class="font-bold mb-4">Active Alerts</h3>
            @foreach($alerts as $alert)
                <div class="flex items-center gap-3 py-2 border-b border-base-200 last:border-0">
                    <span class="badge {{ $alert['color'] }} text-white text-xs">{{ $alert['priority'] }}</span>
                    <span class="text-sm flex-1">{{ $alert['desc'] }}</span>
                    <span class="text-xs text-base-content/40">{{ $alert['time'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Per Faculty Cards --}}
    <div class="mb-2 font-bold text-base-content/50 uppercase tracking-widest text-xs">Faculty Overview</div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($clients as $client)
            <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 overflow-hidden hover:shadow-md transition-shadow">
                <div class="px-5 pt-4 pb-3 border-b border-base-200">
                    <h3 class="font-bold">{{ $client->name }}</h3>
                    <div class="text-xs text-base-content/40 font-mono">{{ $client->code }}</div>
                </div>
                <div class="grid grid-cols-3 border-b border-base-200">
                    <div class="px-3 py-3 text-center border-r border-base-200">
                        <div class="text-xl font-bold">{{ $client->total_buildings }}</div>
                        <div class="text-[10px] text-base-content/50 uppercase tracking-widest">Buildings</div>
                    </div>
                    <div class="px-3 py-3 text-center border-r border-base-200">
                        <div class="text-xl font-bold">{{ $client->total_classrooms }}</div>
                        <div class="text-[10px] text-base-content/50 uppercase tracking-widest">Rooms</div>
                    </div>
                    <div class="px-3 py-3 text-center">
                        <div class="text-xl font-bold text-primary">{{ $client->total_nodes }}</div>
                        <div class="text-[10px] text-primary/60 uppercase tracking-widest">Nodes</div>
                    </div>
                </div>
                <div class="px-5 py-3 border-b border-base-200">
                    <div class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest mb-2">Latest Activity</div>
                    @if($client->latest_node)
                        <div class="text-base font-bold flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-success animate-pulse shrink-0"></span>
                            {{ $client->latest_node->sensor_reading }}
                        </div>
                        <div class="text-xs text-base-content/50 mt-1 flex justify-between">
                            <span>{{ $client->latest_node->classroom->name ?? '-' }}</span>
                            <span>{{ $client->latest_node->last_status_at->diffForHumans() }}</span>
                        </div>
                    @else
                        <div class="text-xs text-base-content/40 italic">No data yet.</div>
                    @endif
                </div>
                <div class="px-5 py-3">
                    <div class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest mb-2">History Trend</div>
                    <div class="flex items-end gap-0.5 h-10 w-full">
                        @if(count($client->graph_data) > 0)
                            @php $maxVal = max($client->graph_data) ?: 1; @endphp
                            @foreach($client->graph_data as $val)
                                <div class="flex-1 bg-primary/50 rounded-t-sm hover:bg-primary transition-all duration-300"
                                     style="height: {{ ($val / $maxVal) * 100 }}%; min-height: 2px;" title="{{ $val }}"></div>
                            @endforeach
                        @else
                            <div class="w-full text-center text-xs text-base-content/30">Waiting for history...</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center text-base-content/40 py-12">No faculty clients registered yet.</div>
        @endforelse
    </div>
</div>
