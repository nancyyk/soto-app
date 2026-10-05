<div class="space-y-4">
    @if(session('success'))
        <div class="p-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-4 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari pengguna, reward, ekspedisi, no. resi..." class="form-input w-auto text-sm flex-1 min-w-[240px]" />
        
        <select wire:model.live="filterStatus" class="form-input w-auto text-sm">
            <option value="">Semua Status</option>
            <option value="Proses">Proses</option>
            <option value="Pengiriman">Pengiriman</option>
            <option value="Selesai">Selesai</option>
        </select>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Pengguna</th>
                        <th class="table-th">Reward</th>
                        <th class="table-th text-right">Poin</th>
                        <th class="table-th text-center">Status</th>
                        <th class="table-th text-center">Ekspedisi</th>
                        <th class="table-th">Nomor Resi</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($redemptions as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="table-td whitespace-nowrap text-xs text-gray-500">
                            {{ $item->created_at ? $item->created_at->format('d M Y, H:i') : '-' }}
                        </td>
                        <td class="table-td">
                            <div class="font-medium text-gray-900 text-xs">{{ $item->user?->nama ?? 'Pengguna #' . $item->user_id }}</div>
                            <div class="text-[11px] text-gray-400">{{ $item->user?->email ?? '-' }}</div>
                        </td>
                        <td class="table-td">
                            <div class="font-medium text-gray-900 text-xs flex items-center gap-1.5">
                                @if($item->reward?->kode)
                                    <span class="font-mono text-[11px] text-gray-500 font-semibold">{{ $item->reward->kode }}</span>
                                    <span>&bull;</span>
                                @endif
                                <span>{{ $item->reward?->nama ?? 'Reward #' . $item->reward_id }}</span>
                            </div>
                            @if($item->reward?->kategori)
                                <div class="text-[11px] text-gray-400">{{ $item->reward->kategori }}</div>
                            @endif
                        </td>
                        <td class="table-td text-right font-semibold text-emerald-600 text-xs">
                            -{{ number_format($item->poin) }} pts
                        </td>
                        <td class="table-td text-center">
                            @if($item->status === 'Selesai')
                                <span class="badge-green">Selesai</span>
                            @elseif($item->status === 'Pengiriman')
                                <span class="badge-blue">Pengiriman</span>
                            @else
                                <span class="badge-yellow">{{ $item->status ?? 'Proses' }}</span>
                            @endif
                        </td>
                        <td class="table-td text-center text-xs font-medium text-gray-700">
                            {{ $item->reward?->kategori === 'Merchandise' ? ($item->ekspedisi ?: '-') : '-' }}
                        </td>
                        <td class="table-td text-xs font-mono text-gray-700">
                            {{ $item->reward?->kategori === 'Merchandise' ? ($item->nomor_resi ?: '-') : '-' }}
                        </td>
                        <td class="table-td text-right">
                            <button type="button" wire:click="openUpdateModal({{ $item->id }})" class="text-xs text-blue-600 hover:underline font-medium">
                                Update
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="table-td text-center text-gray-400 py-10">Tidak ada riwayat penukaran reward.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($redemptions->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 bg-white">{{ $redemptions->links() }}</div>
        @endif
    </div>

    {{-- Modal Update Status & Pengiriman --}}
    @if($showModal)
    <div wire:key="redemption-modal-{{ $editingId }}" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl ring-1 ring-gray-200 w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">Update Penukaran #{{ $editingId }}</h3>
                <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="updateStatus">
                <div class="p-6 space-y-4">
                    <div class="p-3 bg-gray-50 rounded-lg space-y-1.5 text-xs text-gray-600 border border-gray-200">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tanggal:</span>
                            <span class="font-medium text-gray-900">{{ $selectedDate }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Pengguna:</span>
                            <span class="font-medium text-gray-900">{{ $selectedUserName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Reward:</span>
                            <span class="font-medium text-gray-900">{{ $selectedRewardName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Kategori:</span>
                            <span class="font-medium text-gray-900">{{ $selectedRewardCategory ?: '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Poin Ditukar:</span>
                            <span class="font-semibold text-emerald-600">-{{ number_format($selectedPointsUsed) }} pts</span>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Status <span class="text-red-500">*</span></label>
                        <select id="redemption-status" wire:model.live="status" class="form-input">
                            <option value="Proses">Proses</option>
                            @if($selectedRewardCategory === 'Merchandise')
                                <option id="shipping-status-option" value="Pengiriman">Pengiriman</option>
                            @endif
                            <option value="Selesai">Selesai</option>
                        </select>
                        @error('status') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if($selectedRewardCategory === 'Merchandise' && ($status === 'Pengiriman' || $status === 'Selesai'))
                    <div id="shipment-fields" class="space-y-4">
                    <div>
                        <label class="form-label">Ekspedisi @if($status === 'Pengiriman') <span class="text-red-500">*</span> @endif</label>
                        <select wire:model="ekspedisi" class="form-input">
                            <option value="">Pilih Ekspedisi</option>
                            <option value="POS">POS</option>
                            <option value="J&T">J&T</option>
                        </select>
                        @error('ekspedisi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Nomor Resi @if($status === 'Pengiriman') <span class="text-red-500">*</span> @endif</label>
                        <input wire:model="nomor_resi" type="text" placeholder="Masukkan nomor resi" class="form-input font-mono" />
                        @error('nomor_resi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    </div>
                    @endif
                </div>
                <div class="flex gap-3 justify-end px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                    <button type="button" wire:click="closeModal" class="btn-secondary text-sm">Batal</button>
                    <button type="submit" wire:target="updateStatus" wire:loading.attr="disabled" class="btn-primary text-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
