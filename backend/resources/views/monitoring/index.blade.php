@extends("layouts.app")
@section("title", "Monitoring Node RVM")
@section("content")
<div class="space-y-4">
    <p class="text-sm text-gray-500">Status real-time semua 5 node RVM. Update otomatis via WebSocket.</p>
    <livewire:monitoring.node-grid />
</div>
@endsection
