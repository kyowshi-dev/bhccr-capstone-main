<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - BHCIS Sta. Ana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --primary: #0d4a3c;
            --ink: #1a1a1a;
            --ink-muted: #6b7280;
            --border: #e5e7eb;
            --accent-blue: #3b82f6;
            --bg-page: #f9fafb;
        }
        body { font-family: 'Poppins', sans-serif; margin: 0; background: var(--bg-page); color: var(--ink); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="text-center max-w-md mx-auto p-8">
        <div class="mb-6">
            <i class="fa-solid fa-map-location-dot text-6xl" style="color: var(--accent-blue);"></i>
        </div>
        <h1 class="font-display text-3xl font-semibold mb-2" style="color: var(--ink);">Page Not Found</h1>
        <p class="text-base mb-6" style="color: var(--ink-muted);">The page you are looking for may have been moved, deleted, or never existed.</p>
        <div class="flex gap-3 justify-center">
            <a href="{{ url()->previous() ?? '/' }}" class="px-6 py-2 rounded-xl text-sm font-semibold transition" style="background: var(--border); color: var(--ink); text-decoration: none;">Go Back</a>
            <a href="{{ url('/') }}" class="px-6 py-2 rounded-xl text-white text-sm font-semibold transition" style="background: var(--primary); text-decoration: none;">Go Home</a>
        </div>
    </div>
</body>
</html>
