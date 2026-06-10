<?php
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $showModal = false;
    public ?int $editId = null;
    public string $search = '';

    protected function rules(): array
    {
        return [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email' . ($this->editId ? ",{$this->editId}" : ''),
            'password' => $this->editId ? 'nullable|min:6|confirmed' : 'required|min:6|confirmed',
        ];
    }

    public function getClientId(): int
    {
        return Client::where('user_id', Auth::id())->value('id');
    }

    public function getUsersProperty()
    {
        $clientId = $this->getClientId();
        return User::where('client_id', $clientId)
            ->whereHas('roles', fn($q) => $q->where('name', 'maintenance'))
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
            }))->with('roles')->latest()->get();
    }

    public function save(): void
    {
        $this->validate();
        $clientId = $this->getClientId();

        if ($this->editId) {
            $user = User::findOrFail($this->editId);
            $user->name = $this->name;
            $user->email = $this->email;
            if ($this->password) $user->password = Hash::make($this->password);
            $user->save();
            $this->success('Maintenance updated.');
        } else {
            $user = User::create([
                'name'        => $this->name,
                'email'       => $this->email,
                'password'    => Hash::make($this->password),
                'client_id'   => $clientId,
                'is_approved' => true,
            ]);
            $user->assignRole('maintenance');
            $this->success('Maintenance created.');
        }

        $this->showModal = false;
        $this->reset('name', 'email', 'password', 'password_confirmation', 'editId');
    }

    public function openCreate(): void
    {
        $this->reset('name', 'email', 'password', 'password_confirmation', 'editId');
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->reset('password', 'password_confirmation');
        $this->showModal = true;
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
        $this->warning('Maintenance deleted.');
    }
};
?>

<div>
    <x-header title="Maintenance Management" separator>
        <x-slot:actions>
            <x-input wire:model.live.debounce="search" placeholder="Search..." icon="o-magnifying-glass" class="input-sm" />
            <x-button label="Add Maintenance" icon="o-plus" wire:click="openCreate" class="btn-primary btn-sm" />
        </x-slot:actions>
    </x-header>

    <div class="overflow-x-auto">
        <table class="table table-sm w-full">
            <thead>
                <tr class="text-xs uppercase text-base-content/50">
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->users as $user)
                    <tr class="hover:bg-base-200/50">
                        <td class="font-semibold">{{ $user->name }}</td>
                        <td class="text-xs text-base-content/50">{{ $user->email }}</td>
                        <td><span class="badge badge-success text-white text-xs">Active</span></td>
                        <td>
                            <x-button icon="o-pencil" wire:click="openEdit({{ $user->id }})" class="btn-ghost btn-xs" />
                            <x-button icon="o-trash" wire:click="delete({{ $user->id }})" wire:confirm="Delete this maintenance?" class="btn-ghost btn-xs text-error" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4 text-base-content/40">No maintenances yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal wire:model="showModal" title="{{ $editId ? 'Edit' : 'Add' }} Maintenance">
        <div class="space-y-3">
            <x-input wire:model="name" label="Full Name" placeholder="Full name" />
            <x-input wire:model="email" label="Email" type="email" placeholder="email@example.com" />
            <x-input wire:model="password" label="{{ $editId ? 'New Password (leave blank)' : 'Password' }}" type="password" placeholder="••••••••" />
            @if(!$editId)
                <x-input wire:model="password_confirmation" label="Confirm Password" type="password" placeholder="••••••••" />
            @endif
        </div>
        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('showModal', false)" />
            <x-button label="{{ $editId ? 'Update' : 'Create' }}" wire:click="save" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
