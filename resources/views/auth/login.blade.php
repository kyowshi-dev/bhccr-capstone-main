<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --font-display: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
            --font-body: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
            --bg-page: #f0f4f8;
            --bg-surface: #fdfcfa;
            --bg-card: #ffffff;
            --ink: #1a1f1c;
            --ink-muted: #5c6560;
            --border: rgba(26, 31, 28, 0.12);
            --primary: #0d4a3c;
            --accent: #c45c41;
            --accent-strong: #b13f2f;
            --accent-hover: #a84d36;
            --teal-soft: rgba(13, 74, 60, 0.08);
            --shadow-md: 0 4px 12px rgba(26, 31, 28, 0.08);
            --shadow-lg: 0 12px 32px rgba(26, 31, 28, 0.12);
        }

        /* Responsive / autoscaling adjustments */
        html { font-size: clamp(14px, 1.2vw, 18px); }

        .responsive-card {
            width: min(92vw, 640px);
            margin-left: auto;
            margin-right: auto;
        }

        .card-compact { padding: clamp(1rem, 2.5vw, 1.75rem); border-radius: clamp(0.75rem, 1.5vw, 1rem); }

        .logo-mark {
            width: clamp(48px, 12vw, 80px);
            height: clamp(48px, 12vw, 80px);
            border-radius: clamp(10px, 2vw, 14px);
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-surface);
        }

        .logo-mark img { width: 100%; height: 100%; object-fit: cover; transform: scale(1.08); }

        .brand-title { font-size: clamp(1rem, 2.2vw, 1.25rem); }

        input[type="text"], input[type="password"] {
            border: 1px solid var(--border);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(13, 74, 60, 0.2);
        }

        .accent-button {
            background: var(--accent-strong);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(196, 92, 65, 0.35);
            border: 1px solid transparent;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .accent-button:hover {
            background: var(--accent-hover);
        }

        .accent-button:focus-visible {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(13, 74, 60, 0.35);
        }

        .grain::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(circle, rgba(13, 74, 60, 0.06) 1.5px, transparent 1.5px),
                url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            background-size: 32px 32px, auto;
            pointer-events: none;
            z-index: 0;
        }
        /* Compact form adjustments */
        .card-compact { padding: 1.25rem; border-radius: 1rem; }
        .logo-mark { width:56px; height:56px; border-radius:14px; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; background: var(--bg-surface); zoom: 200%;}
        .logo-mark img { width: 100%; height: 100%; object-fit:cover; }
        .muted-xs { font-size:0.78rem; color:var(--ink-muted); }
        @media (min-width: 1024px) { .card-compact { padding:1.75rem 2rem; } }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: fadeSlideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center font-sans antialiased overflow-hidden" style="background: url('{{ asset('img/bg.svg') }}') center/cover no-repeat, var(--bg-page); font-family: var(--font-body);">
    <div class="grain fixed inset-0 z-0"></div>
    <div class="absolute inset-0 z-0 opacity-50" style="background: linear-gradient(145deg, var(--teal-soft) 0%, transparent 40%, rgba(196,92,65,0.06) 100%);"></div>

    <div class="relative z-10 w-full px-4">
        <div class="border-2 card-compact responsive-card animate-in opacity-0" style="background: var(--bg-card); font-family: var(--font-display);">
            <div class="text-center mb-6">
            <div class="flex items-center justify-center gap-3">
                <div class="logo-mark">
                    <img src="{{ asset('img/logo.svg') }}" alt="Santa Ana logo">
                </div>
                <div class="text-left">
                    <h1 class="font-extrabold brand-title leading-snug mb-0" style="color: var(--primary);">Barangay Health Center Consultation and Referral System</h1>
                    <p class="muted-xs leading-tight">Sta. Ana Health Center</p>
                </div>
            </div>
            <p class="text-xs mt-4 muted-xs leading-relaxed">Pag-log in aron maka-access sa mga rekord sa pasyente ug mga serbisyo.</p>
            </div>

            <form action="{{ route('login.process') }}" method="POST" id="login-form">
            <input type="hidden" name="_token" id="csrf-token-input" value="{{ csrf_token() }}" autocomplete="off">

            @if (session('error') || session('session_expired'))
                <div class="mb-4 p-3 text-sm" style="background: rgba(196, 92, 65, 0.08); border-left:4px solid var(--accent); color: var(--accent-strong);">
                <p class="font-medium text-sm">{{ session('error', 'Your session has expired. Please sign in again.') }}</p>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-4 p-3 text-sm" style="background: rgba(13, 74, 60, 0.08); border-left:4px solid var(--primary); color: var(--primary);">
                <p class="font-medium text-sm">{{ session('success') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 text-sm" style="background: rgba(196, 92, 65, 0.08); border-left:4px solid var(--accent); color: var(--accent-strong);">
                <p class="font-medium text-sm">Login failed. {{ $errors->first() }}</p>
                </div>
            @endif

            <div class="mb-4">
                <label for="username" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Username</label>
                <input type="text" name="username" id="username" value="{{ old('username') }}"
                   class="w-full px-3 py-2 rounded-md border text-[var(--ink)] placeholder-[var(--ink-muted)] focus:outline-none focus:ring-2 transition text-sm"
                   style="border-color: var(--border); --tw-ring-color: var(--primary);"
                   placeholder="Username" required autofocus>
            </div>

            <div class="mb-5">
                <label for="password" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Password</label>
                <input type="password" name="password" id="password"
                   class="w-full px-3 py-2 rounded-md border text-[var(--ink)] placeholder-[var(--ink-muted)] focus:outline-none focus:ring-2 transition text-sm"
                   style="border-color: var(--border); --tw-ring-color: var(--primary);"
                   placeholder="Password" required>
            </div>

            <div class="flex items-center justify-between mb-5 text-sm">
                <label class="flex items-center text-sm cursor-pointer" style="color: var(--ink-muted);">
                <input type="checkbox" name="remember" class="mr-2 h-4 w-4 rounded border-[var(--border)]" style="accent-color: var(--primary);">
                Remember me
                </label>
                <a href="{{ route('password.forgot') }}" class="text-sm font-medium" style="color: var(--primary); text-decoration: underline;">
                Forgot Password?
                </a>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 active:scale-[0.99]" style="background: var(--primary); color: #ffffff; box-shadow: 0 2px 8px rgba(13, 74, 60, 0.35);">
                Sign in
            </button>
            </form>

            <p class="text-center text-xs mt-6" style="color: var(--ink-muted);">
            &copy; {{ date('Y') }} | Developed by
            <a href="facebook.com/charlz.chavaria" class="font-medium" style="color: var(--primary);">
                PHINMA COC Students
            </a>
            </p>
        </div>
    </div>

    <script>
        (function () {
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            var tokenInput = document.getElementById('csrf-token-input');
            var refreshIntervalMs = Math.max(60000, {{ (int) config('session.lifetime') * 60 * 1000 }} / 2);

            function updateCsrfToken(token) {
                if (tokenInput) {
                    tokenInput.value = token;
                }
                if (tokenMeta) {
                    tokenMeta.setAttribute('content', token);
                }
            }

            function refreshCsrfToken() {
                fetch('{{ route('login') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to refresh CSRF token');
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (data.token) {
                        updateCsrfToken(data.token);
                    }
                })
                .catch(function () {
                    // Keep the last known token; server-side handler redirects on mismatch.
                });
            }

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') {
                    refreshCsrfToken();
                }
            });

            setInterval(refreshCsrfToken, refreshIntervalMs);
        })();
    </script>
</body>
</html>
