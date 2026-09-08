<!DOCTYPE html>
<html lang="id" class="h-full"
    x-data="{ dark: localStorage.getItem('soto-theme') === 'dark' }"
    x-init="document.documentElement.classList.toggle('dark', dark)"
    :class="{ 'dark': dark }">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield("title", "Login") ? SOTO Admin</title>
    @vite(["resources/css/app.css", "resources/js/app.js"])
</head>
<body class="h-full flex items-center justify-center p-4 bg-green-50 dark:bg-gray-950 transition-colors duration-200">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-green-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">S</div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">SOTO Admin</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sampah Otomatis Tukar Poin</p>
        </div>

        {{-- Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
            <div class="p-6">
                @yield("content")
            </div>
        </div>
    </div>
</body>
</html>
