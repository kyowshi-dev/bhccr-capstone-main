<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BHCIS') - Sta. Ana</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/layout.js', 'resources/js/charts.js'])
    @livewireStyles

</head>

<body x-data="{ sidebarOpen: false, showVitalsModal: false }" 
      :class="{ 'overflow-hidden': sidebarOpen }" 
      class="min-h-screen overflow-x-hidden font-sans text-ink antialiased bg-page" 
      x-on:open-vitals-modal.window="showVitalsModal = true" 
      x-on:close-vitals-modal.window="showVitalsModal = false">
    
    <div class="relative z-10 flex min-h-screen">
        
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 z-40 bg-black/40 lg:hidden" 
             style="display: none;">
        </div>

        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" 
               class="app-sidebar transform fixed lg:sticky top-0 h-[calc(100vh/var(--app-zoom,1))] overflow-y-auto w-64 shrink-0 flex flex-col z-50 transition-all duration-300 ease-out border-r border-border shadow-md">
            
            <div class="flex items-center justify-between p-4 lg:p-5 border-b border-border">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <div class="logo-mark" style="background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.25);">
                        <img src="{{ asset('img/logo.svg') }}" alt="Santa Ana logo">
                    </div>
                    <span class="font-display font-semibold text-lg text-white">BHCIS System</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg hover:bg-white/10 transition-colors text-white/90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-5 pt-3 pb-1">
                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] px-2.5 py-1 rounded-full border border-white/15 text-white/80 bg-white/5">
                    <span class="h-1.5 w-1.5 rounded-full" style="background: var(--primary);"></span>
                    Sta. Ana Health Center
                </span>
            </div>

            <nav class="flex-1 p-3 pt-2 space-y-1 overflow-y-auto" aria-label="Main navigation">

                @php
                    /** @var \App\Models\User|null $authUser */
                    $authUser = auth()->user();
                    $can = fn (string $perm) => $authUser !== null && $authUser->hasPermission($perm);
                @endphp

                <a href="{{ route('dashboard') }}" aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}" aria-label="Dashboard" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5">
                    <i class="fa-solid fa-house text-base opacity-70" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>

                @if ($can('patients') || $can('household') || $can('zones'))
                    <p class="mt-3 pt-3 border-t border-white/10 px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/50">Community</p>

                    @if ($can('patients'))
                        <x-layouts.nav-link url="{{ route('patients.index') }}" label="Patients" icon="fa-solid fa-user-injured"
                                            :active="request()->routeIs('patients*')" />
                    @endif

                    @if ($can('household'))
                        <x-layouts.nav-link url="{{ route('households.index') }}" label="Households" icon="fa-solid fa-house-chimney"
                                            :active="request()->routeIs('households*')" />
                    @endif

                    @if ($can('zones'))
                        <x-layouts.nav-link url="{{ route('zones.index') }}" label="Zones" icon="fa-solid fa-map-location-dot"
                                            :active="request()->routeIs('zones*')" />
                    @endif
                @endif

                @if ($can('consultations') || $can('immunizations') || $can('maternal'))
                    <p class="mt-3 pt-3 border-t border-white/10 px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/50">Health Care Services</p>

                    @if ($can('consultations'))
                        <x-layouts.nav-link url="{{ route('consultations.index') }}" label="Consultations" icon="fa-solid fa-stethoscope"
                                            :active="request()->routeIs('consultations*')" />
                    @endif

                    @if ($can('immunizations'))
                        <x-layouts.nav-link url="{{ route('immunizations.index') }}" label="Vaccinations" icon="fa-solid fa-syringe"
                                            :active="request()->routeIs('immunizations*')" />
                    @endif

                    @if ($can('maternal'))
                        <x-layouts.nav-link url="{{ route('maternal.prenatal.index') }}" label="Maternal Care" icon="fa-solid fa-person-pregnant"
                                            :active="request()->routeIs('maternal*')" />
                    @endif

                    @if ($can('consultations'))
                        <x-layouts.nav-link url="{{ route('referrals.index') }}" label="Referrals" icon="fa-solid fa-arrow-up-right-from-square"
                                            :active="request()->routeIs('referrals*')" />
                    @endif
                @endif

                @if ($can('medicines') || $can('reports'))
                    <p class="mt-3 pt-3 border-t border-white/10 px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/50">Reports & Inventory</p>

                    @if ($can('medicines'))
                        <x-layouts.nav-link url="{{ route('medicines.index') }}" label="Medicines" icon="fa-solid fa-pills"
                                            :active="request()->routeIs('medicines*')" />
                    @endif

                    @if ($can('reports'))
                        <x-layouts.nav-link url="{{ route('reports.index') }}" label="Reports" icon="fa-solid fa-file-lines"
                                            :active="request()->routeIs('reports*')" />
                    @endif
                @endif

                <p class="mt-3 pt-3 border-t border-white/10 px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/50">
                    Administration <span class="normal-case tracking-normal font-medium text-[9px] text-white/40">(Admin/Midwife only)</span>
                </p>

                @if ($can('users'))
                    <x-layouts.nav-link url="{{ route('users.index') }}" label="User Management" icon="fa-solid fa-user-gear"
                                        :active="request()->routeIs('users*')" />
                    <x-layouts.nav-link url="{{ route('roles.index') }}" label="Roles" icon="fa-solid fa-user-shield"
                                        :active="request()->routeIs('roles*')" />
                @endif

                <x-layouts.nav-link url="{{ route('settings.index') }}" label="Settings" icon="fa-solid fa-gear"
                                    :active="request()->routeIs('settings*')" />
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0" x-data="{ headerSticky: false }" @scroll.window="headerSticky = window.scrollY > 275">
            
            <header :class="{ 'sticky top-0': headerSticky }" class="app-header z-40 shrink-0 flex justify-between items-center px-4 lg:px-6 py-1 border-b border-white/10 shadow-sm"
                    style="background: linear-gradient(180deg, #0b4438 0%, #0a3d32 100%);">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-white/10 transition-colors text-white/90">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="ml-auto flex items-center gap-4">
                    @if ($authUser)
                        @php
                            $roleName = $authUser->role?->role_name ?? 'User';
                            $username = (string) $authUser->username;
                            $initials = mb_strtoupper(mb_substr($username, 0, 1));
                            $notifications = $authUser->notifications()->latest()->take(5)->get();
                            $unreadCount = $authUser->unreadNotifications->count();
                        @endphp
                        
                        <!-- Notifications Dropdown -->
                        <div x-data="{ notificationsOpen: false }" class="relative">
                            <button type="button"
                                    @click="notificationsOpen = !notificationsOpen"
                                    @click.away="notificationsOpen = false"
                                    class="relative p-2 rounded-lg hover:bg-white/10 transition-colors text-white/90 hover:text-white">
                                <i class="fa-solid fa-bell text-lg" aria-hidden="true"></i>
                                @if ($unreadCount > 0)
                                    <span class="absolute top-1 right-1 inline-flex items-center justify-center h-5 w-5 text-xs font-bold rounded-full bg-red-500 text-white">
                                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                    </span>
                                @endif
                            </button>

                            <div x-show="notificationsOpen"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform translate-y-1"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform translate-y-0"
                                 x-transition:leave-end="opacity-0 transform translate-y-1"
                                 class="absolute right-0 mt-3 w-80 rounded-xl border border-border shadow-md bg-surface-elevated z-50"
                                 style="display: none;">
                                
                                <div class="px-4 py-3 border-b border-border">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-semibold text-ink">Notifications</h3>
                                        @if ($unreadCount > 0)
                                            <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium text-primary hover:opacity-70 transition-opacity">
                                                    Mark all as read
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="max-h-96 overflow-y-auto">
                                    @forelse ($notifications as $notification)
                                        <div class="px-4 py-3 border-b border-border hover:bg-black/3 transition-colors {{ is_null($notification->read_at) ? 'bg-teal-soft' : '' }}">
                                            <div class="flex gap-3">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-ink">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                                    <p class="text-xs text-ink-muted mt-1">{{ $notification->data['message'] ?? '' }}</p>
                                                    <p class="text-xs text-ink-subtle mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                                                </div>
                                                @if (is_null($notification->read_at))
                                                    <form action="{{ route('notifications.mark-read', $notification->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="p-1 rounded hover:bg-black/10 transition-colors" title="Mark as read">
                                                            <i class="fa-solid fa-check text-xs text-primary" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-4 py-8 text-center">
                                            <i class="fa-solid fa-bell-slash text-2xl text-ink-subtle opacity-50 mb-2" aria-hidden="true"></i>
                                            <p class="text-sm text-ink-muted">No notifications yet</p>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="px-4 py-3 border-t border-border">
                                    <a href="{{ route('notifications.index') }}" class="block text-center text-sm font-medium text-primary hover:opacity-75 transition-opacity">
                                        View all notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div x-data="{ profileOpen: false }" class="relative">
                            <button type="button"
                                    @click="profileOpen = !profileOpen"
                                    @click.away="profileOpen = false"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 hover:shadow-sm transition-all duration-200  border border-white/20 hover:bg-white/15 text-white">
                                <span class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold bg-white/20 text-white">
                                    {{ $initials }}
                                </span>
                                <span class="hidden sm:block text-left leading-tight">
                                    <span class="block text-sm font-semibold text-white">
                                    {{ ucwords($username) }}
                                    </span>
                                    <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-white/20 text-white">
                                        {{ $roleName }}
                                    </span>
                                </span>
                                <svg class="w-4 h-4 hidden sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6"></path>
                                </svg>
                            </button>

                            <div x-show="profileOpen"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform translate-y-1"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform translate-y-0"
                                 x-transition:leave-end="opacity-0 transform translate-y-1"
                                 class="absolute right-0 mt-3 w-52 rounded-xl border border-border shadow-md bg-surface-elevated z-50"
                                 style="display: none;">
                                
                                <div class="px-4 py-3 text-xs border-b border-border text-ink-muted">
                                    <div class="font-semibold text-ink">{{ $username }}</div>
                                    <div>{{ $roleName }}</div>
                                </div>

                                <div class="p-2 space-y-1">
                                    <a href="{{ route('profile.show') }}" class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-black/5 text-ink">
                                        My Profile
                                    </a>
                                    @if($authUser->hasPermission('users'))
                                        <a href="{{ route('profile.settings') }}" class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-black/5 text-ink">
                                            Session Settings
                                        </a>
                                    @endif
                                </div>

                                <div class="p-3 border-t border-border">
                                    <form action="{{ route('logout') }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 hover:bg-black/5 active:scale-[0.98] border border-border text-ink py-1.5 bg-transparent">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </header>

            <main class="flex-1 px-[clamp(0.5rem,2vw,1.5rem)] pt-3 pb-2 lg:pt-4 lg:pb-3 overflow-auto">
                @php
                    $wideRoutes = ['dashboard', 'consultations.index', 'immunizations.index', 'patients.index', 'households.index', 'referrals.index', 'medicines.index', 'reports.index', 'zones.index', 'users.index', 'notifications.index'];
                @endphp

                <div class="{{ request()->routeIs($wideRoutes) ? 'max-w-[min(96vw,120rem)]' : 'max-w-[min(88vw,76rem)]' }} mx-auto">

                    @php
                        $breadcrumbs = \App\Helpers\BreadcrumbHelper::getBreadcrumbs();
                    @endphp
                    @if (count($breadcrumbs) > 1)
                        <nav class="flex items-center gap-2 mb-3 py-1 animate-in opacity-0 delay-1 lg:px-2" aria-label="Breadcrumb trail">
                            @foreach ($breadcrumbs as $index => $crumb)
                                @if ($index > 0)
                                    <svg class="w-4 h-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6"></path>
                                    </svg>
                                @endif
                                @if ($crumb['url'])
                                    <a href="{{ $crumb['url'] }}" class="font-medium transition-colors duration-200 hover:opacity-75 text-primary">{{ $crumb['name'] }}</a>
                                @else
                                    <span class="font-semibold text-ink">{{ $crumb['name'] }}</span>
                                @endif
                            @endforeach
                        </nav>
                    @endif

                    <div class="lg:px-2 animate-in opacity-0 delay-2">
                        @yield('content')
                    </div>

                </div>
            </main>
            
            <footer class="shrink-0 text-center py-3 text-xs border-t border-border" style="background: var(--bg-surface); color: var(--ink-subtle);">
                &copy; {{ date('Y') }} Barangay Sta. Ana Health Center. All rights reserved.
            </footer>
        </div>
    </div>

    <div id="liveConsultationToast" class="fixed bottom-5 right-5 z-[60] hidden max-w-[380px] rounded-3xl border border-border bg-surface-elevated shadow-lg overflow-hidden" aria-live="assertive" aria-atomic="true">
        <div class="p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal-soft text-primary">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p id="liveToastTitle" class="text-sm font-semibold text-ink">New Consultation Request</p>
                    <p id="liveToastSubtitle" class="text-xs text-ink-muted mt-1">Santa Ana Health Center • BHW</p>
                </div>
            </div>

            <div class="mt-4 rounded-3xl bg-teal-soft/50 p-4 text-ink">
                <p id="liveToastPatient" class="text-sm font-semibold"></p>
                <p id="liveToastDetails" class="text-xs text-ink-muted mt-1"></p>
                <p id="liveToastReason" class="mt-3 text-sm text-ink"></p>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button id="liveToastDecline" type="button" class="w-full rounded-2xl border border-border px-4 py-2 text-sm font-semibold text-ink-muted transition hover:bg-black/5 sm:w-auto">Cancel</button>
                <button id="liveToastAccept" type="button" class="w-full rounded-2xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover sm:w-auto">Accept & Open Case</button>
            </div>
        </div>
    </div>

    <div id="consultationCreateModal" x-show="$store.modals.consultation" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" aria-labelledby="consultationCreateModalTitle" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeConsultationCreateModal()"></div>
        <div id="consultationCreateModalPanel" x-show="$store.modals.consultation" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl focus:outline-none" style="background: var(--bg-surface-elevated);" tabindex="-1">
            <div id="consultationCreateModalContent"></div>
        </div>
    </div>

    <div id="printReferralConfirmModal" x-show="$store.modals.printReferral" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" aria-labelledby="printReferralConfirmTitle" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePrintReferralConfirmModal()"></div>
        <div id="printReferralConfirmPanel" x-show="$store.modals.printReferral" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-md rounded-2xl shadow-2xl p-6 focus:outline-none" style="background: var(--bg-surface-elevated);" tabindex="-1">
            <div class="flex items-start gap-3 mb-4">
                <div class="shrink-0 w-11 h-11 rounded-full flex items-center justify-center" style="background: var(--teal-soft); color: var(--primary);">
                    <i class="fa-solid fa-print text-lg" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 id="printReferralConfirmTitle" class="font-display font-semibold text-lg" style="color: var(--ink);">Referral saved</h2>
                    <p class="text-sm mt-1" style="color: var(--ink-muted);">The outward referral has been recorded. Print the referral slip for the patient before they leave.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closePrintReferralConfirmModal()" class="px-4 py-2.5 rounded-xl border font-medium text-sm transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">Close</button>
                <a id="printReferralConfirmLink" href="#" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white font-semibold text-sm transition hover:opacity-95" style="background: var(--primary);">
                    <i class="fa-solid fa-print" aria-hidden="true"></i> Print referral
                </a>
            </div>
        </div>
    </div>

    <script>
        window.BHCIS = {!! json_encode([
            'routes' => [
                'consultationsCreate' => route('consultations.create', ['patient' => '__PID__']),
                'sessionStatus' => route('session.status'),
                'sessionHeartbeat' => route('session.heartbeat'),
                'login' => route('login'),
                'liveRequests' => route('consultations.live-requests'),
            ],
            'sessionLifetimeMinutes' => (int) config('session.lifetime'),
            'openConsultationFor' => session('open_consultation_for') ? (int) session('open_consultation_for') : null,
            'printReferralId' => session('print_referral_id') ? (int) session('print_referral_id') : null,
            'canPollLiveRequests' => auth()->check() && auth()->user()->hasPermission('consultations'),
        ], JSON_UNESCAPED_SLASHES) !!};
    </script>

    @livewireScripts
    @stack('page-modals')
    @stack('scripts')
</body>
</html>