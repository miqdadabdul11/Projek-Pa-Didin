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
    public $remember = false;

    protected $rules = [
        'email'    => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $user = Auth::user();

            if (!$user->is_approved) {
                Auth::logout();
                $this->reset('password');
                $this->error('Akun Anda belum disetujui oleh Admin. Mohon tunggu.', position: 'toast-top toast-center', timeout: 5000);
                return;
            }

            $user->load('roles');
            $targetRoute = null;
            $roleNames   = $user->getRoleNames();

            if ($roleNames->contains('admin'))           $targetRoute = 'admin';
            elseif ($roleNames->contains('client'))      $targetRoute = 'client';
            elseif ($roleNames->contains('operator'))    $targetRoute = 'operator';
            elseif ($roleNames->contains('maintenance')) $targetRoute = 'maintenance';
            elseif ($roleNames->contains('viewer'))      $targetRoute = 'viewer';

            if ($targetRoute) {
                session()->regenerate();
                return redirect()->route($targetRoute);
            }

            $errorMsg = $roleNames->isNotEmpty()
                ? "Access denied. Your role: [{$roleNames->join(', ')}]"
                : "This account has no assigned role.";
            Auth::logout();
            $this->reset('password');
            $this->error($errorMsg, position: 'toast-top toast-center', timeout: 5000);

        } else {
            $this->reset('password');
            $this->error('Incorrect email or password.', position: 'toast-top toast-center');
        }
    }
};
?>

<div style="position:relative;min-height:100vh;width:100vw;display:flex;margin:0;padding:0;background-image:url('{{ asset('foto-gedung.jpg') }}');background-size:cover;background-position:center;">

    <div style="position:absolute;inset:0;background:rgba(10,20,50,0.62);z-index:0;"></div>

    <div class="hidden md:flex" style="position:relative;z-index:1;flex-direction:column;justify-content:center;padding:0 4rem;width:50%;">
        <p style="color:rgba(255,255,255,0.70);font-size:0.85rem;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;margin-bottom:12px;">UPI Smart Campus IoT</p>
        <h1 style="color:#fff;font-size:2.75rem;font-weight:800;line-height:1.15;margin-bottom:20px;">Smart Building<br>Monitoring Platform</h1>
        <p style="color:rgba(255,255,255,0.85);font-size:1.05rem;font-weight:600;margin-bottom:10px;">Universitas Pendidikan Indonesia</p>
        <p style="color:rgba(255,255,255,0.55);font-size:0.875rem;line-height:1.7;max-width:420px;">Real-time monitoring and management of campus buildings, rooms, IoT devices, sensors, MQTT communication, and campus infrastructure.</p>
    </div>

    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:center;width:100%;padding:3rem 2rem;" class="md:w-1/2">
        <div style="width:100%;max-width:420px;">
            <div style="border-radius:20px;padding:40px;background:rgba(235,240,255,0.25);backdrop-filter:blur(30px);-webkit-backdrop-filter:blur(30px);border:1.5px solid rgba(255,255,255,0.50);box-shadow:0 8px 32px rgba(0,0,0,0.20);">

                <div style="display:flex;justify-content:center;margin-bottom:18px;">
                    <div style="width:80px;height:80px;border-radius:50%;background:#fff;border:2.5px solid rgba(255,255,255,0.95);box-shadow:0 4px 16px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        <img src="{{ asset('UPI.png') }}" alt="Logo UPI" style="width:64px;height:64px;object-fit:contain;" onerror="this.style.display='none';this.nextElementSibling.style.display='block';" />
                        <span style="display:none;font-weight:700;font-size:1.2rem;color:#1e293b;">UPI</span>
                    </div>
                </div>

                <h2 style="text-align:center;font-size:1.5rem;font-weight:700;color:#111827;margin-bottom:28px;">Welcome Back</h2>

                <form wire:submit.prevent="login">
                    @csrf

                    <div style="position:relative;margin-bottom:12px;">
                        <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#6b7280" style="width:17px;height:17px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5H4.5a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <input type="email" wire:model="email" autocomplete="email" placeholder="Email@ceilgmail.com"
                            style="width:100%;box-sizing:border-box;border-radius:12px;padding:11px 14px 11px 38px;font-size:0.875rem;background:rgba(255,255,255,0.72);color:#111827;border:1px solid rgba(255,255,255,0.85);outline:none;" />
                    </div>

                    <div style="position:relative;margin-bottom:4px;">
                        <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#6b7280" style="width:17px;height:17px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.125a4.5 4.5 0 10-9 0V10.5m-1.5 0h12a1.5 1.5 0 011.5 1.5v7.5a1.5 1.5 0 01-1.5 1.5h-12A1.5 1.5 0 014.5 19.5V12a1.5 1.5 0 011.5-1.5z" />
                            </svg>
                        </div>
                        <input type="password" id="password-input" wire:model="password" autocomplete="current-password" placeholder="Password"
                            style="width:100%;box-sizing:border-box;border-radius:12px;padding:11px 44px 11px 38px;font-size:0.875rem;background:rgba(255,255,255,0.72);color:#111827;border:1px solid rgba(255,255,255,0.85);outline:none;" />
                        <button type="button" id="toggle-pw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;">
                            <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#6b7280" style="width:17px;height:17px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#6b7280" style="width:17px;height:17px;display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <div style="display:flex;align-items:center;margin-top:14px;">
                        <input type="checkbox" id="remember-me" wire:model="remember" style="width:15px;height:15px;accent-color:#3b82f6;cursor:pointer;" />
                        <label for="remember-me" style="margin-left:8px;font-size:0.875rem;color:#374151;cursor:pointer;user-select:none;">Remember Me</label>
                    </div>

                    <button type="submit" style="width:100%;padding:0.875rem;border:none;border-radius:0.75rem;background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;font-size:0.875rem;font-weight:600;cursor:pointer;">Loginkan</button>

                    <div style="display:flex;justify-content:space-between;margin-top:18px;">
                        <a href="{{ route('register') }}" style="font-size:0.85rem;color:rgba(55,65,81,0.80);text-decoration:none;" onmouseover="this.style.color='#111827'" onmouseout="this.style.color='rgba(55,65,81,0.80)'">Register Account</a>
                        <span style="font-size:0.85rem;color:rgba(55,65,81,0.50);">Forgot Password</span>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
document.addEventListener('livewire:navigated', initToggle);
document.addEventListener('DOMContentLoaded', initToggle);
function initToggle() {
    var pw      = document.getElementById('password-input');
    var btn     = document.getElementById('toggle-pw');
    var eyeOpen = document.getElementById('eye-open');
    var eyeOff  = document.getElementById('eye-closed');
    if (!pw || !btn) return;
    btn.addEventListener('click', function () {
        if (pw.type === 'password') {
            pw.type = 'text';
            eyeOpen.style.display = 'none';
            eyeOff.style.display  = 'block';
        } else {
            pw.type = 'password';
            eyeOpen.style.display = 'block';
            eyeOff.style.display  = 'none';
        }
    });
}
</script>