<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

new class extends Component
{
    use WithPagination;
    use Toast;
    
    public string $search = '';
    
    // 1. Menambahkan kolom Status ke dalam Header Table
    public $headers = [
        ['key' => 'id', 'label' => '#', 'class' => 'w-1/12 hidden'],
        ['key' => 'code', 'label' => 'Code', 'class' => 'w-1/12'],
        ['key' => 'name','label' => 'Name', 'class' => 'w-3/12'],
        ['key' => 'user_id', 'label' => 'User ID', 'class' => 'w-1/12'],
        ['key' => 'status', 'label' => 'Account Status', 'class' => 'w-2/12'], // Kolom baru
        ['key' => 'expirity', 'label' => 'Expiry', 'class' => 'w-2/12'],
        ['key' => 'remain', 'label' => 'Remain', 'class' => 'w-2/12', 'align' => 'left'],
        ['key' => 'action', 'label' => 'Action', 'class' => 'w-2/12 text-right'],
    ];

    #[On('refreshIndexClient')]
    public function render()
    {
        // Data clients diambil sekaligus me-load data User di dalamnya untuk mengecek approval status
        $clients = Client::with('user')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->paginate(10);

        return $this->view(['clients' => $clients]);
    }

    // 2. Logika Fungsi ACC ditaruh di dalam body class component
    public function approveUser($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->update(['is_approved' => true]);
            $client = \App\Models\BEMS\Client::where('user_id', $user->id)->first();
            if ($client) { $user->update(['client_id' => $client->id]); }
            
            // Opsional: Jika menggunakan Spatie Laravel Permission
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('client');
            }

            $this->success("Akun {$user->name} berhasil di-aktifkan!", position: 'toast-top toast-center');
        } else {
            $this->error('User tidak ditemukan.');
        }
    }

    // 3. Logika Fungsi REJECT ditaruh di dalam body class component
    public function rejectUser($userId, $clientId)
    {
        // Hapus data client profile dan data user registrasinya
        $client = Client::find($clientId);
        if ($client) {
            $client->delete();
        }
        
        $user = User::find($userId);
        if ($user) {
            $user->delete();
        }

        $this->success("Pendaftaran akun telah ditolak dan dihapus.", position: 'toast-top toast-center');
    }

    public function deleteClient($clientId)
    {
        $client = Client::find($clientId);
        if ($client) {
            $userIdToDelete = $client->user_id;
            $client->delete();
            if ($userIdToDelete) {
                User::find($userIdToDelete)?->delete();
            }
            $this->success('Client profile and login credentials have been deleted');
        } else {
            $this->error('Client not found.');
        }
    }

    public function loginAs($clientId)
    {
        $client = Client::find($clientId);
        $user = User::find($client->user_id);
        
        if ($user && !$user->is_approved) {
            $this->error('Tidak bisa login as, akun ini belum di-ACC oleh Admin.');
            return;
        }

        Auth::login($user);
        $this->redirectRoute('client');
    }
};
?>

<div>
    <x-card title="Client Management" shadow separator>
        <livewire:pages::admin.client.create/>
        <livewire:pages::admin.client.edit/>
        
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex-1">
                <x-input
                    wire:model.live.debounce="search"
                    placeholder="Search by name or code..."
                    icon="o-magnifying-glass"
                    class="w-full bg-base-200 border-none rounded-xl"
                    clearable
                />
            </div>
            <x-button
                wire:click="$dispatch('openClientCreate')"
                label="Add New Client"
                icon="o-plus"
                class="btn-success border-none shadow-none rounded-xl shrink-0"
            />
        </div>

        <x-table :headers="$headers" :rows="$clients" with-pagination class="rounded-xl overflow-hidden">
            
            {{-- 4. SCOPE BARU: Menampilkan status akun di dalam tabel --}}
            @scope('cell_status', $clients)
                @if($clients->user?->is_approved)
                    <span class="badge badge-success text-white font-medium">Active (Approved)</span>
                @else
                    <span class="badge badge-warning text-white font-medium animate-pulse">Pending ACC</span>
                @endif
            @endscope

            @scope('cell_remain', $clients)
                <div class="flex items-center">
                @php
                    $days = intval(now()->diffInDays($clients->expirity, false));
                @endphp
                @if($days < 0)
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap" style="background-color: var(--color-error); color: white;">
                        Expired
                    </span>
                @elseif($days <= 5)
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap" style="background-color: var(--color-error); color: white;">
                        {{ $days }} days left
                    </span>
                @else
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap" style="background-color: var(--color-success); color: white;">
                        {{ $days }} days left
                    </span>
                @endif
                </div>
            @endscope

            @scope('cell_action', $clients)
                <div class="flex items-center justify-end gap-1">
                    
                    {{-- 5. TEMPAT TOMBOL ACC & REJECT: Ditampilkan HANYA jika user tersebut belum di-approve --}}
                    @if(!$clients->user?->is_approved && $clients->user_id)
                        <button wire:click="approveUser({{ $clients->user_id }})" class="bg-green-500 hover:bg-green-600 text-white px-2.5 py-1 rounded-lg text-xs font-semibold mr-1 shadow-sm transition">
                            ACC
                        </button>
                        <button wire:click="rejectUser({{ $clients->user_id }}, {{ $clients->id }})" class="bg-red-500 hover:bg-red-600 text-white px-2.5 py-1 rounded-lg text-xs font-semibold mr-2 shadow-sm transition">
                            Reject
                        </button>
                    @endif

                    <x-button
                        wire:click="$dispatch('enableEditClient', {clientId: {{$clients->id}} })"
                        icon="o-pencil"
                        class="btn-ghost btn-sm btn-circle border-none shadow-none"
                        tooltip="Edit"
                    />
                    <x-button
                        wire:click="deleteClient({{$clients->id}})"
                        icon="o-trash"
                        class="btn-ghost btn-sm btn-circle border-none shadow-none text-error"
                        tooltip="Delete"
                    />
                    <x-button
                        wire:click="loginAs({{$clients->id}})"
                        icon="o-user-circle"
                        class="btn-ghost btn-sm btn-circle border-none shadow-none text-info"
                        tooltip="Login as"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>