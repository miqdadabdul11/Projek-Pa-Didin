<?php

use Livewire\Component;
use App\Models\BEMS\Building;
use App\Models\BEMS\Node;
use App\Models\BEMS\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public string $search = '';

    public function with(): array
    {
        $client = Auth::user()->client;

        if (!$client) {
            return ['latestNode' => null, 'buildings' => collect(), 'clientName' => 'Error', 'stats' => [], 'staff' => collect()];
        }

        $latestNode = Node::whereHas('classroom.building', fn($q) => $q->where('client_id', $client->id))
            ->whereNotNull('last_status_at')
            ->with(['classroom.building'])
            ->orderByDesc('last_status_at')
            ->first();

        $buildings = Building::where('client_id', $client->id)
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with(['classrooms.nodes'])
            ->get()
            ->map(function($building) {
                $building->total_classrooms = $building->classrooms->count();
                $allNodes = $building->classrooms->flatMap->nodes;
                $building->total_nodes = $allNodes->count();
                $latestNode = $allNodes->whereNotNull('last_status_at')->sortByDesc('last_status_at')->first();
                $building->latest_node = $latestNode;
                $building->status = $latestNode ? 'Active' : 'Idle';
                if ($latestNode) {
                    $logs = $latestNode->telemetryLogs()->latest()->take(12)->get()->reverse();
                    $building->graph_data = $logs->map(fn($log) => floatval($log->sensor_reading))->values()->toArray();
                } else {
                    $building->graph_data = [];
                }
                return $building;
            });

        $totalRooms = Classroom::whereHas('building', fn($q) => $q->where('client_id', $client->id))->count();
        $totalNodes = Node::whereHas('classroom.building', fn($q) => $q->where('client_id', $client->id))->count();
        $totalStaff = User::where('client_id', $client->id)->count();

        $staff = User::where('client_id', $client->id)
            ->with('roles')
            ->take(5)
            ->get();

        $userRoleDistribution = [
            ['role' => 'Operator',    'count' => User::where('client_id', $client->id)->role('operator')->count(),    'color' => '#6366f1'],
            ['role' => 'Maintenance', 'count' => User::where('client_id', $client->id)->role('maintenance')->count(), 'color' => '#f97316'],
            ['role' => 'Viewer',      'count' => User::where('client_id', $client->id)->role('viewer')->count(),      'color' => '#22c55e'],
        ];

        return [
            'clientName' => $client->name,
            'latestNode' => $latestNode,
            'buildings'  => $buildings,
            'staff'      => $staff,
            'userRoleDistribution' => $userRoleDistribution,
            'stats'      => [
                'buildings' => $buildings->count(),
                'rooms'     => $totalRooms,
                'nodes'     => $totalNodes,
                'staff'     => $totalStaff,
            ],
        ];
    }
};
?>

<div wire:poll.5s class="min-h-screen bg-[#0f1117] text-white p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Smart Campus IoT</h1>
            <p class="text-sm text-gray-400">{{ $clientName }} Dashboard</p>
        </div>
        <div class="relative">
            <input wire:model.live.debounce="search" type="text"
                placeholder="Search buildings, rooms, users..."
                class="bg-[#1a1d27] border border-gray-700 rounded-xl px-4 py-2 text-sm text-gray-300 w-72 focus:outline-none focus:border-indigo-500" />
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-[#1a1d27] rounded-2xl border border-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-white">{{ $stats['buildings'] }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-widest mt-1">Total Buildings</div>
            <div class="w-full h-1 bg-indigo-500/30 rounded-full mt-3"><div class="h-1 bg-indigo-500 rounded-full" style="width:70%"></div></div>
        </div>
        <div class="bg-[#1a1d27] rounded-2xl border border-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-white">{{ $stats['rooms'] }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-widest mt-1">Total Rooms</div>
            <div class="w-full h-1 bg-cyan-500/30 rounded-full mt-3"><div class="h-1 bg-cyan-500 rounded-full" style="width:60%"></div></div>
        </div>
        <div class="bg-[#1a1d27] rounded-2xl border border-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-white">{{ $stats['nodes'] }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-widest mt-1">IoT Nodes</div>
            <div class="w-full h-1 bg-purple-500/30 rounded-full mt-3"><div class="h-1 bg-purple-500 rounded-full" style="width:50%"></div></div>
        </div>
        <div class="bg-[#1a1d27] rounded-2xl border border-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-cyan-400">{{ $stats['staff'] }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-widest mt-1">Active Staff</div>
            <div class="w-full h-1 bg-cyan-500/30 rounded-full mt-3"><div class="h-1 bg-cyan-400 rounded-full" style="width:43%"></div></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-[#1a1d27] rounded-2xl border border-gray-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-white text-base">Campus Overview & Hierarchy</h3>
                <a href="{{ route('client.buildings') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-4 py-1.5 rounded-lg transition">MANAGE</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase text-gray-500 border-b border-gray-800">
                            <th class="pb-2 text-left font-medium">ID</th>
                            <th class="pb-2 text-left font-medium">Building Name</th>
                            <th class="pb-2 text-left font-medium">Floors</th>
                            <th class="pb-2 text-left font-medium">Total Rooms</th>
                            <th class="pb-2 text-left font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buildings as $building)
                            <tr class="border-b border-gray-800/50 hover:bg-gray-800/20 transition">
                                <td class="py-2.5 text-gray-500 text-xs">{{ $building->id }}</td>
                                <td class="py-2.5 font-medium text-white">{{ $building->name }}</td>
                                <td class="py-2.5 text-gray-300">{{ $building->floors ?? '-' }}</td>
                                <td class="py-2.5 text-gray-300">{{ $building->total_classrooms }}</td>
                                <td class="py-2.5">
                                    @if($building->status === 'Active')
                                        <span class="bg-emerald-500/20 text-emerald-400 text-xs px-2.5 py-1 rounded-full">Active</span>
                                    @else
                                        <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2.5 py-1 rounded-full">Configuring</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-gray-500 py-6 text-sm">No buildings yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-800">
                <div class="text-xs text-gray-500 uppercase tracking-widest mb-2">Buildings</div>
                @foreach($buildings->take(3) as $building)
                    <div class="text-gray-300 mb-1">
                        <div class="flex items-center gap-2 py-1 text-sm">
                            <span>🏠</span><span class="font-medium">{{ $building->name }}</span>
                        </div>
                        <div class="ml-6 text-gray-500 text-xs">
                            @foreach($building->classrooms->take(2) as $room)
                                <div class="py-0.5">📄 {{ $room->name }}</div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-[#1a1d27] rounded-2xl border border-gray-800 p-5">
            <h3 class="font-bold text-white text-base mb-4">Data Onboarding</h3>
            <div class="text-xs text-gray-500 uppercase tracking-widest mb-3">Import Building Data</div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <a href="{{ route('client.buildings.import') }}" class="bg-[#0f1117] border border-cyan-500/40 rounded-xl p-4 text-center hover:border-cyan-500 transition">
                    <div class="text-3xl mb-2">📊</div>
                    <div class="text-xs font-bold text-cyan-400">CSV</div>
                </a>
                <a href="{{ route('client.buildings.import') }}" class="bg-[#0f1117] border border-emerald-500/40 rounded-xl p-4 text-center hover:border-emerald-500 transition">
                    <div class="text-3xl mb-2">📗</div>
                    <div class="text-xs font-bold text-emerald-400">XLSX</div>
                </a>
            </div>
            <a href="{{ route('client.buildings.import') }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold text-center py-2.5 rounded-xl transition mb-4">IMPORT DATA</a>
            @if($latestNode)
                <div class="bg-indigo-500/10 border border-indigo-500/30 rounded-xl p-3">
                    <div class="text-lg font-bold text-indigo-400">{{ $latestNode->sensor_reading }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $latestNode->classroom->name ?? '-' }} • {{ $latestNode->last_status_at->diffForHumans() }}</div>
                </div>
            @else
                <div class="bg-gray-800/50 rounded-xl p-3 text-xs text-gray-500 text-center">Waiting for data...</div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-[#1a1d27] rounded-2xl border border-gray-800 p-5">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-bold text-white text-base">User & Permission Management</h3>
                <a href="{{ route('client.manageroles') }}" class="text-xs text-indigo-400 hover:underline">Manage</a>
            </div>
            <div class="text-xs text-gray-500 mb-4">Manage Operators, Manage Maintenance Staff, Manage Viewers</div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase text-gray-600 border-b border-gray-800">
                        <th class="pb-2 text-left">Name</th>
                        <th class="pb-2 text-left">Role</th>
                        <th class="pb-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                        @php $role = $member->roles->first()->name ?? 'No Role'; @endphp
                        <tr class="border-b border-gray-800/50">
                            <td class="py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-indigo-500/20 flex items-center justify-center text-xs text-indigo-400 font-bold">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                                    <div>
                                        <div class="font-medium text-white text-xs">{{ $member->name }}</div>
                                        <div class="text-gray-500 text-xs">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-2.5">
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $role === 'operator' ? 'bg-indigo-500/20 text-indigo-400' : ($role === 'maintenance' ? 'bg-orange-500/20 text-orange-400' : 'bg-emerald-500/20 text-emerald-400') }}">{{ ucfirst($role) }}</span>
                            </td>
                            <td class="py-2.5"><span class="text-xs text-emerald-400">● Active</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-gray-500 py-6 text-sm">No staff yet</td></tr>
                    @endforelse
                </tbody>
            </table>
            <a href="{{ route('client.manageroles') }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold text-center py-2.5 rounded-xl transition mt-4">REGISTER USERS & ASSIGN ROLES</a>
        </div>

        <div class="bg-[#1a1d27] rounded-2xl border border-gray-800 p-5">
            <h3 class="font-bold text-white text-base mb-4">Campus Analytics</h3>
            @php
                $total = collect($userRoleDistribution)->sum('count') ?: 1;
                $circumference = 2 * pi() * 30;
                $offset = 0;
            @endphp
            <div class="grid grid-cols-3 gap-3 mb-5">
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-2">Building Status</div>
                    <div class="relative w-14 h-14 mx-auto">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#1f2937" stroke-width="20"/>
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#6366f1" stroke-width="20" stroke-dasharray="{{ $circumference * 0.6 }} {{ $circumference * 0.4 }}"/>
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#22c55e" stroke-width="20" stroke-dasharray="{{ $circumference * 0.4 }} {{ $circumference * 0.6 }}" stroke-dashoffset="-{{ $circumference * 0.6 }}"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white">{{ $stats['buildings'] }}</div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-2">User Roles</div>
                    <div class="relative w-14 h-14 mx-auto">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#1f2937" stroke-width="20"/>
                            @php $offset = 0; @endphp
                            @foreach($userRoleDistribution as $item)
                                @php $dash = ($item['count']/$total) * $circumference; @endphp
                                <circle cx="50" cy="50" r="30" fill="none" stroke="{{ $item['color'] }}" stroke-width="20" stroke-dasharray="{{ $dash }} {{ $circumference - $dash }}" stroke-dashoffset="-{{ $offset }}"/>
                                @php $offset += $dash; @endphp
                            @endforeach
                        </svg>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-500 mb-2">Room Types</div>
                    <div class="relative w-14 h-14 mx-auto">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#1f2937" stroke-width="20"/>
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#6366f1" stroke-width="20" stroke-dasharray="{{ $circumference*0.4 }} {{ $circumference*0.6 }}"/>
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#22c55e" stroke-width="20" stroke-dasharray="{{ $circumference*0.35 }} {{ $circumference*0.65 }}" stroke-dashoffset="-{{ $circumference*0.4 }}"/>
                            <circle cx="50" cy="50" r="30" fill="none" stroke="#f97316" stroke-width="20" stroke-dasharray="{{ $circumference*0.25 }} {{ $circumference*0.75 }}" stroke-dashoffset="-{{ $circumference*0.75 }}"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-[#0f1117] rounded-xl p-3 border border-gray-800">
                    <div class="text-xl font-bold text-indigo-400">{{ $stats['buildings'] }}</div>
                    <div class="text-xs text-gray-500">Total Buildings</div>
                    <svg viewBox="0 0 60 20" class="w-full h-6 mt-1"><polyline points="0,15 10,10 20,12 30,5 40,8 50,3 60,6" fill="none" stroke="#6366f1" stroke-width="1.5"/></svg>
                </div>
                <div class="bg-[#0f1117] rounded-xl p-3 border border-gray-800">
                    <div class="text-xl font-bold text-cyan-400">{{ $stats['rooms'] }}</div>
                    <div class="text-xs text-gray-500">Total Rooms</div>
                    <svg viewBox="0 0 60 20" class="w-full h-6 mt-1"><polyline points="0,10 10,8 20,12 30,6 40,9 50,4 60,7" fill="none" stroke="#22d3ee" stroke-width="1.5"/></svg>
                </div>
                <div class="bg-[#0f1117] rounded-xl p-3 border border-gray-800">
                    <div class="text-xl font-bold text-purple-400">{{ $stats['nodes'] }}</div>
                    <div class="text-xs text-gray-500">IoT Nodes</div>
                    <svg viewBox="0 0 60 20" class="w-full h-6 mt-1"><polyline points="0,12 10,9 20,14 30,7 40,11 50,5 60,8" fill="none" stroke="#a855f7" stroke-width="1.5"/></svg>
                </div>
                <div class="bg-[#0f1117] rounded-xl p-3 border border-gray-800">
                    <div class="text-xl font-bold text-emerald-400">{{ $stats['staff'] }}</div>
                    <div class="text-xs text-gray-500">Active Staff</div>
                    <svg viewBox="0 0 60 20" class="w-full h-6 mt-1"><polyline points="0,14 10,11 20,13 30,8 40,10 50,5 60,7" fill="none" stroke="#22c55e" stroke-width="1.5"/></svg>
                </div>
            </div>
        </div>
    </div>
</div>
