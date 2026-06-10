<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    <script>
        (function() {
            if (!localStorage.getItem('theme')) {
                localStorage.setItem('theme', 'pinky');
            }
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme'));
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    <x-main>
    @if(session('impersonated_by'))
    <div class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-6 py-2 text-white text-sm font-medium shadow-lg" style="background:#7c3aed;">
        <span>⚡ Login as: <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->roles->first()->name ?? 'no role' }})</span>
        <a href="{{ route('impersonate.stop') }}" class="px-4 py-1 rounded-full text-xs font-bold text-white border border-white/40 hover:bg-white/20 transition">← Back to Client</a>
    </div>
    @endif
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            <x-app-brand class="px-5 pt-4" />

            <x-menu>
                @if(Auth::check())
                    <x-menu-separator />

                    {{-- User info - expanded --}}
                    <div class="hidden-when-collapsed px-2 py-2 rounded-xl bg-base-200 mb-1">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col min-w-0">
                                <span class="text-sm font-semibold truncate text-base-content">{{ Str::limit(Auth::user()->name, 25, '...') }}</span>
                                <span class="text-xs text-base-content/50 truncate">{{ Auth::user()->email }}</span>
                            </div>
                            <div class="flex items-center gap-1 ml-2 shrink-0">
                                <label class="btn btn-circle btn-ghost btn-xs swap swap-rotate" title="Toggle theme">
                                    <input type="checkbox" id="theme-toggle-cb" />
                                    <svg class="swap-off w-4 h-4 stroke-current fill-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5">
                                        <circle cx="12" cy="12" r="4"/>
                                        <line x1="12" y1="2" x2="12" y2="5"/><line x1="2" y1="12" x2="5" y2="12"/>
                                        <line x1="19" y1="12" x2="22" y2="12"/><line x1="12" y1="19" x2="12" y2="22"/>
                                        <line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                                        <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/>
                                    </svg>
                                    <svg class="swap-on w-4 h-4 stroke-current fill-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                                    </svg>
                                </label>
                                <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="Logout" no-wire-navigate link="/" />
                            </div>
                        </div>
                    </div>

                    {{-- User info - collapsed (icon only) --}}
                    <div class="display-when-collapsed flex flex-col items-center py-2 mb-1 gap-1">
                        <label class="btn btn-circle btn-ghost btn-sm swap swap-rotate" title="Toggle theme">
                            <input type="checkbox" id="theme-toggle-cb-collapsed" />
                            <svg class="swap-off w-5 h-5 stroke-current fill-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5">
                                <circle cx="12" cy="12" r="4"/>
                                <line x1="12" y1="2" x2="12" y2="5"/><line x1="2" y1="12" x2="5" y2="12"/>
                                <line x1="19" y1="12" x2="22" y2="12"/><line x1="12" y1="19" x2="12" y2="22"/>
                                <line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                                <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/>
                            </svg>
                            <svg class="swap-on w-5 h-5 stroke-current fill-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </label>
                        <a href="/" class="btn btn-circle btn-ghost btn-sm" title="Logout">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                            </svg>
                        </a>
                    </div>

                    <x-menu-separator />

                    {{-- ===================== ADMIN ===================== --}}
                    @if(Auth::user()->hasRole('admin'))
                        <x-menu-item title="Dashboard" icon="o-sparkles"
                            link="{{ route('admin') }}" :active="request()->routeIs('admin')" wire:navigate />
                        <x-menu-item title="Client Management" icon="o-users"
                            link="{{ route('admin.client') }}" :active="request()->routeIs('admin.client')" wire:navigate />

                    {{-- ===================== CLIENT ===================== --}}
                    @elseif(Auth::user()->hasRole('client'))
                        <x-menu-item title="Access Management" icon="o-user"
                            link="{{ route('client') }}" :active="request()->routeIs('client')" wire:navigate />

                        <x-menu-sub title="Buildings" icon="o-building-office">
                            <x-menu-item title="All Buildings" icon="o-list-bullet"
                                link="{{ route('client.buildings') }}" :active="request()->routeIs('client.buildings')" wire:navigate />
                            <x-menu-item title="Import CSV / XLSX" icon="o-arrow-up-tray"
                                link="{{ route('client.buildings.import') }}" :active="request()->routeIs('client.buildings.import')" wire:navigate />
                        </x-menu-sub>

                        <x-menu-item title="Rooms" icon="o-squares-plus"
                            link="{{ route('client.classrooms') }}" :active="request()->routeIs('client.classrooms')" wire:navigate />

                        <x-menu-sub title="User Management" icon="o-user-group">
                            <x-menu-item title="Operators" icon="o-wrench"
                                link="{{ route('client.users.operator') }}" :active="request()->routeIs('client.users.operator')" wire:navigate />
                            <x-menu-item title="Maintenance" icon="o-cpu-chip"
                                link="{{ route('client.users.maintenance') }}" :active="request()->routeIs('client.users.maintenance')" wire:navigate />
                            <x-menu-item title="Viewers" icon="o-eye"
                                link="{{ route('client.users.viewer') }}" :active="request()->routeIs('client.users.viewer')" wire:navigate />
                        </x-menu-sub>

                    {{-- ===================== OPERATOR / MAINTENANCE / VIEWER ===================== --}}
                    @elseif(Auth::user()->hasRole('operator') || Auth::user()->hasRole('maintenance') || Auth::user()->hasRole('viewer'))
                        <x-menu-sub title="Monitoring" icon="o-chart-bar">
                            <x-menu-item title="Buildings" icon="o-building-office"
                                link="{{ route('monitoring.buildings') }}" :active="request()->routeIs('monitoring.buildings')" wire:navigate />
                            <x-menu-item title="Rooms" icon="o-squares-plus"
                                link="{{ route('monitoring.rooms') }}" :active="request()->routeIs('monitoring.rooms')" wire:navigate />
                            <x-menu-item title="Nodes" icon="o-cpu-chip"
                                link="{{ route('monitoring.nodes') }}" :active="request()->routeIs('monitoring.nodes')" wire:navigate />
                        </x-menu-sub>
                        <x-menu-separator />
                        @if(Auth::user()->hasRole('operator'))
                            <x-menu-item title="Dashboard" icon="o-wrench"
                                link="{{ route('operator') }}" :active="request()->routeIs('operator')" wire:navigate />
                            <x-menu-item title="Node Control" icon="o-cpu-chip"
                                link="{{ route('operator.nodes') }}" :active="request()->routeIs('operator.nodes')" wire:navigate />
                            <x-menu-item title="Incoming Requests" icon="o-inbox-arrow-down"
                                link="{{ route('operator.requests') }}" :active="request()->routeIs('operator.requests')" wire:navigate />
                        @elseif(Auth::user()->hasRole('maintenance'))
                            <x-menu-item title="Maintenance Log" icon="o-clipboard-document-check"
                                link="{{ route('maintenance') }}" :active="request()->routeIs('maintenance')" wire:navigate />
                            <x-menu-item title="Register Node" icon="o-plus-circle"
                                link="{{ route('maintenance.nodes') }}" :active="request()->routeIs('maintenance.nodes')" wire:navigate />
                            <x-menu-item title="MQTT Config" icon="o-signal"
                                link="{{ route('maintenance.mqtt') }}" :active="request()->routeIs('maintenance.mqtt')" wire:navigate />
                            <x-menu-item title="Export Node Report" icon="o-arrow-down-tray"
                                link="{{ route('maintenance.export') }}" :active="request()->routeIs('maintenance.export')" wire:navigate />
                        @elseif(Auth::user()->hasRole('viewer'))
                            <x-menu-item title="Monitoring Dashboard" icon="o-eye"
                                link="{{ route('viewer') }}" :active="request()->routeIs('viewer')" wire:navigate />
                            <x-menu-item title="Request to Operator" icon="o-paper-airplane"
                                link="{{ route('viewer.requests') }}" :active="request()->routeIs('viewer.requests')" wire:navigate />
                            <x-menu-item title="Export Node Report" icon="o-arrow-down-tray"
                                link="{{ route('viewer.export') }}" :active="request()->routeIs('viewer.export')" wire:navigate />
                        @endif

                    @endif
                @endif
            </x-menu>
        </x-slot:sidebar>

        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    <x-toast />

    <script>
        function applyTheme() {
            const theme = localStorage.getItem('theme') || 'pinky';
            document.documentElement.setAttribute('data-theme', theme);
            const cb = document.getElementById('theme-toggle-cb');
            const cbC = document.getElementById('theme-toggle-cb-collapsed');
            if (cb) cb.checked = theme === 'navy';
            if (cbC) cbC.checked = theme === 'navy';
        }

        function initToggle() {
            const cb = document.getElementById('theme-toggle-cb');
            const cbC = document.getElementById('theme-toggle-cb-collapsed');
            if (cb) {
                cb.checked = (localStorage.getItem('theme') || 'pinky') === 'navy';
                cb.addEventListener('change', () => {
                    const theme = cb.checked ? 'navy' : 'pinky';
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('theme', theme);
                    if (cbC) cbC.checked = cb.checked;
                });
            }
            if (cbC) {
                cbC.checked = (localStorage.getItem('theme') || 'pinky') === 'navy';
                cbC.addEventListener('change', () => {
                    const theme = cbC.checked ? 'navy' : 'pinky';
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('theme', theme);
                    if (cb) cb.checked = cbC.checked;
                });
            }
        }

        applyTheme();
        initToggle();
        function observeSidebar() {
            const sidebar = document.querySelector('aside');
            if (!sidebar) return;
            const observer = new MutationObserver(() => {
                const isCollapsed = sidebar.offsetWidth < 100;
                if (isCollapsed) {
                    sidebar.classList.add('mary-sidebar-collapsed');
                } else {
                    sidebar.classList.remove('mary-sidebar-collapsed');
                }
            });
            observer.observe(sidebar, { attributes: true, attributeFilter: ['class', 'style'] });
        }
        observeSidebar();
        document.addEventListener('livewire:navigated', () => {
            applyTheme();
            initToggle();
        });
    </script>
</body>
</html>
