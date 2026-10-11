<div class="max-w-2xl space-y-6">
    @if(session("success"))
        <div class="p-3 rounded-lg bg-green-50  border border-green-200  text-sm text-green-700 ">
            ✓ {{ session("success") }}
        </div>
    @endif

    {{-- KARTU 1: Lokasi Pangkalan Depot Truk --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-800  mb-1 flex items-center gap-2">
            <span>🏢</span> Pangkalan Logistik (Depot Pengangkutan)
        </h3>
        <p class="text-xs text-gray-400  mb-4 pb-2 border-b border-gray-100 ">
            Pangkalan pool armada truk sampah tempat awal keberangkatan dan kepulangan akhir (Sirkuit TSP).
        </p>

        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="form-label mb-0">Pilihan Pangkalan / Pool</label>
                    <button type="button" wire:click="resetToDefaultDepot" class="text-xs text-green-600 hover:text-green-700 font-medium">
                        ↺ Reset ke Pos Pusat ITS
                    </button>
                </div>
                <select wire:model.live="depotSource" class="form-input">
                    <optgroup label="🏢 Pangkalan / Pool Rekomendasi (Terpisah dari Mesin)">
                        <option value="pos_its">Pos Pengangkutan Pusat ITS (-7.2750, 112.7900)</option>
                        <option value="bank_sampah">Bank Sampah Induk Surabaya (-7.2820, 112.7980)</option>
                        <option value="dlh">Kantor Dinas Lingkungan Hidup DLH (-7.2650, 112.7520)</option>
                    </optgroup>
                    <optgroup label="✏️ Kustom">
                        <option value="custom">— Input Koordinat Manual —</option>
                    </optgroup>
                    <optgroup label="📍 Titik Mesin SOTO (Jika salah satu mesin dijadikan pangkalan)">
                        @foreach($machines as $m)
                            <option value="machine_{{ $m['id'] }}">{{ $m['nama_lokasi'] }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <p class="text-xs text-gray-400  mt-1">Pilih pangkalan truk pengangkut. Disarankan menggunakan Pos Pusat ITS agar terpisah dari mesin penampungan botol.</p>
            </div>

            <div>
                <label class="form-label">Nama Pangkalan Depot</label>
                <input
                    wire:model="depotNama"
                    type="text"
                    class="form-input {{ $depotSource !== 'custom' ? 'bg-gray-50  text-gray-500 ' : '' }}"
                    {{ $depotSource !== 'custom' ? 'readonly' : '' }}
                />
                @error("depotNama") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Latitude</label>
                    <input
                        wire:model="depotLat"
                        type="number" step="0.0001"
                        class="form-input font-mono {{ $depotSource !== 'custom' ? 'bg-gray-50  text-gray-500 ' : '' }}"
                        {{ $depotSource !== 'custom' ? 'readonly' : '' }}
                    />
                    @error("depotLat") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Longitude</label>
                    <input
                        wire:model="depotLng"
                        type="number" step="0.0001"
                        class="form-input font-mono {{ $depotSource !== 'custom' ? 'bg-gray-50  text-gray-500 ' : '' }}"
                        {{ $depotSource !== 'custom' ? 'readonly' : '' }}
                    />
                    @error("depotLng") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @if($depotSource !== 'custom')
                <p class="text-xs text-blue-500  flex items-center gap-1">
                    ℹ️ Koordinat otomatis terkunci dari pilihan pangkalan di atas.
                </p>
            @endif
        </div>
    </div>

    {{-- KARTU 2: Ambang Batas Pengangkutan Mesin --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-800  mb-1 flex items-center gap-2">
            <span>🚚</span> Ambang Batas Pengangkutan Mesin (Algoritma TSP)
        </h3>
        <p class="text-xs text-gray-400  mb-4 pb-2 border-b border-gray-100 ">
            Kriteria kepenuhan mesin penampung botol (RVM 1 s/d 5) agar didatangi dan dikosongkan oleh petugas.
        </p>

        <div>
            <label class="form-label">Threshold Kepenuhan Mesin (%)</label>
            <div class="flex items-center gap-3">
                <input wire:model="tspThreshold" type="number" min="1" max="100" class="form-input w-36 font-mono font-bold" />
                <span class="text-sm text-gray-500 ">% Kapasitas Mesin</span>
            </div>
            <p class="text-xs text-gray-400  mt-2">
                Hanya mesin SOTO dengan kapasitas <strong>&ge; {{ $tspThreshold }}%</strong> yang akan dikunjungi oleh armada pengangkut. Mesin di bawah nilai ini akan dilewati agar menghemat jarak tempuh.
            </p>
            @error("tspThreshold") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex justify-end pt-2">
        <button wire:click="save" wire:loading.attr="disabled" class="btn-primary px-6 py-2.5">
            <span wire:loading.remove>Simpan Seluruh Pengaturan</span>
            <span wire:loading>Menyimpan...</span>
        </button>
    </div>
</div>

