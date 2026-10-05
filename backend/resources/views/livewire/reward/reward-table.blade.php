<div class="space-y-4">
    @if(session('success'))
        <div class="p-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="card p-4 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode, nama, kategori, deskripsi..." class="form-input w-auto text-sm flex-1 min-w-[200px]" />
        
        <select wire:model.live="filterKategori" class="form-input w-auto text-sm">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category }}">{{ $category }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStatus" class="form-input w-auto text-sm">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
        </select>

        <button type="button" wire:click="openCreateModal" class="btn-primary ml-auto text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Reward
        </button>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="table-th">Kode</th>
                        <th class="table-th">Nama Reward</th>
                        <th class="table-th">Kategori</th>
                        <th class="table-th text-right">Poin</th>
                        <th class="table-th text-right">Stok</th>
                        <th class="table-th text-center">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($rewards as $reward)
                    <tr class="hover:bg-gray-50 transition-colors">
                        @php
                            $imageIsUrl = $reward->gambar && filter_var($reward->gambar, FILTER_VALIDATE_URL);
                            $hasStoredImage = $imageIsUrl || ($reward->gambar && \Illuminate\Support\Facades\Storage::disk('public')->exists($reward->gambar));
                            $imageSource = $imageIsUrl ? $reward->gambar : ($hasStoredImage ? '/storage/' . ltrim($reward->gambar, '/') : null);
                        @endphp
                        <td class="table-td font-mono text-xs font-semibold text-gray-700">
                            {{ $reward->kode }}
                        </td>
                        <td class="table-td">
                            <div class="flex items-center gap-3">
                                @if($imageSource)
                                    <img src="{{ $imageSource }}" alt="{{ $reward->nama }}" class="w-10 h-10 object-cover rounded-md border border-gray-200" onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')" />
                                @endif
                                <div class="{{ $imageSource ? 'hidden' : '' }} w-10 h-10 rounded-md bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 font-bold text-xs flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H4.5a1.5 1.5 0 01-1.5-1.5v-8.25M21 11.25H3m18 0l-1.5-4.5H4.5L3 11.25m9-8.25v8.25M9.75 3a2.25 2.25 0 00-2.25 2.25c0 1.243 1.007 2.25 2.25 2.25h2.25V5.25A2.25 2.25 0 009.75 3zm4.5 0a2.25 2.25 0 012.25 2.25c0 1.243-1.007 2.25-2.25 2.25h-2.25V5.25A2.25 2.25 0 0114.25 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 text-sm">{{ $reward->nama }}</div>
                                    <div class="text-xs text-gray-500 max-w-xs truncate" title="{{ $reward->deskripsi }}">
                                        {{ $reward->deskripsi ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="table-td text-xs">
                            <span class="badge-blue">{{ $reward->kategori }}</span>
                        </td>
                        <td class="table-td text-right font-semibold text-emerald-600 text-sm">
                            {{ number_format($reward->poin) }} pts
                        </td>
                        <td class="table-td text-right">
                            <span class="font-medium text-sm {{ $reward->stok > 0 ? 'text-gray-900' : 'text-red-500 font-semibold' }}">
                                {{ number_format($reward->stok) }}
                            </span>
                        </td>
                        <td class="table-td text-center">
                            <button wire:click="toggleStatus({{ $reward->id }})" title="Klik untuk mengubah status" class="inline-block transition-transform hover:scale-105">
                                @if($reward->is_active)
                                    <span class="badge-green cursor-pointer">Aktif</span>
                                @else
                                    <span class="badge-gray cursor-pointer">Nonaktif</span>
                                @endif
                            </button>
                        </td>
                        <td class="table-td text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" wire:click="edit({{ $reward->id }})" class="text-xs text-blue-600 hover:underline font-medium">Edit</button>
                                <button wire:click="delete({{ $reward->id }})" wire:confirm="Hapus reward '{{ $reward->nama }}'?" class="text-xs text-red-500 hover:underline font-medium">Hapus</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="table-td text-center text-gray-400 py-10">Tidak ada data reward.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rewards->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 bg-white">{{ $rewards->links() }}</div>
        @endif
    </div>

    {{-- Modal Tambah / Edit --}}
    @if($showModal)
    <div wire:key="reward-modal-{{ $editingId ?? 'create' }}" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl ring-1 ring-gray-200 w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">{{ $editingId ? 'Edit Reward' : 'Tambah Reward' }}</h3>
                <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="{{ $editingId ? 'update' : 'save' }}">
                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Kode Reward <span class="text-red-500">*</span></label>
                            <input wire:model="kode" type="text" placeholder="Masukkan kode reward" class="form-input font-mono" />
                            @error('kode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Kategori <span class="text-red-500">*</span></label>
                            <select wire:model="kategori" class="form-input">
                                <option value="Voucher">Voucher</option>
                                <option value="E-Wallet">E-Wallet</option>
                                <option value="Pulsa">Pulsa</option>
                                <option value="Merchandise">Merchandise</option>
                            </select>
                            @error('kategori') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Nama Reward <span class="text-red-500">*</span></label>
                        <input wire:model="nama" type="text" placeholder="Contoh: Voucher Belanja Rp25.000" class="form-input" />
                        @error('nama') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Deskripsi</label>
                        <textarea wire:model="deskripsi" rows="3" placeholder="Penjelasan mengenai reward..." class="form-input"></textarea>
                        @error('deskripsi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Poin Dibutuhkan <span class="text-red-500">*</span></label>
                            <input wire:model="poin" type="number" min="0" class="form-input" />
                            @error('poin') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Stok <span class="text-red-500">*</span></label>
                            <input wire:model="stok" type="number" min="0" class="form-input" />
                            @error('stok') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Foto Reward</label>
                        <input wire:model="gambar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="form-input" />
                        <p class="mt-1 text-xs text-gray-500">JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.</p>
                        @error('gambar') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="gambar" class="mt-2 text-xs text-gray-500">Mengunggah foto...</div>
                        @if($gambar && $gambar->isPreviewable())
                            <div class="mt-2 flex items-center gap-3 p-2 bg-gray-50 rounded border border-gray-200">
                                <img src="{{ $gambar->temporaryUrl() }}" alt="Preview foto reward" class="w-16 h-16 object-cover rounded" />
                                <div class="flex-1 text-xs text-gray-600">Preview foto baru</div>
                                <button type="button" wire:click="removeSelectedImage" class="text-xs text-red-600 hover:underline">Hapus pilihan</button>
                            </div>
                        @elseif($gambar)
                            <div class="mt-2 flex items-center gap-3 p-2 bg-gray-50 rounded border border-gray-200">
                                <span class="flex-1 text-xs text-gray-600">{{ $gambar->getClientOriginalName() }}</span>
                                <button type="button" wire:click="removeSelectedImage" class="text-xs text-red-600 hover:underline">Hapus pilihan</button>
                            </div>
                        @elseif($gambarLama)
                            @php
                                $oldImageIsUrl = filter_var($gambarLama, FILTER_VALIDATE_URL);
                                $oldImageExists = $oldImageIsUrl || \Illuminate\Support\Facades\Storage::disk('public')->exists($gambarLama);
                                $oldImageSource = $oldImageIsUrl ? $gambarLama : ($oldImageExists ? '/storage/' . ltrim($gambarLama, '/') : null);
                            @endphp
                            @if($oldImageSource)
                                <div class="mt-2 flex items-center gap-3 p-2 bg-gray-50 rounded border border-gray-200">
                                    <img src="{{ $oldImageSource }}" alt="Foto reward saat ini" class="w-16 h-16 object-cover rounded" onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')" />
                                    <div class="{{ $oldImageSource ? 'hidden' : '' }} w-16 h-16 rounded bg-emerald-50 flex items-center justify-center text-emerald-600">Foto tidak tersedia</div>
                                    <span class="text-xs text-gray-500">Foto saat ini</span>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input wire:model="is_active" id="is_active" type="checkbox" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                        <label for="is_active" class="text-sm font-medium text-gray-700 select-none cursor-pointer">Reward Aktif (Tersedia untuk ditukarkan)</label>
                    </div>
                    @error('is_active') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 justify-end px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                    <button type="button" wire:click="closeModal" class="btn-secondary text-sm">Batal</button>
                    <button type="submit" wire:target="save,update" wire:loading.attr="disabled" class="btn-primary text-sm">{{ $editingId ? 'Simpan Perubahan' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
