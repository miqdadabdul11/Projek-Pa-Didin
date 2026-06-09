<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;

new #[Layout('layouts.app')] #[Title('Maintenance Management')] class extends Component
{
    use Toast;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $deleteId = null;
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

    public function getStaffProperty()
    {
        $clientId = $this->getClientId();
        return User::where('client_id', $clientId)
            ->whereHas('roles', fn($q) => $q->where('name', 'maintenance'))
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->get();
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
        $this->name   = $user->name;
        $this->email  = $user->email;
        $this->reset('password', 'password_confirmation');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $clientId = $this->getClientId();

        if ($this->editId) {
            $user = User::findOrFail($this->editId);
            $user->name  = $this->name;
            $user->email = $this->email;
            if ($this->password) {
                $user->password = Hash::make($this->password);
            }
            $user->save();
            $this->success('Maintenance staff updated.');
        } else {
            $user = User::create([
                'name'      => $this->name,
                'email'     => $this->email,
                'password'  => Hash::make($this->password),
                'client_id' => $clientId,
            ]);
            $user->assignRole('maintenance');
            $this->success('Maintenance account created.');
        }

        $this->showModal = false;
        $this->reset('name', 'email', 'password', 'password_confirmation', 'editId');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        User::findOrFail($this->deleteId)->delete();
        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->warning('Maintenance staff deleted.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('pages.client.users.maintenance', [
            'staff' => $this->staff,
        ]);
    }
};
?>

<div>
    <x-header title="Maintenance Management" subtitle="Manage maintenance staff accounts for your faculty" separator>
        <x-slot:actions>
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
            <x-button label="Add Maintenance" icon="o-plus" wire:click="openCreate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="[
            ['key' => 'name',       'label' => 'Name'],
            ['key' => 'email',      'label' => 'Email'],
            ['key' => 'created_at', 'label' => 'Created'],
        ]" :rows="$staff" striped>
            @scope('cell_created_at', $user)
                {{ $user->created_at->format('d M Y') }}
            @endscope
            @scope('actions', $user)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" wire:click="openEdit({{ $user->id }})"
                        class="btn-ghost btn-xs" tooltip="Edit" />
                    <x-button icon="o-trash" wire:click="confirmDelete({{ $user->id }})"
                        class="btn-ghost btn-xs text-error" tooltip="Delete" />
                </div>
            @endscope
        </x-table>

        @if($staff->isEmpty())
            <div class="text-center py-12 text-base-content/40">
                <x-icon name="o-cpu-chip" class="w-12 h-12 mx-auto mb-2 opacity-30" />
                <p class="text-sm">No maintenance staff yet. Click "Add Maintenance" to create one.</p>
            </div>
        @endif
    </x-card>

    {{-- Create / Edit Modal --}}
    <x-modal wire:model="showModal" :title="$editId ? 'Edit Maintenance' : 'Add Maintenance'" separator>
        <div class="space-y-4">
            <x-input label="Full Name" wire:model="name" placeholder="e.g. Andi Wijaya" icon="o-user" />
            <x-input label="Email" wire:model="email" type="email" placeholder="maintenance@example.com" icon="o-envelope" />
            <x-input label="{{ $editId ? 'New Password (leave blank to keep)' : 'Password' }}"
                wire:model="password" type="password" icon="o-lock-closed" />
            <x-input label="Confirm Password" wire:model="password_confirmation" type="password" icon="o-lock-closed" />
        </div>
        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('showModal', false)" />
            <x-button label="{{ $editId ? 'Update' : 'Create' }}" wire:click="save"
                class="btn-primary" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-modal>

    {{-- Delete Confirm Modal --}}
    <x-modal wire:model="showDeleteModal" title="Delete Maintenance Staff" separator>
        <p class="text-sm text-base-content/70">Are you sure? This action cannot be undone.</p>
        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('showDeleteModal', false)" />
            <x-button label="Delete" wire:click="delete" class="btn-error" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-modal>
</div>