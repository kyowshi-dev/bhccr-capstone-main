<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify Password Reset</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        html { font-size: clamp(14px, 1.2vw, 18px); }

        .responsive-card {
            width: min(92vw, 640px);
            margin-left: auto;
            margin-right: auto;
        }

        .card-compact { padding: 1.25rem; border-radius: 1rem; }

        .logo-mark {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-surface);
        }

        .logo-mark img { width: 100%; height: 100%; object-fit: cover; }

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

        .muted-xs { font-size: 0.78rem; color: var(--ink-muted); }

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
                <p class="text-xs mt-4 muted-xs leading-relaxed">Isulod ang verification code ug ang imong bag-ong password.</p>
            </div>

            <form action="{{ route('password.forgot.verify.submit') }}" method="POST" id="verify-form">
                @csrf
                <input type="hidden" name="username" value="{{ $username }}">

                @if (session('status'))
                    <div class="mb-4 p-3 text-sm" style="background: rgba(13, 74, 60, 0.08); border-left:4px solid var(--primary); color: var(--primary);">
                        <p class="font-medium text-sm">{{ session('status') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-3 text-sm" style="background: rgba(196, 92, 65, 0.08); border-left:4px solid var(--accent); color: var(--accent-strong);">
                        <p class="font-medium text-sm">{{ $errors->first() }}</p>
                    </div>
                @endif

                <div class="mb-4">
                    <label for="otp" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Verification code</label>
                    <input type="text" name="otp" id="otp" value="{{ old('otp') }}"
                       class="w-full px-3 py-2 rounded-md border text-[var(--ink)] placeholder-[var(--ink-muted)] focus:outline-none focus:ring-2 transition text-sm tracking-[0.3em]"
                       style="border-color: var(--border); --tw-ring-color: var(--primary);"
                       placeholder="123456" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
                    <p class="text-xs mt-2" style="color: var(--ink-muted);">Check the email registered to your account. The code expires in 15 minutes.</p>
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">New password</label>
                    <input type="password" name="password" id="password"
                       class="w-full px-3 py-2 rounded-md border text-[var(--ink)] placeholder-[var(--ink-muted)] focus:outline-none focus:ring-2 transition text-sm"
                       style="border-color: var(--border); --tw-ring-color: var(--primary);"
                       placeholder="At least 8 characters" minlength="8" required>
                </div>

                <div class="mb-5">
                    <label for="password_confirmation" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Confirm new password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                       class="w-full px-3 py-2 rounded-md border text-[var(--ink)] placeholder-[var(--ink-muted)] focus:outline-none focus:ring-2 transition text-sm"
                       style="border-color: var(--border); --tw-ring-color: var(--primary);"
                       placeholder="Repeat your new password" minlength="8" required>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 active:scale-[0.99]" style="background: var(--primary); color: #ffffff; box-shadow: 0 2px 8px rgba(13, 74, 60, 0.35);">
                    Reset password
                </button>
            </form>

            <p class="text-center text-xs mt-5" style="color: var(--ink-muted);">
                <a href="{{ route('password.forgot') }}" class="font-medium" style="color: var(--primary); text-decoration: underline;">← Request a new code</a>
            </p>

            <p class="text-center text-xs mt-6" style="color: var(--ink-muted);">
                &copy; {{ date('Y') }} | Developed by
                <a href="facebook.com/charlz.chavaria" class="font-medium" style="color: var(--primary);">
                    PHINMA COC Students
                </a>
            </p>
        </div>
    </div>
</body>
</html>