<?php
use Livewire\Component;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
new class extends Component
{
    use Toast;
    
    public $enableClientCreate = false;
    
    public $code;
    public $name;
    public $expirity;
    public $admin_email;
    public $admin_password;

    #[On('openClientCreate')]
    public function openForm(){
        $this->enableClientCreate = true;
    }

    public function saveClient(){
        $this->validate([
            'code' => 'required|string|unique:bems_clients,code',
            'name' => 'required|string',
            'expirity' => 'required|date',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|min:6',
        ]);
        $user = User::create([
            'name' => 'Admin ' . $this->name,
            'email' => $this->admin_email,
            'password' => Hash::make($this->admin_password)
        ]);
        $client = Client::create([
            'code' => $this->code,
            'name' => $this->name,
            'expirity' => $this->expirity,
            'user_id' => $user->id, 
        ]);
        $user->update(['client_id' => $client->id]);
        $user->assignRole('client');
        $this->reset(['code', 'name', 'expirity', 'admin_email', 'admin_password']);
        $this->enableClientCreate = false;
        $this->success('Client profile and Admin account successfully created!');
    }
};
?>
<div>
    <x-modal wire:model="enableClientCreate" title="Add New Client" class="backdrop-blur-sm" box-class="rounded-2xl max-w-3xl">
        <x-form wire:submit="saveClient">
            <div class="text-xs font-bold text-base-content/50 uppercase tracking-widest mb-4 border-b pb-2">1. Institution Profile</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <x-input wire:model="code" label="Client Code (e.g., FPTI)" icon="o-building-office" required />
                <x-input wire:model="name" label="Institution Name" icon="o-tag" required />
                <x-datetime wire:model="expirity" label="Access Expiry Date" icon="o-calendar" required />
            </div>
            <div class="text-xs font-bold text-base-content/50 uppercase tracking-widest mb-4 border-b pb-2">2. Client Admin Account</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <x-input wire:model="admin_email" label="Admin Email" placeholder="admin@institution.com" icon="o-envelope" required />
                <x-password wire:model="admin_password" label="Password" right icon="o-key" required />
            </div>
            <x-slot:actions>
                <x-button wire:click="$toggle('enableClientCreate')" label="Cancel" class="btn-ghost border-none" />
                <x-button type="submit" label="Save Client" icon="o-check" class="btn-success border-none shadow-none rounded-xl" spinner="saveClient" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
