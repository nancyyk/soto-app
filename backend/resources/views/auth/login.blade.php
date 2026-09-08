@extends("layouts.auth")
@section("title", "Masuk")

@section("content")
<h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-6">Masuk ke Panel Admin</h2>

@if ($errors->any())
    <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route("login") }}" class="space-y-4">
    @csrf
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
        <input id="email" name="email" type="email" value="{{ old("email") }}" required autofocus
               class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:border-green-500 focus:ring-green-500"
               placeholder="admin@soto.test" />
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
        <input id="password" name="password" type="password" required
               class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:border-green-500 focus:ring-green-500"
               placeholder="????????" />
    </div>
    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <input type="checkbox" name="remember" class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500" />
            Ingat saya
        </label>
    </div>
    <button type="submit" class="btn-primary w-full justify-center py-2.5">
        Masuk
    </button>
</form>
@endsection
