<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;
use Livewire\Attributes\Layout;

new #[Layout('layouts::guest')] class extends Component
{
    use Toast;

    public $name;
    public $email;
    public $password;
    public $password_confirmation;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6|confirmed',
    ];

    public function register()
    {
        $this->validate();

        // Buat user baru dengan status pending
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'is_approved' => false,
        ]);

        // Auto-create client profile di tabel bems_clients
        DB::table('bems_clients')->insert([
            'code'       => 'CLT-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'user_id'    => $user->id,
            'name'       => $this->name,
            'expirity'   => now()->addYear()->toDateString(),
            'remain'     => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->success('Pendaftaran berhasil! Mohon tunggu persetujuan Admin.', redirectTo: '/');
    }
};
?>

<div
class="min-h-screen w-screen flex items-center justify-center"
style="
    background-image: url('{{ asset('images/login-bg.png') }}');
    background-size: cover;
    background-position: center;
    background-color: rgba(0,0,0,0.55);
    background-blend-mode: darken;
    margin: 0;
    padding: 0;
"
>
    <div class="w-full max-w-sm px-6">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">IoT Dashboard</h1>
            <p class="text-white/50 mt-2 text-sm">Faculty Building Management System</p>
        </div>

        <div class="rounded-2xl p-8" style="
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,0.22);
        ">
            <form wire:submit.prevent="register">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-white mb-1 block">Nama Lengkap</label>
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="Nama Lengkap Anda"
                            class="input w-full rounded-xl border-none focus:outline-none"
                            style="background:rgba(255,255,255,0.15); color:#fff;"
                        />
                        @error('name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-white mb-1 block">Email</label>
                        <input
                            type="email"
                            wire:model="email"
                            placeholder="you@example.com"
                            class="input w-full rounded-xl border-none focus:outline-none"
                            style="background:rgba(255,255,255,0.15); color:#fff;"
                        />
                        @error('email') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-white mb-1 block">Password</label>
                        <input
                            type="password"
                            wire:model="password"
                            placeholder="••••••••"
                            class="input w-full rounded-xl border-none focus:outline-none"
                            style="background:rgba(255,255,255,0.15); color:#fff;"
                        />
                        @error('password') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-white mb-1 block">Konfirmasi Password</label>
                        <input
                            type="password"
                            wire:model="password_confirmation"
                            placeholder="••••••••"
                            class="input w-full rounded-xl border-none focus:outline-none"
                            style="background:rgba(255,255,255,0.15); color:#fff;"
                        />
                    </div>
                </div>

                <div class="mt-5">
                    <button
                        type="submit"
                        class="btn btn-primary w-full border-none shadow-none rounded-xl text-white font-semibold"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading wire:target="register" class="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full mr-2"></span>
                        <span>Daftar Akun</span>
                    </button>
                </div>

                <div class="mt-4 text-center">
                    <p class="text-xs text-white/60">
                        Sudah punya akun? 
                        <a href="{{ route('login') }}" class="text-white font-semibold hover:underline ml-1">
                            Login di sini
                        </a>
                    </p>
                </div>
            </form>
        </div>

        <div class="text-center mt-6 text-xs text-white/30">
            &copy; {{ date('Y') }} Universitas Pendidikan Indonesia
        </div>

    </div>
</div>