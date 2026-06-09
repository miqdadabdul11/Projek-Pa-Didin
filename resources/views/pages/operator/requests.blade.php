<?php

use Livewire\Component;
use App\Models\BEMS\NodeRequest;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $client_id;
    public string $filter = 'all'; // all, pending, approved, rejected

    public function mount(): void
    {
        $this->client_id = Auth::user()->client_id;
    }

    public function approveRequest(int $id): void
    {
        $req = NodeRequest::findOrFail($id);
        $req->update(['status' => 'approved']);
        $this->success('Request approved!');
    }

    public function rejectRequest(int $id): void
    {
        $req = NodeRequest::findOrFail($id);
        $req->update(['status' => 'rejected']);
        $this->warning('Request rejected.');
    }

    public function with(): array
    {
        $requests = NodeRequest::whereHas('node.classroom.building', function ($q) {
                $q->where('client_id', $this->client_id);
            })
            ->with(['node.classroom.building', 'user'])
            ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->latest()
            ->get();

        return [
            'requests'    => $requests,
            'filterOptions' => [
                ['id' => 'all',      'name' => 'All Requests'],
                ['id' => 'pending',  'name' => 'Pending'],
                ['id' => 'approved', 'name' => 'Approved'],
                ['id' => 'rejected', 'name' => 'Rejected'],
            ],
            'pendingCount' => NodeRequest::whereHas('node.classroom.building', fn($q) =>
                $q->where('client_id', $this->client_id)
            )->where('status', 'pending')->count(),
        ];
    }
};
?>

<div>
    <x-header title="Node Requests" subtitle="Manage viewer requests for node operations" separator />

    {{-- Filter --}}
    <div class="flex items-center gap-3 mb-4">
        <x-select
            wire:model.live="filter"
            :options="$filterOptions"
            option-value="id"
            option-label="name"
            icon="o-funnel"
            class="w-48"
        />
        @if($pendingCount > 0)
            <x-badge value="{{ $pendingCount }} pending" class="badge-warning" />
        @endif
    </div>

    <x-card shadow>
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Requested By</th>
                        <th class="bg-transparent border-none">Node</th>
                        <th class="bg-transparent border-none">Location</th>
                        <th class="bg-transparent border-none">Action</th>
                        <th class="bg-transparent border-none">Status</th>
                        <th class="bg-transparent border-none">Time</th>
                        <th class="bg-transparent border-none text-right">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr class="border-none">
                            <td class="border-none">
                                <div class="font-semibold">{{ $req->user->name }}</div>
                                <div class="text-xs text-base-content/50">{{ $req->user->email }}</div>
                            </td>
                            <td class="border-none">
                                <div class="font-semibold">{{ $req->node->name }}</div>
                                <div class="text-xs font-mono text-base-content/50">#{{ $req->node_id }}</div>
                            </td>
                            <td class="border-none text-sm">
                                <div>{{ $req->node->classroom->building->name ?? '-' }}</div>
                                <div class="text-xs text-base-content/50">{{ $req->node->classroom->name ?? '-' }}</div>
                            </td>
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
                            <td class="border-none text-right">
                                @if($req->status === 'pending')
                                    <div class="flex gap-1 justify-end">
                                        <x-button
                                            wire:click="approveRequest({{ $req->id }})"
                                            icon="o-check"
                                            class="btn-ghost btn-sm btn-circle border-none shadow-none text-success"
                                            tooltip="Approve"
                                            spinner
                                        />
                                        <x-button
                                            wire:click="rejectRequest({{ $req->id }})"
                                            icon="o-x-mark"
                                            class="btn-ghost btn-sm btn-circle border-none shadow-none text-error"
                                            tooltip="Reject"
                                            spinner
                                        />
                                    </div>
                                @else
                                    <span class="text-xs text-base-content/30">Done</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-base-content/50 py-8 border-none">
                                No requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>