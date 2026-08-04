<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BHCIS') - Sta. Ana</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/layout.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @livewireStyles
    
    <style>
        :root {
            /* Base Palette */
            --bg-page: #ffffff;
            --bg-surface: #ffffff;
            --bg-surface-elevated: #ffffff;
            --bg-sidebar: #0d4a3c;
            --bg-header: #0a3d32;
            
            /* Text / Ink Colors */
            --ink: #0f172a; 
            --ink-muted: #475569; 
            --ink-subtle: #94a3b8;
            --border: rgba(15, 23, 42, 0.08); 

            /* Primary Colors */
            --primary: #0d4a3c;
            --primary-hover: #0a3d32;
            --teal-soft: rgba(13, 74, 60, 0.08);

            /* Accent Colors */
            --accent: #0d4a3c;
            --accent-hover: #0a3d32;
            --accent-soft: rgba(196, 92, 65, 0.12);
            --accent-blue: #0284c7;
            --accent-blue-soft: rgba(2, 132, 199, 0.12);
            --danger: #dc2626;
            --danger-soft: rgba(220, 38, 38, 0.12);

            /* Shadows */
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.06);
            --shadow-lg: 0 12px 32px rgba(15, 23, 42, 0.08);
        }

        /* === AUTOSCALING FOUNDATION === */
        html {
            font-size: clamp(13px, 1.1vw, 16px);
        }

        body {
            font-size: clamp(13px, 1.1vw, 16px);
        }

        .grain::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }

        /* === AUTOSCALING TYPOGRAPHY === */
        h1 { font-size: clamp(1.5rem, 5vw, 2.25rem); line-height: clamp(1.3, 1.5, 1.8); }
        h2 { font-size: clamp(1.25rem, 3.5vw, 1.875rem); line-height: clamp(1.3, 1.5, 1.8); }
        h3 { font-size: clamp(1.125rem, 2.5vw, 1.5rem); line-height: clamp(1.4, 1.6, 1.9); }
        h4 { font-size: clamp(1rem, 2vw, 1.25rem); }
        h5 { font-size: clamp(0.95rem, 1.8vw, 1.125rem); }
        
        .text-xs { font-size: clamp(0.7rem, 0.85vw, 0.875rem); }
        .text-sm { font-size: clamp(0.8rem, 0.95vw, 0.9375rem); }
        .text-base { font-size: clamp(0.9rem, 1.1vw, 1rem); }
        .text-lg { font-size: clamp(1rem, 1.2vw, 1.125rem); }
        .text-xl { font-size: clamp(1.1rem, 1.4vw, 1.25rem); }

        /* === AUTOSCALING SPACING === */
        .px-2 { padding-left: clamp(0.4rem, 1vw, 0.5rem); padding-right: clamp(0.4rem, 1vw, 0.5rem); }
        .px-3 { padding-left: clamp(0.6rem, 1.5vw, 0.75rem); padding-right: clamp(0.6rem, 1.5vw, 0.75rem); }
        .px-4 { padding-left: clamp(0.8rem, 2vw, 1rem); padding-right: clamp(0.8rem, 2vw, 1rem); }
        .px-5 { padding-left: clamp(1rem, 2.5vw, 1.25rem); padding-right: clamp(1rem, 2.5vw, 1.25rem); }
        .px-6 { padding-left: clamp(1.2rem, 3vw, 1.5rem); padding-right: clamp(1.2rem, 3vw, 1.5rem); }
        
        .py-1 { padding-top: clamp(0.2rem, 0.5vw, 0.25rem); padding-bottom: clamp(0.2rem, 0.5vw, 0.25rem); }
        .py-2 { padding-top: clamp(0.4rem, 1vw, 0.5rem); padding-bottom: clamp(0.4rem, 1vw, 0.5rem); }
        .py-3 { padding-top: clamp(0.6rem, 1.5vw, 0.75rem); padding-bottom: clamp(0.6rem, 1.5vw, 0.75rem); }
        .py-4 { padding-top: clamp(0.8rem, 2vw, 1rem); padding-bottom: clamp(0.8rem, 2vw, 1rem); }
        .py-5 { padding-top: clamp(1rem, 2.5vw, 1.25rem); padding-bottom: clamp(1rem, 2.5vw, 1.25rem); }
        
        .gap-2 { gap: clamp(0.4rem, 1vw, 0.5rem); }
        .gap-3 { gap: clamp(0.6rem, 1.5vw, 0.75rem); }
        .gap-4 { gap: clamp(0.8rem, 2vw, 1rem); }
        .gap-5 { gap: clamp(1rem, 2.5vw, 1.25rem); }

        .mb-2 { margin-bottom: clamp(0.4rem, 1vw, 0.5rem); }
        .mb-4 { margin-bottom: clamp(0.8rem, 2vw, 1rem); }
        .mb-6 { margin-bottom: clamp(1.2rem, 3vw, 1.5rem); }
        .mb-8 { margin-bottom: clamp(1.6rem, 4vw, 2rem); }

        /* === AUTOSCALING COMPONENTS === */
        .logo-mark { 
            width: clamp(36px, 8vw, 48px); 
            height: clamp(36px, 8vw, 48px); 
            border-radius: clamp(8px, 1.5vw, 12px); 
            overflow: hidden; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            background: var(--bg-surface); 
        }
        .logo-mark img { width: 100%; height: 100%; object-fit: cover; }

        /* === AUTOSCALING SIDEBAR === */
        aside {
            width: clamp(240px, 30vw, 300px) !important;
        }

        aside.lg\:w-0 {
            width: 0 !important;
        }
        
        .disabled {
            opacity: 0.5;
            filter: grayscale(100%);
            cursor: not-allowed;
        }

        /* === AUTOSCALING BUTTONS & FORMS === */
        button, a[role="button"] {
            padding-top: clamp(0.5rem, 1.2vw, 0.75rem);
            padding-bottom: clamp(0.5rem, 1.2vw, 0.75rem);
            padding-left: clamp(0.8rem, 2vw, 1.25rem);
            padding-right: clamp(0.8rem, 2vw, 1.25rem);
            border-radius: clamp(0.375rem, 1vw, 0.625rem);
            font-size: clamp(0.8rem, 0.95vw, 1rem);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            padding: clamp(0.5rem, 1vw, 0.75rem);
            border-radius: clamp(0.375rem, 0.8vw, 0.625rem);
            font-size: clamp(0.85rem, 1vw, 1rem);
            border: 1px solid var(--border);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 74, 60, 0.15);
        }

        /* === AUTOSCALING CARDS & MODALS === */
        .rounded-2xl {
            border-radius: clamp(0.75rem, 2vw, 1.5rem);
        }

        .rounded-xl {
            border-radius: clamp(0.5rem, 1.5vw, 0.75rem);
        }

        .rounded-lg {
            border-radius: clamp(0.375rem, 1vw, 0.5rem);
        }

        /* === AUTOSCALING TABLE === */
        table {
            font-size: clamp(0.8rem, 0.95vw, 0.95rem);
        }

        th {
            padding: clamp(0.6rem, 1.2vw, 0.75rem);
            font-size: clamp(0.8rem, 0.9vw, 0.9375rem);
        }

        td {
            padding: clamp(0.5rem, 1vw, 0.875rem);
            font-size: clamp(0.8rem, 0.95vw, 0.95rem);
        }

        /* === AUTOSCALING MODALS === */
        #pageModalPanel,
        #consultationCreateModalPanel,
        #printReferralConfirmPanel {
            max-width: min(95vw, 900px);
            border-radius: clamp(0.75rem, 2vw, 1.25rem);
            padding: clamp(1rem, 3vw, 2rem);
        }

        /* === AUTOSCALING KPI CARDS === */
        .kpi-card {
            min-height: clamp(3rem, 10vw, 5rem);
            padding: clamp(0.75rem, 1.5vw, 1.25rem);
            border-radius: clamp(0.625rem, 1.5vw, 0.875rem);
        }

        .kpi-card__icon {
            width: clamp(2rem, 4vw, 2.5rem);
            height: clamp(2rem, 4vw, 2.5rem);
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: clamp(0.4rem, 1vw, 0.625rem);
        }

        .kpi-card__icon svg {
            width: clamp(1rem, 2vw, 1.25rem);
            height: clamp(1rem, 2vw, 1.25rem);
        }

        .kpi-card__value {
            font-family: 'Poppins', system-ui, sans-serif;
            font-weight: 600;
            font-size: clamp(1rem, 2vw, 1.5rem);
            line-height: 1.2;
            color: var(--ink);
        }

        .dashboard-chart__plot {
            height: clamp(10rem, 40vw, 16rem);
        }

        /* === AUTOSCALING NAVIGATION === */
        .nav-link {
            padding: clamp(0.5rem, 1vw, 0.75rem) clamp(0.75rem, 1.5vw, 1rem);
            border-radius: clamp(0.375rem, 0.8vw, 0.625rem);
            font-size: clamp(0.8rem, 0.95vw, 0.95rem);
            gap: clamp(0.5rem, 1vw, 0.75rem);
        }

        .nav-link i {
            font-size: clamp(0.875rem, 1.2vw, 1.1rem);
        }

        /* === AUTOSCALING ANIMATIONS === */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(clamp(8px, 2vw, 16px)); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-in { animation: fadeSlideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.1s; }

        /* Normalize checkbox appearance & size across the app */
        input[type="checkbox"],
        input[type="checkbox"].checkbox,
        input[type="checkbox"].household-checkbox,
        input[type="checkbox"]#selectAllCheckbox,
        input[type="checkbox"].rounded {
            width: clamp(0.9rem, 2vw, 1.125rem) !important; 
            height: clamp(0.9rem, 2vw, 1.125rem) !important; 
            min-width: clamp(0.9rem, 2vw, 1.125rem) !important;
            min-height: clamp(0.9rem, 2vw, 1.125rem) !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            vertical-align: middle !important;
            -webkit-appearance: checkbox !important;
            appearance: checkbox !important;
            transform: scale(1) !important;
        }

        /* Reduce visual differences from border-radius utilities */
        input[type="checkbox"].rounded { border-radius: clamp(3px, 0.5vw, 5px) !important; }

        /* Ensure accent color consistent */
        input[type="checkbox"] { accent-color: var(--primary); }

        .app-sidebar,
        .app-header {
            color: #ffffff;
        }

        .app-sidebar .text-ink,
        .app-sidebar .text-ink-muted,
        .app-sidebar .text-primary,
        .app-sidebar .nav-link,
        .app-sidebar button {
            color: rgba(255, 255, 255, 0.92) !important;
        }

        .app-sidebar .border-border {
            border-color: rgba(255, 255, 255, 0.18) !important;
        }

        .app-sidebar .nav-link:hover,
        .app-sidebar button:hover {
            background: rgba(255, 255, 255, 0.14) !important;
            color: #ffffff !important;
        }

        /* === RESPONSIVE PADDING FOR MAIN CONTENT === */
        main {
            padding-left: clamp(0.5rem, 2vw, 1rem);
            padding-right: clamp(0.5rem, 2vw, 1rem);
            padding-top: clamp(0.75rem, 1.5vw, 1.5rem);
            padding-bottom: clamp(0.5rem, 1.5vw, 1rem);
        }

        .max-w-7xl, .max-w-5xl {
            margin-left: auto;
            margin-right: auto;
            width: 100%;
            padding-left: clamp(0.5rem, 1.5vw, 1rem);
            padding-right: clamp(0.5rem, 1.5vw, 1rem);
        }

        /* === BREADCRUMB AUTOSCALING === */
        nav[aria-label="Breadcrumb"] {
            font-size: clamp(0.75rem, 0.9vw, 0.9375rem);
            margin-left: clamp(0.5rem, 2vw, 1.25rem);
            margin-bottom: clamp(0.5rem, 1.5vw, 1rem);
            gap: clamp(0.4rem, 0.8vw, 0.625rem);
        }

        /* === NOTIFICATION TOAST AUTOSCALING === */
        #liveConsultationToast {
            width: clamp(300px, 90vw, 420px);
            border-radius: clamp(1rem, 2vw, 1.5rem);
            bottom: clamp(1rem, 3vw, 2rem);
            right: clamp(1rem, 3vw, 2rem);
        }

        #liveConsultationToast .p-5 {
            padding: clamp(1rem, 1.5vw, 1.25rem);
        }

        #liveConsultationToast .p-4 {
            padding: clamp(0.75rem, 1.2vw, 1rem);
        }

        @media (max-width: 640px) {
            #liveConsultationToast {
                width: clamp(280px, 95vw, 350px);
                border-radius: clamp(0.75rem, 1.5vw, 1rem);
            }
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                        sans: ['Poppins', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        page: 'var(--bg-page)',
                        surface: 'var(--bg-surface)',
                        'surface-elevated': 'var(--bg-surface-elevated)',
                        ink: 'var(--ink)',
                        'ink-muted': 'var(--ink-muted)',
                        'ink-subtle': 'var(--ink-subtle)',
                        border: 'var(--border)',
                        primary: 'var(--primary)',
                        'teal-soft': 'var(--teal-soft)',
                        accent: 'var(--accent)',
                        'accent-blue': 'var(--accent-blue)',
                        'accent-blue-soft': 'var(--accent-blue-soft)',
                        danger: 'var(--danger)',
                        'danger-soft': 'var(--danger-soft)',
                        sky: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                        },
                    },
                    boxShadow: {
                        sm: 'var(--shadow-sm)',
                        md: 'var(--shadow-md)',
                        lg: 'var(--shadow-lg)',
                    },
                    /* === AUTOSCALING UTILITIES === */
                    fontSize: {
                        'xs': ['clamp(0.7rem, 0.85vw, 0.875rem)', 'clamp(1rem, 1.2vw, 1.25rem)'],
                        'sm': ['clamp(0.8rem, 0.95vw, 0.9375rem)', 'clamp(1.1rem, 1.3vw, 1.375rem)'],
                        'base': ['clamp(0.9rem, 1.1vw, 1rem)', 'clamp(1.3rem, 1.5vw, 1.5rem)'],
                        'lg': ['clamp(1rem, 1.2vw, 1.125rem)', 'clamp(1.4rem, 1.6vw, 1.625rem)'],
                        'xl': ['clamp(1.1rem, 1.4vw, 1.25rem)', 'clamp(1.5rem, 1.8vw, 1.75rem)'],
                    },
                    spacing: {
                        'autoscale-xs': 'clamp(0.25rem, 0.5vw, 0.375rem)',
                        'autoscale-sm': 'clamp(0.5rem, 1vw, 0.75rem)',
                        'autoscale-md': 'clamp(0.75rem, 1.5vw, 1rem)',
                        'autoscale-lg': 'clamp(1rem, 2vw, 1.5rem)',
                        'autoscale-xl': 'clamp(1.5rem, 3vw, 2rem)',
                    },
                    borderRadius: {
                        'autoscale': 'clamp(0.375rem, 1vw, 0.625rem)',
                        'autoscale-lg': 'clamp(0.75rem, 2vw, 1.5rem)',
                    }
                },
            },
        };
    </script>
</head>

<body x-data="{ sidebarOpen: false, desktopSidebarOpen: localStorage.getItem('desktop-sidebar-open') !== '0', showVitalsModal: false }" 
      :class="{ 'overflow-hidden': sidebarOpen }" 
      class="min-h-screen overflow-x-hidden font-sans text-ink antialiased bg-page" 
      x-on:open-vitals-modal.window="showVitalsModal = true" 
      x-on:close-vitals-modal.window="showVitalsModal = false">
    
    <div class="grain fixed inset-0 z-0"></div>
    <div class="absolute inset-0 z-0 opacity-40 bg-[linear-gradient(135deg,var(--teal-soft)_0%,transparent_50%,rgba(196,92,65,0.06)_100%)]"></div>

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

        <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', desktopSidebarOpen ? 'lg:w-64 lg:border-r lg:shadow-md' : 'lg:w-0 lg:border-r-0 lg:shadow-none']" 
               class="app-sidebar transform fixed lg:sticky top-0 h-screen overflow-y-auto w-64 shrink-0 flex flex-col z-50 transition-all duration-300 ease-out border-r border-border shadow-md"
               style="background: var(--bg-sidebar);">
            
            <div class="flex items-center justify-between p-4 lg:p-5 border-b border-border">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <div class="logo-mark" style="background: transparent;">
                        <img src="{{ asset('img/logo.svg') }}" alt="Santa Ana logo">
                    </div>
                    <span class="font-display font-semibold text-lg text-ink">BHCIS System</span>
                    <span class="text-[10px] font-medium uppercase tracking-wider px-2 py-0.5 rounded bg-teal-soft text-primary">Sta. Ana</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg hover:bg-black/5 transition-colors text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 p-3 space-y-1 overflow-y-auto" 
                 x-data="{ 
                     patientCareOpen: false, 
                     managementOpen: false, 
                     adminOpen: false,
                     initDropdowns() {
                         const current = window.location.pathname;
                         this.patientCareOpen = ['household', 'patient', 'consultation', 'immunization'].some(r => current.includes(r));
                         this.managementOpen = ['medicine', 'report'].some(r => current.includes(r));
                         this.adminOpen = current.includes('user');
                     }
                 }" 
                 x-init="initDropdowns()">
                
                @php
                    /** @var \App\Models\User|null $authUser */
                    $authUser = auth()->user();
                    $swalError = "Swal.fire({title: 'Unauthorized', text: 'Please contact the administrator if you believe this is a mistake.', icon: 'error'}); return false;";
                @endphp

                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5">
                    <i class="fa-solid fa-house texhovert-base opacity-70" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>

                <div>
                    <button @click="patientCareOpen = !patientCareOpen" 
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 hover:opacity-100">
                        <i class="fa-solid fa-user-doctor text-base opacity-70" aria-hidden="true"></i>
                        <span class="flex-1 text-left">Services</span>
                        <i class="fa-solid fa-chevron-down text-sm transition-transform duration-200" :class="{ 'rotate-180': patientCareOpen }" aria-hidden="true"></i>
                    </button>
                    <div x-show="patientCareOpen" 
                         x-collapse
                         class="mt-1 ml-2 pl-3 border-l border-border space-y-0.5">
                        
                        <a href="{{ route('households.index') }}" 
                           class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ !$authUser->hasPermission('household') ? 'disabled' : '' }}" 
                           {!! !$authUser->hasPermission('household') ? 'onclick="'.$swalError.'"' : '' !!}>
                            <i class="fa-solid fa-house-chimney text-sm opacity-70" aria-hidden="true"></i>
                            <span>Household</span>
                        </a>

                        <a href="{{ url('/patients') }}" 
                           class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ !$authUser->hasPermission('patients') ? 'disabled' : '' }}" 
                           {!! !$authUser->hasPermission('patients') ? 'onclick="'.$swalError.'"' : '' !!}>
                            <i class="fa-solid fa-user-injured text-sm opacity-70" aria-hidden="true"></i>
                            <span>Patients</span>
                        </a>

                        <a href="{{ route('consultations.index') }}" 
                           class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ !$authUser->hasPermission('consultations') ? 'disabled' : '' }}" 
                           {!! !$authUser->hasPermission('consultations') ? 'onclick="'.$swalError.'"' : '' !!}>
                            <i class="fa-solid fa-stethoscope text-sm opacity-70" aria-hidden="true"></i>
                            <span>Check-ups</span>
                        </a>

                        <a href="{{ route('immunizations.index') }}" 
                           class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ !$authUser->hasPermission('immunizations') ? 'disabled' : '' }}" 
                           {!! !$authUser->hasPermission('immunizations') ? 'onclick="'.$swalError.'"' : '' !!}>
                            <i class="fa-solid fa-syringe text-sm opacity-70" aria-hidden="true"></i>
                            <span>Immunizations</span>
                        </a>

                        <a href="{{ route('referrals.index') }}" 
                           class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ !$authUser->hasPermission('consultations') ? 'disabled' : '' }}" 
                           {!! !$authUser->hasPermission('consultations') ? 'onclick="'.$swalError.'"' : '' !!}>
                            <i class="fa-solid fa-arrow-up-right-from-square text-sm opacity-70" aria-hidden="true"></i>
                            <span>Referrals</span>
                        </a>

                    </div>
                </div>

                @if ($authUser && ($authUser->hasPermission('medicines') || $authUser->hasPermission('reports')))
                    <div>
                        <button @click="managementOpen = !managementOpen" 
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 hover:opacity-100">
                            <i class="fa-solid fa-layer-group text-base opacity-70" aria-hidden="true"></i>
                            <span class="flex-1 text-left">Management</span>
                            <i class="fa-solid fa-chevron-down text-sm transition-transform duration-200" :class="{ 'rotate-180': managementOpen }" aria-hidden="true"></i>
                        </button>
                        <div x-show="managementOpen" 
                             x-collapse
                             class="mt-1 ml-2 pl-3 border-l border-border space-y-0.5">
                            
                            @if ($authUser->hasPermission('medicines'))
                                <a href="{{ route('medicines.index') }}" 
                                   class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5">
                                    <i class="fa-solid fa-pills text-sm opacity-70" aria-hidden="true"></i>
                                    <span>Medicines Lists</span>
                                </a>
                            @endif

                            @if ($authUser->hasPermission('reports'))
                                <a href="{{ route('reports.index') }}" 
                                   class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5">
                                    <i class="fa-solid fa-file-lines text-sm opacity-70" aria-hidden="true"></i>
                                    <span>Reports</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($authUser && $authUser->hasPermission('users'))
                    <div>
                        <button @click="adminOpen = !adminOpen" 
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 hover:opacity-100">
                            <i class="fa-solid fa-user-gear text-base opacity-70" aria-hidden="true"></i>
                            <span class="flex-1 text-left">Administration</span>
                            <i class="fa-solid fa-chevron-down text-sm transition-transform duration-200" :class="{ 'rotate-180': adminOpen }" aria-hidden="true"></i>
                        </button>
                        <div x-show="adminOpen" 
                             x-collapse
                             class="mt-1 ml-2 pl-3 border-l border-border space-y-0.5">
                            <a href="{{ route('users.index') }}" 
                               class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5">
                                <i class="fa-solid fa-users text-sm opacity-70" aria-hidden="true"></i>
                                <span>User Management</span>
                            </a>
                            <a href="{{ route('roles.index') }}" 
                               class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5">
                                <i class="fa-solid fa-user-shield text-sm opacity-70" aria-hidden="true"></i>
                                <span>Role Manager</span>
                            </a>
                            <a href="{{ route('zones.index') }}" 
                           class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ !$authUser->hasPermission('zones') ? 'disabled' : '' }}" 
                           {!! !$authUser->hasPermission('zones') ? 'onclick="'.$swalError.'"' : '' !!}>
                            <i class="fa-solid fa-map-marker-alt text-sm opacity-70" aria-hidden="true"></i>
                            <span>Manage Purok</span>
                        </a>
                        </div>
                    </div>
                @endif

                <a href="{{ route('settings.index') }}" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5">
                    <i class="fa-solid fa-gear text-base opacity-70" aria-hidden="true"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0" x-data="{ headerSticky: false }" @scroll.window="headerSticky = window.scrollY > 275">
            
            <header :class="{ 'sticky top-0': headerSticky }" class="app-header z-40 shrink-0 flex justify-between items-center px-4 lg:px-6 py-1 border-b border-border transition-all duration-200"
                    style="background: var(--bg-header);">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-white/10 transition-colors text-white/90">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <button @click="desktopSidebarOpen = !desktopSidebarOpen; localStorage.setItem('desktop-sidebar-open', desktopSidebarOpen ? '1' : '0')"
                        class="hidden lg:inline-flex p-2 rounded-lg hover:bg-white/10 transition-colors text-white/90"
                        :title="desktopSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'">
                    <i class="fa-solid text-sm" :class="desktopSidebarOpen ? 'fa-angles-left' : 'fa-angles-right'" aria-hidden="true"></i>
                </button>

                <div class="ml-auto flex items-center gap-4">
                    @if ($authUser)
                        @php
                            $roleName = $authUser->role?->role_name ?? 'User';
                            $username = (string) $authUser->username;
                            $initials = mb_strtoupper(mb_substr($username, 0, 1));
                            $notifications = auth()->user()->notifications()->latest()->take(5)->get();
                            $unreadCount = auth()->user()->unreadNotifications->count();
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

            <main class="flex-1 px-2 lg:px-2 pt-3 pb-2 lg:pt-3 lg:pb-2 overflow-auto">
                @php
    $wideRoutes = ['dashboard', 'consultations.index', 'immunizations.index', 'patients.index', 'households.index', 'referrals.index', 'medicines.index', 'reports.index', 'zones.index', 'users.index', 'notifications.index'];
@endphp
<div class="{{ request()->routeIs($wideRoutes) ? 'max-w-7xl' : 'max-w-5xl' }} mx-auto">
                    
                    @php
                        $breadcrumbs = \App\Helpers\BreadcrumbHelper::getBreadcrumbs();
                    @endphp
                    @if(count($breadcrumbs) > 1)
                        <nav class="flex items-center gap-2 mb-2 py-1 animate-in opacity-0 delay-1 ml-5" aria-label="Breadcrumb">
                            @foreach($breadcrumbs as $index => $crumb)
                                @if($index > 0)
                                    <svg class="w-4 h-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6"></path>
                                    </svg>
                                @endif
                                @if($crumb['url'])
                                    <a href="{{ $crumb['url'] }}" class="font-medium transition-colors duration-200 hover:opacity-75 text-primary">{{ $crumb['name'] }}</a>
                                @else
                                    <span class="font-semibold text-ink">{{ $crumb['name'] }}</span>
                                @endif
                            @endforeach
                        </nav>
                    @endif
                    
                    <div class="rounded-2xl lg:p-8 animate-in opacity-0 delay-2 bg-surface-elevated shadow-sm">
                        @yield('content')
                    </div>

                </div>
            </main>
            
            <footer class="shrink-0 text-center py-3 text-xs border-t border-[var(--border)]" style="background: var(--bg-surface); color: var(--ink-subtle);">
                &copy; {{ date('Y') }} Barangay Sta. Ana Health Center. All rights reserved.
            </footer>
        </div>
    </div>

    <div id="liveConsultationToast" class="fixed bottom-5 right-5 z-[60] hidden max-w-[380px] rounded-3xl border border-slate-200 bg-white shadow-[0_24px_80px_rgba(14,30,37,0.15)] ring-1 ring-slate-900/5 overflow-hidden" aria-live="assertive" aria-atomic="true">
        <div class="p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 text-lg">!</span>
                <div class="min-w-0">
                    <p id="liveToastTitle" class="text-sm font-semibold text-slate-900">New Consultation Request</p>
                    <p id="liveToastSubtitle" class="text-xs text-slate-500 mt-1">Santa Ana Health Center • BHW</p>
                </div>
            </div>

            <div class="mt-4 rounded-3xl bg-slate-50 p-4 text-slate-700">
                <p id="liveToastPatient" class="text-sm font-semibold"></p>
                <p id="liveToastDetails" class="text-xs text-slate-500 mt-1"></p>
                <p id="liveToastReason" class="mt-3 text-sm text-slate-700"></p>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button id="liveToastDecline" type="button" class="w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto">Cancel</button>
                <button id="liveToastAccept" type="button" class="w-full rounded-2xl bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 sm:w-auto">Accept & Open Case</button>
            </div>
        </div>
    </div>

    <div id="pageModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePageDrawer()"></div>
        <div id="pageModalPanel" class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl transform scale-95 opacity-0 transition-all duration-300 ease-out">
            @stack('modal-content')
        </div>
    </div>

    <div id="consultationCreateModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-modal="true" role="dialog" aria-labelledby="consultationCreateModalTitle">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeConsultationCreateModal()"></div>
        <div id="consultationCreateModalPanel" class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 ease-out" style="background: var(--bg-surface-elevated);">
            <div id="consultationCreateModalContent"></div>
        </div>
    </div>

    <div id="printReferralConfirmModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4" aria-modal="true" role="dialog" aria-labelledby="printReferralConfirmTitle">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePrintReferralConfirmModal()"></div>
        <div id="printReferralConfirmPanel" class="relative w-full max-w-md rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 ease-out p-6" style="background: var(--bg-surface-elevated);">
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

    <style>
        .nav-link:hover { background: var(--teal-soft); color: var(--primary) !important; }
        .nav-submenu:hover { background: var(--teal-soft); color: var(--primary) !important; }
        a[href="{{ request()->url() }}"].nav-link,
        .nav-link.router-link-active { background: var(--teal-soft); color: var(--primary) !important; }
        a[href="{{ request()->url() }}"].nav-submenu,
        .nav-submenu.router-link-active { background: var(--teal-soft); color: var(--primary) !important; }

        .app-sidebar .nav-link:hover,
        .app-sidebar .nav-submenu:hover {
            background: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
        }

        .app-sidebar a[href="{{ request()->url() }}"].nav-link,
        .app-sidebar .nav-link.router-link-active,
        .app-sidebar a[href="{{ request()->url() }}"].nav-submenu,
        .app-sidebar .nav-submenu.router-link-active {
            background: rgba(255, 255, 255, 0.24) !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        #liveConsultationToast {
            transform: translateX(16px);
            opacity: 0;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        #liveConsultationToast.active {
            display: block;
            transform: translateX(0);
            opacity: 1;
        }
    </style>
    <script>
        window.BHCIS = {!! json_encode([
            'routes' => [
                'consultationsCreate' => route('consultations.create', ['patient' => '__PID__']),
                'sessionStatus' => route('session.status'),
                'liveRequests' => route('consultations.live-requests'),
            ],
            'openConsultationFor' => session('open_consultation_for') ? (int) session('open_consultation_for') : null,
            'printReferralId' => session('print_referral_id') ? (int) session('print_referral_id') : null,
            'canPollLiveRequests' => auth()->check() && auth()->user()->hasPermission('consultations'),
        ], JSON_UNESCAPED_SLASHES) !!};
    </script>

    @livewireScripts
    <script src="/vendor/livewire-charts/app.js"></script>
    @stack('page-modals')
    @stack('scripts')
</body>
</html>