<?php

use Livewire\Component;
use App\Models\BEMS\Node;
use App\Models\BEMS\NodeRequest;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $client_id;
    public ?int $selectedNodeId = null;
    public string $action = 'ON';
    public bool $showModal = false;

    public function mount(): void
    {
        $this->client_id = Auth::user()->client_id;
    }

    public function openRequest(int $nodeId): void
    {
        $this->selectedNodeId = $nodeId;
        $this->action         = 'ON';
        $this->showModal      = true;
    }

    public function submitRequest(): void
    {
        $this->validate([
            'selectedNodeId' => 'required|exists:nodes,id',
            'action'         => 'required|in:ON,OFF,ACTION',
        ]);

        // Cek apakah sudah ada pending request untuk node ini
        $existing = NodeRequest::where('node_id', $this->selectedNodeId)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            $this->error('You already have a pending request for this node.');
            return;
        }

        NodeRequest::create([
            'node_id' => $this->selectedNodeId,
            'user_id' => Auth::id(),
            'action'  => $this->action,
            'status'  => 'pending',
        ]);

        $this->success('Request submitted! Waiting for operator approval.');
        $this->showModal = false;
        $this->reset('selectedNodeId', 'action');
    }

    public function with(): array
    {
        return [
            'nodes' => Node::whereHas('classroom.building', fn($q) =>
                    $q->where('client_id', $this->client_id)
                )
                ->with(['classroom.building'])
                ->get(),

            'myRequests' => NodeRequest::where('user_id', Auth::id())
                ->with(['node.classroom.building'])
                ->latest()
                ->take(10)
                ->get(),

            'actionOptions' => [
                ['id' => 'ON',     'name' => '⚡ Turn ON'],
                ['id' => 'OFF',    'name' => '🔴 Turn OFF'],
                ['id' => 'ACTION', 'name' => '⚙️ Custom Action'],
            ],
        ];
    }
};
?>

<div>
    <x-header title="Request Node Operation" subtitle="Submit a request to the operator to control a node" separator />

    {{-- Node List --}}
    <x-card shadow class="mb-6">
        <h3 class="font-bold mb-4">Available Nodes</h3>
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Node</th>
                        <th class="bg-transparent border-none">Location</th>
                        <th class="bg-transparent border-none">Last Telemetry</th>
                        <th class="bg-transparent border-none text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nodes as $node)
                        <tr class="border-none">
                            <td class="border-none">
                                <div class="font-semibold">{{ $node->name }}</div>
                                <div class="text-xs text-base-content/50">{{ $node->microcontroller_chip ?? '-' }}</div>
                            </td>
                            <td class="border-none">
                                <div>{{ $node->classroom->building->name ?? '-' }}</div>
                                <div class="text-xs text-base-content/50">{{ $node->classroom->name ?? '-' }}</div>
                            </td>
                            <td class="border-none">
                                @if($node->last_status_at)
                                    <div class="text-success text-xs font-semibold">{{ $node->last_status_at->diffForHumans() }}</div>
                                    <div class="text-xs">{{ $node->sensor_reading ?? '--' }}</div>
                                @else
                                    <span class="text-warning text-xs">Waiting for data...</span>
                                @endif
                            </td>
                            <td class="border-none text-right">
                                <x-button
                                    wire:click="openRequest({{ $node->id }})"
                                    label="Request"
                                    icon="o-paper-airplane"
                                    class="btn-primary btn-sm border-none shadow-none rounded-xl"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-base-content/50 py-8 border-none">
                                No nodes available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- My Recent Requests --}}
    <x-card shadow>
        <h3 class="font-bold mb-4">My Recent Requests</h3>
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Node</th>
                        <th class="bg-transparent border-none">Action</th>
                        <th class="bg-transparent border-none">Status</th>
                        <th class="bg-transparent border-none">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myRequests as $req)
                        <tr class="border-none">
                            <td class="border-none font-semibold">{{ $req->node->name }}</td>
                            <td class="border-none">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                    {{ $req->action === 'ON' ? 'bg-success/20 text-success' :
                                      ($req->action === 'OFF' ? 'bg-error/20 text-error' : 'bg-info/20 text-info') }}">
                                    {{ $req->action }}
                                </span>
                            </td>
                            <td class="border-none">
                                @if($req->status === 'pending')
                                    <x-badge value="Pending" class="badge-warning" />
                                @elseif($req->status === 'approved')
                                    <x-badge value="Approved" class="badge-success" />
                                @else
                                    <x-badge value="Rejected" class="badge-error" />
                                @endif
                            </td>
                            <td class="border-none text-xs text-base-content/50">
                                {{ $req->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-base-content/50 py-8 border-none">
                                No requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Request Modal --}}
    <x-modal wire:model="showModal" title="Submit Node Request" box-class="rounded-2xl max-w-md">
        <x-form wire:submit="submitRequest">
            <x-select
                wire:model="action"
                label="Operation"
                :options="$actionOptions"
                option-value="id"
                option-label="name"
                required
            />
            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showModal', false)" class="btn-ghost border-none" />
                <x-button label="Submit Request" type="submit" icon="o-paper-airplane"
                    class="btn-primary border-none shadow-none rounded-xl" spinner="submitRequest" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>