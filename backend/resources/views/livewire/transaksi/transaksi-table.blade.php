<div class="space-y-4">
    <div class="card p-4">
        <div class="flex flex-wrap gap-3">
            <select wire:model.live="filterMachine" class="form-input w-auto text-sm">
                <option value="">Semua Mesin</option>
                @foreach($machines as $m)
                    <option value="{{ $m->id }}">{{ $m->nama_lokasi }}</option>
                @endforeach
            </select>
            <input type="date" wire:model.live="filterDate" class="form-input w-auto text-sm" />
            @if($filterMachine || $filterDate)
                <button wire:click="$set(''filterMachine'',''''); $set(''filterDate'','''')" class="btn-secondary text-sm">Reset Filter</button>
            @endif
            <span class="ml-auto text-xs text-gray-400 dark:text-gray-500 self-center">
                Live <span wire:poll.10s="refresh" class="text-green-500">?</span>
            </span>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="table-th">ID</th>
                        <th class="table-th">Pengguna</th>
                        <th class="table-th">Lokasi Mesin</th>
                        <th class="table-th text-right">Botol</th>
                        <th class="table-th text-right">Poin</th>
                        <th class="table-th">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    @forelse($transactions as $t)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="table-td text-gray-400 dark:text-gray-500 font-mono text-xs">#{{ $t->id }}</td>
                        <td class="table-td font-medium text-gray-900 dark:text-gray-100">{{ $t->user?->nama ?? "?" }}</td>
                        <td class="table-td text-gray-500 dark:text-gray-400">{{ $t->machine?->nama_lokasi ?? "?" }}</td>
                        <td class="table-td text-right font-semibold text-gray-900 dark:text-gray-100">{{ $t->jumlah_botol }}</td>
                        <td class="table-td text-right"><span class="badge-green">+{{ $t->poin_diperoleh }}</span></td>
                        <td class="table-td text-gray-500 dark:text-gray-400 text-xs">
                            {{ $t->created_at?->locale("id")->isoFormat("D MMM YYYY, HH:mm") }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="table-td text-center text-gray-400 dark:text-gray-500 py-10">Tidak ada data transaksi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
