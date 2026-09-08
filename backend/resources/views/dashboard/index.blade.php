@extends("layouts.app")
@section("title", "Dashboard")
@section("content")
<div class="space-y-6">

    <livewire:dashboard.stats-overview />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <livewire:dashboard.botol-chart />
        </div>

        {{-- Quick Node Status --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Status Node RVM</h3>
            <div class="space-y-3">
                @foreach($machines as $m)
                @php $pct = $m->kapasitas_terkini; @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600 dark:text-gray-400 truncate max-w-[160px]">{{ $m->nama_lokasi }}</span>
                        <span class="{{ $pct >= 80 ? "text-red-600 dark:text-red-400 font-semibold" : "text-gray-500 dark:text-gray-400" }}">{{ $pct }}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill {{ $pct >= 80 ? "bg-red-500" : ($pct >= 50 ? "bg-yellow-400" : "bg-green-500") }}"
                             style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            <a href="{{ route("monitoring.index") }}" class="mt-4 block text-xs text-green-600 dark:text-green-400 hover:underline text-center">
                Lihat detail semua node ?
            </a>
        </div>
    </div>
</div>
@endsection
