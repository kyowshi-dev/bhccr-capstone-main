<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BHCIS') - Sta. Ana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .auth-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-sans, Poppins, sans-serif);
            background:
                linear-gradient(160deg, rgba(13, 74, 60, 0.88) 0%, rgba(10, 61, 50, 0.92) 100%),
                url('{{ asset('img/bg.svg') }}') center/cover no-repeat,
                var(--bg-page);
        }

        .auth-card {
            width: min(92vw, 560px);
            margin-left: auto;
            margin-right: auto;
            background: var(--bg-surface-elevated);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 1.25rem;
            box-shadow: 0 24px 60px -16px rgba(0, 0, 0, 0.45);
            padding: clamp(1.5rem, 3vw, 2.5rem);
        }

        .auth-title {
            font-size: clamp(1rem, 2.2vw, 1.25rem);
            color: var(--primary);
        }

        .auth-input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: var(--ink);
            background: #ffffff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .auth-input::placeholder {
            color: var(--ink-muted);
        }

        .auth-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--ring);
        }

        .auth-btn {
            width: 100%;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            background: var(--primary);
            box-shadow: 0 4px 14px -2px rgba(13, 74, 60, 0.5);
            transition: opacity 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
        }

        .auth-btn:hover {
            opacity: 0.95;
            box-shadow: 0 6px 18px -2px rgba(13, 74, 60, 0.55);
        }

        .auth-btn:active {
            transform: scale(0.99);
        }

        .muted-xs {
            font-size: 0.78rem;
            color: var(--ink-muted);
        }
    </style>
</head>
<body class="auth-body antialiased">
    <div class="relative z-10 w-full px-4">
        @yield('content')
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
