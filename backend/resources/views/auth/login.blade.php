@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-semibold tracking-tight text-gray-900 ">Masuk</h2>
    <p class="text-sm text-gray-500  mt-2">Masukkan kredensial Anda untuk melanjutkan.</p>
</div>

@if ($errors->any())
    <div class="mb-6 p-4 rounded-md bg-red-50  border border-red-200  text-sm text-red-600 ">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf
    <div>
        <label for="email" class="form-label">Alamat Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="form-input" placeholder="admin@soto.test" />
    </div>
    <div>
        <label for="password" class="form-label">Password</label>
        <input id="password" name="password" type="password" required class="form-input" placeholder="••••••••" />
    </div>
    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-gray-600  cursor-pointer">
            <input type="checkbox" name="remember" class="rounded border-gray-300  text-emerald-600 focus:ring-emerald-500 " />
            Ingat saya
        </label>
    </div>
    <button type="submit" class="btn-primary w-full justify-center mt-2">
        Masuk ke Dashboard
    </button>
</form>
@endsection

