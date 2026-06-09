<?php

use Livewire\Component;
use App\Models\BEMS\MqttBroker;
use App\Models\BEMS\Node;
use App\Models\BEMS\Classroom;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    // Broker form
    public string $name        = '';
    public string $host        = '';
    public int    $port        = 1883;
    public string $username    = '';
    public string $password    = '';
    public string $description = '';
    public bool   $showBrokerModal  = false;
    public ?int   $editBrokerId     = null;

    // Node MQTT form
    public bool  $showNodeModal   = false;
    public ?int  $editNodeId      = null;
    public string $mqtt_topic     = '';
    public int    $mqtt_qos       = 0;
    public bool   $mqtt_retain    = false;

    // ─── Broker CRUD ────────────────────────────────────────────

    public function openCreateBroker(): void
    {
        $this->resetBrokerForm();
        $this->editBrokerId   = null;
        $this->showBrokerModal = true;
    }

    public function openEditBroker(int $id): void
    {
        $broker = MqttBroker::findOrFail($id);
        $this->editBrokerId  = $id;
        $this->name          = $broker->name;
        $this->host          = $broker->host;
        $this->port          = $broker->port;
        $this->username      = $broker->username ?? '';
        $this->password      = '';
        $this->description   = $broker->description ?? '';
        $this->showBrokerModal = true;
    }

    public function saveBroker(): void
    {
        $data = $this->validate([
            'name'        => 'required|string|max:255',
            'host'        => 'required|string|max:255',
            'port'        => 'required|integer|min:1|max:65535',
            'username'    => 'nullable|string|max:255',
            'password'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($this->editBrokerId) {
            $broker = MqttBroker::findOrFail($this->editBrokerId);
            // Jangan overwrite password kalau kosong
            if (empty($data['password'])) unset($data['password']);
            $broker->update($data);
            $this->success('Broker updated!');
        } else {
            MqttBroker::create($data);
            $this->success('Broker created!');
        }

        $this->showBrokerModal = false;
        $this->resetBrokerForm();
    }

    public function deleteBroker(int $id): void
    {
        MqttBroker::findOrFail($id)->delete();
        $this->warning('Broker deleted.');
    }

    public function setActiveBroker(int $id): void
    {
        MqttBroker::setActive($id);
        $this->success('Active broker updated!');
    }

    private function resetBrokerForm(): void
    {
        $this->name = $this->host = $this->username = $this->password = $this->description = '';
        $this->port = 1883;
    }

    // ─── Node MQTT Config ───────────────────────────────────────

    public function openEditNode(int $id): void
    {
        $node = Node::findOrFail($id);
        $this->editNodeId   = $id;
        $this->mqtt_topic   = $node->mqtt_topic ?? '';
        $this->mqtt_qos     = $node->mqtt_qos ?? 0;
        $this->mqtt_retain  = $node->mqtt_retain ?? false;
        $this->showNodeModal = true;
    }

    public function saveNodeMqtt(): void
    {
        $data = $this->validate([
            'mqtt_topic'  => 'required|string|max:255',
            'mqtt_qos'    => 'required|integer|in:0,1,2',
            'mqtt_retain' => 'boolean',
        ]);

        Node::findOrFail($this->editNodeId)->update($data);
        $this->success('Node MQTT config saved!');
        $this->showNodeModal = false;
    }

    // ─── Render ─────────────────────────────────────────────────

    public function with(): array
    {
        return [
            'brokers' => MqttBroker::latest()->get(),
            'nodes'   => Node::with('classroom.building')->latest()->get(),
            'qosOptions' => [
                ['id' => 0, 'name' => 'QoS 0 — At most once'],
                ['id' => 1, 'name' => 'QoS 1 — At least once'],
                ['id' => 2, 'name' => 'QoS 2 — Exactly once'],
            ],
        ];
    }
};
?>

<div>
    <x-header title="MQTT Configuration" subtitle="Manage brokers and node topics" separator />

    {{-- ═══ BROKER SECTION ══════════════════════════════════════ --}}

    <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-lg">Brokers</h2>
        <x-button label="Add Broker" icon="o-plus" wire:click="openCreateBroker"
            class="btn-success border-none shadow-none rounded-xl btn-sm" />
    </div>

    <x-card shadow class="mb-6">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Name</th>
                        <th class="bg-transparent border-none">Host : Port</th>
                        <th class="bg-transparent border-none">Username</th>
                        <th class="bg-transparent border-none">Status</th>
                        <th class="bg-transparent border-none text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brokers as $broker)
                        <tr class="border-none">
                            <td class="border-none">
                                <div class="font-semibold">{{ $broker->name }}</div>
                                @if($broker->description)
                                    <div class="text-xs text-base-content/50">{{ $broker->description }}</div>
                                @endif
                            </td>
                            <td class="border-none font-mono text-sm">
                                {{ $broker->host }}:{{ $broker->port }}
                            </td>
                            <td class="border-none text-sm">
                                {{ $broker->username ?? '-' }}
                            </td>
                            <td class="border-none">
                                @if($broker->is_active)
                                    <x-badge value="Active" class="badge-success" />
                                @else
                                    <x-button
                                        label="Set Active"
                                        wire:click="setActiveBroker({{ $broker->id }})"
                                        class="btn-ghost btn-xs border border-base-300 rounded-lg"
                                        spinner
                                    />
                                @endif
                            </td>
                            <td class="border-none text-right">
                                <div class="flex gap-1 justify-end">
                                    <x-button wire:click="openEditBroker({{ $broker->id }})" icon="o-pencil"
                                        class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                                    <x-button wire:click="deleteBroker({{ $broker->id }})" icon="o-trash"
                                        class="btn-ghost btn-sm btn-circle border-none shadow-none text-error"
                                        wire:confirm="Delete this broker?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-base-content/50 py-8 border-none">
                                No brokers configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- ═══ NODE MQTT SECTION ════════════════════════════════════ --}}

    <h2 class="font-bold text-lg mb-3">Node MQTT Topics</h2>

    <x-card shadow>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Node</th>
                        <th class="bg-transparent border-none">Location</th>
                        <th class="bg-transparent border-none">Topic</th>
                        <th class="bg-transparent border-none">QoS</th>
                        <th class="bg-transparent border-none">Retain</th>
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
                            <td class="border-none text-sm">
                                <div>{{ $node->classroom?->building?->name ?? '-' }}</div>
                                <div class="text-xs text-base-content/50">{{ $node->classroom?->name ?? '-' }}</div>
                            </td>
                            <td class="border-none font-mono text-sm">
                                {{ $node->mqtt_topic ?? '-' }}
                            </td>
                            <td class="border-none">
                                @if($node->mqtt_topic)
                                    <x-badge value="QoS {{ $node->mqtt_qos }}" class="badge-outline" />
                                @else
                                    <span class="text-base-content/30 text-sm">-</span>
                                @endif
                            </td>
                            <td class="border-none">
                                @if($node->mqtt_topic)
                                    <x-icon name="{{ $node->mqtt_retain ? 'o-check-circle' : 'o-x-circle' }}"
                                        class="w-5 h-5 {{ $node->mqtt_retain ? 'text-success' : 'text-base-content/30' }}" />
                                @else
                                    <span class="text-base-content/30 text-sm">-</span>
                                @endif
                            </td>
                            <td class="border-none text-right">
                                <x-button wire:click="openEditNode({{ $node->id }})" icon="o-cog-6-tooth"
                                    class="btn-ghost btn-sm btn-circle border-none shadow-none" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-base-content/50 py-8 border-none">
                                No nodes registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- ═══ BROKER MODAL ═════════════════════════════════════════ --}}

    <x-modal wire:model="showBrokerModal" title="{{ $editBrokerId ? 'Edit Broker' : 'Add Broker' }}" box-class="rounded-2xl max-w-lg">
        <x-form wire:submit="saveBroker">
            <x-input wire:model="name" label="Broker Name" placeholder="e.g., Broker Utama" icon="o-server" required />

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <x-input wire:model="host" label="Host" placeholder="broker.hivemq.com" icon="o-globe-alt" required />
                </div>
                <x-input wire:model="port" label="Port" type="number" placeholder="1883" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-input wire:model="username" label="Username" placeholder="(optional)" />
                <x-input wire:model="password" label="Password" type="password"
                    placeholder="{{ $editBrokerId ? 'Leave blank to keep' : '(optional)' }}" />
            </div>

            <x-textarea wire:model="description" label="Description" placeholder="Optional notes..." rows="2" />

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showBrokerModal', false)" class="btn-ghost border-none" />
                <x-button label="Save Broker" type="submit" icon="o-check"
                    class="btn-success border-none shadow-none rounded-xl" spinner="saveBroker" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- ═══ NODE MQTT MODAL ══════════════════════════════════════ --}}

    <x-modal wire:model="showNodeModal" title="Configure Node MQTT" box-class="rounded-2xl max-w-lg">
        <x-form wire:submit="saveNodeMqtt">
            <x-input wire:model="mqtt_topic" label="MQTT Topic" icon="o-hashtag"
                placeholder="e.g., bems/gedungA/ruang101/sensor" required />

            <x-select wire:model="mqtt_qos" label="QoS Level"
                :options="$qosOptions" option-value="id" option-label="name" required />

            <x-checkbox wire:model="mqtt_retain" label="Retain Message"
                hint="Broker will store the last message for new subscribers" />

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showNodeModal', false)" class="btn-ghost border-none" />
                <x-button label="Save Config" type="submit" icon="o-check"
                    class="btn-success border-none shadow-none rounded-xl" spinner="saveNodeMqtt" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>