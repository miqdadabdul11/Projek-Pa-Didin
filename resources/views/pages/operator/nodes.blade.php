<?php

use Livewire\Component;
use App\Models\BEMS\Node;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use App\Models\BEMS\NodeRequest;

new class extends Component
{
    use Toast;
    
    public $client_id;
    public string $search = '';

    public function approveRequest($requestId) {
        $req = NodeRequest::find($requestId);
        if ($req) {
            $req->update(['status' => 'approved']);
            if ($req->action === 'ON') $this->turnOn($req->node_id);
            if ($req->action === 'OFF') $this->turnOff($req->node_id);
            if ($req->action === 'ACTION') $this->triggerAction($req->node_id);
            $this->success('Request approved and executed!');
        }
    }

    public function denyRequest($requestId) {
        $req = NodeRequest::find($requestId);
        if ($req) {
            $req->update(['status' => 'rejected']);
            $this->error('Request rejected.');
        }
    }

    public function mount() {
        $this->client_id = Auth::user()->client_id; 
        if(!$this->client_id) {
            $this->error('System error: Operator account is not linked to any client.');
        }
    }

    public function turnOn($id) {
        $this->success("ON command sent to Node #{$id}");
    }

    public function turnOff($id) {
        $this->warning("OFF command sent to Node #{$id}");
    }

    public function triggerAction($id) {
        $this->info("Custom action executed for Node #{$id}");
    }

    public function with(): array {
        $nodes = Node::whereHas('classroom.building', function ($query) {
            $query->where('client_id', $this->client_id);
        })
        ->with(['classroom.building'])
        ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
            ->orWhere('microcontroller_chip', 'like', "%{$this->search}%")
            ->orWhereHas('classroom', fn($q2) => $q2->where('name', 'like', "%{$this->search}%")))
        ->orderBy('classroom_id')->get();

        $pendingRequests = NodeRequest::where('status', 'pending')
            ->whereHas('node.classroom.building', function ($query) {
                $query->where('client_id', $this->client_id);
            })
            ->with(['node', 'user'])
            ->get();

        return [
            'nodes' => $nodes,
            'pendingRequests' => $pendingRequests
        ];
    }
};
?>

<div>
    <x-header title="Hardware Control Panel" subtitle="Telemetry monitoring and active sensor node control" separator />

    {{-- Pending Requests --}}
    @if($pendingRequests->count() > 0)
        <x-card shadow class="mb-6 rounded-2xl">
            <h3 class="font-bold flex items-center gap-2 mb-4 text-warning">
                <x-icon name="o-bell-alert" class="w-5 h-5 animate-bounce" />
                {{ $pendingRequests->count() }} Pending Action Request(s)
            </h3>
            <table class="table w-full text-sm">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Requested By</th>
                        <th class="bg-transparent border-none">Action</th>
                        <th class="bg-transparent border-none text-right">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRequests as $req)
                        <tr class="border-none">
                            <td class="border-none">
                                <div class="font-semibold">{{ $req->user->name }}</div>
                                <div class="text-xs text-base-content/50">Node #{{ $req->node_id }}</div>
                            </td>
                            <td class="border-none">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-base-200 text-base-content">{{ $req->action }}</span>
                            </td>
                            <td class="border-none text-right">
                                <div class="flex gap-1 justify-end">
                                    <x-button wire:click="approveRequest({{ $req->id }})" icon="o-check" class="btn-ghost btn-sm btn-circle border-none shadow-none text-success" tooltip="Approve" />
                                    <x-button wire:click="denyRequest({{ $req->id }})" icon="o-x-mark" class="btn-ghost btn-sm btn-circle border-none shadow-none text-error" tooltip="Reject" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif

    <x-card shadow wire:poll.1s>
        {{-- Searchbar --}}
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
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">ID</th>
                        <th class="bg-transparent border-none">Location</th>
                        <th class="bg-transparent border-none">Hardware Info</th>
                        <th class="bg-transparent border-none">Last Telemetry</th>
                        <th class="bg-transparent border-none text-center">Control Panel</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nodes as $node)
                        <tr class="border-none">
                            <td class="border-none font-mono text-xs text-base-content/50">#{{ $node->id }}</td>
                            <td class="border-none">
                                <div class="font-semibold">{{ $node->classroom->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-base-content/50">{{ $node->classroom->building->name ?? '' }}</div>
                            </td>
                            <td class="border-none">
                                <div class="font-semibold">{{ $node->name }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-base-200 text-base-content/60">{{ $node->microcontroller_chip ?? 'No chip info' }}</span>
                            </td>
                            <td class="border-none">
                                @if($node->last_status_at)
                                    <div class="text-success text-xs font-semibold">
                                        {{ $node->last_status_at->format('H:i:s') }}
                                        <span class="text-base-content/40 font-normal">({{ $node->last_status_at->diffForHumans() }})</span>
                                    </div>
                                    <div class="text-xs mt-1">Data: <span class="font-bold">{{ $node->sensor_reading ?? '--' }}</span></div>
                                    <div class="text-xs">Battery: {{ $node->battery ?? '--' }}</div>
                                @else
                                    <div class="text-warning text-xs font-semibold">Waiting for data...</div>
                                @endif
                            </td>
                            <td class="border-none">
                                <div class="flex gap-1 justify-center">
                                    <x-button wire:click="turnOn({{ $node->id }})" label="ON" icon="o-power" class="btn-success btn-sm border-none shadow-none rounded-xl text-white" />
                                    <x-button wire:click="turnOff({{ $node->id }})" label="OFF" icon="o-power" class="btn-error btn-sm border-none shadow-none rounded-xl text-white" />
                                    <x-button wire:click="triggerAction({{ $node->id }})" label="ACT" icon="o-bolt" class="btn-info btn-sm border-none shadow-none rounded-xl text-white" tooltip="Send custom command" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-base-content/50 py-8 border-none">
                                No sensor nodes installed yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
