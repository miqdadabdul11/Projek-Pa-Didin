<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $name;
    public $email;
    public $password;
    public $role_name;
    public string $search = '';
    public $showCreateModal = false;

    public function getAvailableRolesProperty() {
        return [
            ['id' => 'operator', 'name' => 'Operator'],
            ['id' => 'maintenance', 'name' => 'Maintenance'],
            ['id' => 'viewer', 'name' => 'Viewer'],
        ];
    }

    public function createStaff() {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_name' => 'required|in:operator,maintenance,viewer',
        ]);

        $clientId = Auth::user()->client->id ?? null;

        if (!$clientId) {
            $this->error('System error: Client profile not found.');
            return;
        }

        $newStaff = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'client_id' => $clientId,
        ]);

        $newStaff->assignRole($this->role_name);

        $this->reset(['name', 'email', 'password', 'role_name']);
        $this->showCreateModal = false;
        $this->success('Staff account created successfully!');
    }

    public function deleteStaff($userId) {
        $staff = User::find($userId);
        
        if ($staff && $staff->client_id === Auth::user()->client->id) {
            $staff->delete();
            $this->warning('Staff account has been deleted.');
        } else {
            $this->error('Access denied.');
        }
    }

    public function impersonateStaff($userId) {
        $staff = User::find($userId);
        
        if ($staff && $staff->client_id === Auth::user()->client->id) {
            session()->put('impersonated_by', Auth::id());
            Auth::login($staff);

            if ($staff->hasRole('operator')) {
                return redirect()->route('operator');
            } elseif ($staff->hasRole('maintenance')) {
                return redirect()->route('maintenance');
            } else {
                return redirect()->route('viewer');
            }

        } else {
            $this->error('Access denied: Invalid staff.');
        }
    }

    public function with(): array {
        $clientId = Auth::user()->client->id ?? null;
        
        $staffMembers = User::where('client_id', $clientId)
                            ->with('roles')
                            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                            ->get();

        return [
            'staffMembers' => $staffMembers,
            'availableRoles' => $this->availableRoles,
        ];
    }
};
?>

<div>
    <x-header title="Staff Access Management" subtitle="Manage Operator, Maintenance, and Viewer accounts" separator />

    {{-- Modal Add Staff --}}
    <x-modal wire:model="showCreateModal" title="Add New Staff" box-class="rounded-2xl max-w-2xl">
        <x-form wire:submit="createStaff">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Full Name" wire:model="name" icon="o-user" placeholder="e.g., John Doe" required />
                <x-input label="Email Address" wire:model="email" icon="o-envelope" placeholder="john@bems.id" required />
                <x-select
                    label="Access Role"
                    wire:model="role_name"
                    :options="$availableRoles"
                    option-value="id"
                    option-label="name"
                    placeholder="Select role..."
                    icon="o-shield-check"
                    required />
                <x-password label="Password" wire:model="password" icon="o-key" right required />
            </div>
            <x-slot:actions>
                <x-button label="Cancel" wire:click="$toggle('showCreateModal')" class="btn-ghost border-none" />
                <x-button label="Create Account" type="submit" icon="o-user-plus" class="btn-primary border-none shadow-none rounded-xl" spinner="createStaff" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-card shadow>
        {{-- Toolbar --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1">
                <x-input
                    wire:model.live.debounce="search"
                    placeholder="Search by name or email..."
                    icon="o-magnifying-glass"
                    class="w-full bg-base-200 border-none rounded-xl"
                    clearable
                />
            </div>
            <x-button
                wire:click="$toggle('showCreateModal')"
                label="Add New Staff"
                icon="o-plus"
                class="btn-success border-none shadow-none rounded-xl shrink-0"
            />
        </div>

        {{-- Table --}}
        <div class="">
            <table class="table w-full table-compact">
                <thead>
                    <tr class="text-base-content/60 text-xs uppercase tracking-widest">
                        <th class="bg-transparent border-none">Name & Email</th>
                        <th class="bg-transparent border-none">Role</th>
                        <th class="bg-transparent border-none">Status</th>
                        <th class="bg-transparent border-none text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                        <tr class="border-none">
                            <td class="border-none">
                                <div class="font-semibold">{{ $staff->name }}</div>
                                <div class="text-sm text-base-content/50">{{ $staff->email }}</div>
                            </td>
                            <td class="border-none">
                                @php $roleStr = $staff->roles->first()->name ?? 'No Role'; @endphp
                                @if($roleStr === 'operator')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #8CB4BA; color: #3a4664;">Operator</span>
                                @elseif($roleStr === 'maintenance')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #DDCBBA; color: #3a4664;">Maintenance</span>
                                @elseif($roleStr === 'viewer')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #ABC4B2; color: #3a4664;">Viewer</span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #C0DDA6; color: #3a4664;">Admin/Client</span>
                                @endif
                            </td>
                            <td class="border-none">
                                <div class="flex items-center text-xs text-success">
                                    <span class="w-2 h-2 rounded-full bg-success mr-2"></span> Active
                                </div>
                            </td>
                            <td class="border-none">
                                <div class="flex justify-end gap-1">
                                    @if(auth()->id() !== $staff->id)
                                        <x-button
                                            wire:click="impersonateStaff({{ $staff->id }})"
                                            icon="o-arrow-right-on-rectangle"
                                            class="btn-ghost btn-sm btn-circle border-none shadow-none text-info"
                                            tooltip="Login as this staff"
                                        />
                                        <x-button
                                            wire:click="deleteStaff({{ $staff->id }})"
                                            icon="o-trash"
                                            class="btn-ghost btn-sm btn-circle border-none shadow-none text-error"
                                            tooltip="Delete account"
                                            wire:confirm="Are you sure you want to delete this staff account?"
                                        />
                                    @else
                                        <span class="text-xs text-base-content/40 font-semibold uppercase">YOU</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-base-content/50 py-8 border-none">
                                No staff registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
