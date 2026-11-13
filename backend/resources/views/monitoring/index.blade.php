@extends("layouts.app")
@section("title", "Monitoring Node RVM")
@section("content")
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-gray-500">Status real-time node RVM. Update otomatis setiap 3 detik & via WebSocket.</p>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Auto-refresh aktif (3s)
        </span>
    </div>
    <livewire:monitoring.node-grid />
</div>

@endsection
