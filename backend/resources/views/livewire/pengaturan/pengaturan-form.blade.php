<div class="max-w-2xl space-y-6">
    @if(session("success"))
        <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session("success") }}</div>
    @endif

    <div class="card">
        <div class="p-6 space-y-8">

            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">?? Konfigurasi Poin</h3>
                <div>
                    <label class="form-label">Poin per Botol <span class="text-red-500">*</span></label>
                    <input wire:model="poinPerBotol" type="number" min="1" class="form-input" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Jumlah poin yang dikreditkan ke pengguna setiap 1 botol disetorkan.</p>
                    @error("poinPerBotol") <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">?? Lokasi Depot Pengangkutan</h3>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Nama Depot</label>
                        <input wire:model="depotNama" type="text" class="form-input" />
                        @error("depotNama") <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Latitude</label>
                            <input wire:model="depotLat" type="number" step="0.0001" class="form-input font-mono" />
                            @error("depotLat") <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Longitude</label>
                            <input wire:model="depotLng" type="number" step="0.0001" class="form-input font-mono" />
                            @error("depotLng") <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">??? Konfigurasi TSP</h3>
                <div>
                    <label class="form-label">Threshold Kapasitas (%)</label>
                    <input wire:model="tspThreshold" type="number" min="1" max="100" class="form-input" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Node yang kapasitasnya mencapai persentase ini akan masuk dalam kalkulasi rute TSP.</p>
                    @error("tspThreshold") <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-gray-100 dark:border-gray-700">
                <button wire:click="save" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading.remove>Simpan Pengaturan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>
