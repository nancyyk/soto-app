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
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-gray-200 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-gray-200">
                <div class="min-w-0">
                    <h3 class="font-semibold text-lg text-gray-800">Detail Penukaran #{{ $editingId }}</h3>
                    <p class="text-sm text-gray-500 mt-1">Periksa informasi penukaran dan perbarui status.</p>
                </div>
                @if($status === 'Selesai')
                    <span class="badge-green shrink-0">Selesai</span>
                @elseif($status === 'Pengiriman')
                    <span class="badge-blue shrink-0">Pengiriman</span>
                @else
                    <span class="badge-yellow shrink-0">{{ $status ?: 'Proses' }}</span>
                @endif
                <button type="button" wire:click="closeModal" aria-label="Tutup modal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="updateStatus">
                <div class="p-5 sm:p-6 space-y-4 overflow-y-auto max-h-[68vh]">
                    <section class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Informasi Penukaran</h4>
                        <dl class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-3 text-sm">
                            <div class="min-w-0">
                                <dt class="text-xs text-gray-500 mb-1">Pengguna</dt>
                                <dd class="font-medium text-gray-900 break-words">{{ $selectedUserName }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs text-gray-500 mb-1">Tanggal</dt>
                                <dd class="font-medium text-gray-900">{{ $selectedDate }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs text-gray-500 mb-1">Reward</dt>
                                <dd class="font-medium text-gray-900 break-words">{{ $selectedRewardName }}</dd>
                                <dd class="text-xs text-gray-500 mt-0.5">{{ $selectedRewardCategory ?: 'Kategori tidak tersedia' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 mb-1">Poin Ditukar</dt>
                                <dd class="font-semibold text-emerald-700">-{{ number_format($selectedPointsUsed) }} pts</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 mb-1">Status</dt>
                                <dd class="font-medium text-gray-900">{{ $status ?: 'Proses' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-4">
                        @if($selectedRewardCategory === 'Merchandise')
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Alamat Pengiriman</h4>
                            <p class="max-h-24 overflow-y-auto text-sm text-gray-700 leading-relaxed whitespace-pre-line break-words pr-2">{{ $selectedShippingAddress ?: 'Alamat belum tersedia' }}</p>
                        @elseif($selectedRewardCategory === 'E-Wallet')
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Nomor E-Wallet</h4>
                            <p class="text-sm font-mono font-medium text-gray-900 leading-relaxed break-words">{{ $selectedRecipientNumber ?: 'Nomor E-Wallet belum tersedia' }}</p>
                        @elseif($selectedRewardCategory === 'Pulsa')
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Nomor HP Tujuan</h4>
                            <p class="text-sm font-mono font-medium text-gray-900 leading-relaxed break-words">{{ $selectedRecipientNumber ?: 'Nomor HP belum tersedia' }}</p>
                        @elseif($selectedRewardCategory === 'Voucher')
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Data Penerima</h4>
                            <p class="text-sm text-gray-500 leading-relaxed">Tidak diperlukan — kode voucher diberikan setelah penukaran.</p>
                        @else
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Data Penerima</h4>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $selectedShippingAddress ?: ($selectedRecipientNumber ?: 'Data penerima belum tersedia') }}</p>
                        @endif
                    </section>

                    <section @if($selectedRewardCategory === 'Merchandise') id="shipment-fields" @endif class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <div class="min-w-0">
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

                            @if($selectedRewardCategory === 'Merchandise')
                            <div class="min-w-0">
                                <label class="form-label">Ekspedisi @if($status === 'Pengiriman') <span class="text-red-500">*</span> @endif</label>
                                <select wire:model="ekspedisi" class="form-input">
                                    <option value="">Pilih Ekspedisi</option>
                                    <option value="POS">POS</option>
                                    <option value="J&T">J&amp;T</option>
                                </select>
                                @error('ekspedisi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="min-w-0">
                                <label class="form-label">Nomor Resi @if($status === 'Pengiriman') <span class="text-red-500">*</span> @endif</label>
                                <input wire:model="nomor_resi" type="text" placeholder="Masukkan nomor resi" class="form-input font-mono" />
                                @error('nomor_resi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            @endif
                        </div>
                    </section>
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
