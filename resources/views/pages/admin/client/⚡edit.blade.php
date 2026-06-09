<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;
new class extends Component
{
    public $clientEditModal = false;
    public $code;
    public $name;
    public $expirity;
    public $clientId;
    use Toast;
    #[On('enableEditClient')]
    public function enableEditClient ($clientId){
        $this->clientId = $clientId;
        if($this->clientEditModal == false){
            $client = Client::find($clientId);
            $this->code = $client->code;
            $this->name = $client->name;
            $this->expirity = $client->expirity;
            $this->clientEditModal = true;
        }else{
            $this->clientEditModal = false;
        }
    }
    public function updateClient(){
        $this->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'expirity' => 'required|date',
        ]);
        Client::find($this->clientId)->update([
            'code' => $this->code,
            'name' => $this->name,
            'expirity' => $this->expirity,
        ]);
        $this->code = null;
        $this->name = null;
        $this->expirity = null;
        $this->success('Client data has been updated!');
        $this->clientEditModal = false;
        $this->dispatch('refreshIndexClient');
    }
};
?>
<div>
    <x-modal wire:model="clientEditModal" title="Edit Client" box-class="rounded-2xl max-w-2xl">
        <x-form wire:submit="updateClient">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <x-input wire:model="code" label="Client Code" icon="o-building-office" />
                <x-input wire:model="name" label="Institution Name" icon="o-tag" />
            </div>
            <x-datetime label="Access Expiry Date" wire:model="expirity" icon="o-calendar" />

            <x-slot:actions>
                <x-button wire:click="$toggle('clientEditModal')" label="Cancel" class="btn-ghost border-none" />
                <x-button type="submit" label="Save Changes" icon="o-check" class="btn-success border-none shadow-none rounded-xl" spinner="updateClient" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
