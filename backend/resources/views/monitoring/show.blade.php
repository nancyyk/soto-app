@extends("layouts.app")
@section("title", $machine->nama_lokasi)
@section("content")
<div class="mb-4">
    <a href="{{ route("monitoring.index") }}" class="text-sm text-green-600 hover:text-green-800 font-medium">? Kembali ke Monitoring</a>
</div>
<livewire:monitoring.node-detail :machine="$machine" />
@endsection
