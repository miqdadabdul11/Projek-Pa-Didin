<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Livewire\Attributes\Layout;

new #[Layout('layouts::guest')] class extends Component
{
    use Toast;

    public $email;
    public $password;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $user = Auth::user();
            
            if (!$user->is_approved) {
                Auth::logout();
                $this->reset('password');
                $this->error('Akun Anda belum disetujui oleh Admin. Mohon tunggu.', position: 'toast-top toast-center', timeout: 5000);
                return;
            }
            
            // Load roles eager to avoid N+1 queries
            $user->load('roles');
            
            $targetRoute = null;
            $roleNames = $user->getRoleNames();

            // 2. Tentukan halaman tujuan berdasarkan Role mereka
            if ($roleNames->contains('admin')) {
                $targetRoute = 'admin';
            } elseif ($roleNames->contains('client')) {
                $targetRoute = 'client';
            } elseif ($roleNames->contains('operator')) {
                $targetRoute = 'operator';
            } elseif ($roleNames->contains('maintenance')) {
                $targetRoute = 'maintenance';
            } elseif ($roleNames->contains('viewer')) {
                $targetRoute = 'viewer';
            }

            // 3. Jika rutenya terdaftar, jalankan redirect ke Dashboard masing-masing
            if ($targetRoute) {
                session()->regenerate();
                return redirect()->route($targetRoute);
            }

            // 4. Kasus jika password benar tapi akun tidak diberi role apapun oleh sistem
            $errorMsg = $roleNames->isNotEmpty() ? "Access denied. Your role: [{$roleNames->join(', ')}]" : "This account has no assigned role.";
            Auth::logout();
            $this->reset('password');
            $this->error($errorMsg, position: 'toast-top toast-center', timeout: 5000);

        } else {
            // 5. Jika kombinasi salah
            $this->reset('password');
            $this->error('Incorrect email or password.', position: 'toast-top toast-center');
        }
    }
};
?>

<div
class="min-h-screen w-screen flex items-center justify-center"
style="
    background-image: url('{{ asset('images-upi.jpg') }}');
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
            <form wire:submit.prevent="login" id="login-form">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-white mb-1 block">Email</label>
                        <input
                            type="email"
                            name="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            wire:model="email"
                            id="email-input"
                            class="input w-full rounded-xl border-none focus:outline-none text-black p-3"
                            style="background:rgba(255,255,255,0.15); color:#fff;"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-white mb-1 block">Password</label>
                        <div class="relative" id="pw-wrapper">
                            <input
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                wire:model="password"
                                id="password-input"
                                class="input w-full rounded-xl border-none focus:outline-none pr-12 text-black p-3"
                                style="background:rgba(255,255,255,0.15); color:#fff;"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button
                        type="submit"
                        class="btn btn-primary w-full border-none shadow-none rounded-xl text-white font-semibold flex items-center justify-center gap-2"
                        style="background-color: #3b82f6;"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading wire:target="login" class="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full"></span>
                        <span wire:loading.remove wire:target="login">Loginkan</span>
                    </button>
                </div>

                <div class="mt-5 text-center">
                    <p class="text-xs text-white/60 tracking-wide">
                        Belum punya akun? 
                        <a href="{{ route('register') }}" class="text-white font-semibold hover:underline transition ml-1 decoration-white/40">
                            Daftar Sekarang
                        </a>
                    </p>
                </div>
            </form>
        </div>

        <div class="text-center mt-6 text-xs text-white/30">
            &copy; {{ date('Y') }} Universitas Pendidikan Indonesia
        </div>

    </div>

    @script
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password-input');
            const pwWrapper = document.getElementById('pw-wrapper');

            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.setAttribute('style', 'position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:0; display:flex; align-items:center; justify-content:center; z-index:10;');

            const eyeOpenSvg = `<svg id="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px;color:#fff;opacity:0.7;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>`;

            const eyeClosedSvg = `<svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px;color:#fff;opacity:0.7;display:none;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>`;

            toggleBtn.innerHTML = eyeOpenSvg + eyeClosedSvg;

            toggleBtn.addEventListener('click', function () {
                const eyeOpen = document.getElementById('eye-open');
                const eyeClosed = document.getElementById('eye-closed');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'block';
                } else {
                    passwordInput.type = 'password';
                    eyeOpen.style.display = 'block';
                    eyeClosed.style.display = 'none';
                }
            });

            pwWrapper.appendChild(toggleBtn);
        });
    </script>
    @endscript
</div>