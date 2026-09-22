<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - SOTO Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col items-center justify-center p-4 font-sans antialiased text-gray-900 bg-gray-50">
    
    <div class="w-full max-w-[400px]">
        {{-- Logo (Sleek) --}}
        <div class="flex items-center gap-3 mb-8 justify-center">
            <div class="w-8 h-8 rounded bg-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">S</div>
            <span class="text-xl font-semibold tracking-tight text-gray-900 ">SOTO Admin</span>
        </div>

        {{-- Card --}}
        <div class="bg-white  rounded-lg border border-gray-200  shadow-sm overflow-hidden">
            <div class="px-6 py-8 sm:p-8">
                @yield('content')
            </div>
        </div>
        
        <p class="text-center text-xs text-gray-500  mt-8">
            &copy; {{ date('Y') }} SOTO (Sampah Otomatis Tukar Poin)
        </p>
    </div>

    </body>
</html>


