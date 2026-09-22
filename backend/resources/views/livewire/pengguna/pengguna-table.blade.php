<div class="space-y-4">
    @if(session("success"))
        <div class="p-3 rounded-lg bg-green-50  border border-green-200  text-sm text-green-700 ">{{ session("success") }}</div>
    @endif

    <div class="card p-4 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama / email..." class="form-input w-auto text-sm flex-1 min-w-[200px]" />
        <select wire:model.live="filterRole" class="form-input w-auto text-sm">
            <option value="">Semua Role</option>
            @foreach($roles as $r)
                <option value="{{ $r->value }}">{{ $r->label() }}</option>
            @endforeach
        </select>
        <button wire:click="openCreate" class="btn-primary ml-auto text-sm">+ Tambah Pengguna</button>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 ">
                <thead>
                    <tr>
                        <th class="table-th">Nama</th>
                        <th class="table-th">Email</th>
                        <th class="table-th">Role</th>
                        <th class="table-th text-right">Poin</th>
                        <th class="table-th text-right">Transaksi</th>
                        <th class="table-th">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100  bg-white ">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50  transition-colors">
                        <td class="table-td font-medium text-gray-900 ">{{ $user->nama }}</td>
                        <td class="table-td text-gray-500  text-xs">{{ $user->email }}</td>
                        <td class="table-td">
                            <span class="{{ match($user->role) { \App\Enums\UserRole::Admin => "badge-red", \App\Enums\UserRole::Petugas => "badge-yellow", default => "badge-gray" } }}">
                                {{ $user->role->label() }}
                            </span>
                        </td>
                        <td class="table-td text-right font-semibold text-gray-900 ">{{ number_format($user->saldo_poin) }}</td>
                        <td class="table-td text-right text-gray-500 ">{{ $user->transactions_count }}</td>
                        <td class="table-td">
                            <div class="flex gap-3">
                                <button wire:click="openEdit({{ $user->id }})" class="text-xs text-blue-600  hover:underline font-medium">Edit</button>
                                @if($user->id !== auth()->id())
                                <button wire:click="delete({{ $user->id }})" wire:confirm="Hapus pengguna ini?" class="text-xs text-red-500  hover:underline font-medium">Hapus</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="table-td text-center text-gray-400  py-10">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="px-4 py-3 border-t border-gray-100  bg-white ">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white  rounded-xl shadow-2xl ring-1 ring-gray-200  w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 ">
                <h3 class="font-semibold text-gray-800 ">{{ $editingId ? "Edit Pengguna" : "Tambah Pengguna" }}</h3>
                <button wire:click="$set(''showModal'', false)" class="text-gray-400 hover:text-gray-600  text-xl leading-none">&#10005;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="form-label">Nama <span class="text-red-500">*</span></label>
                    <input wire:model="nama" class="form-input" />
                    @error("nama") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <input wire:model="email" type="email" class="form-input" />
                    @error("email") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Password {{ $editingId ? "(kosongkan jika tidak diubah)" : "*" }}</label>
                    <input wire:model="password" type="password" class="form-input" />
                    @error("password") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Role <span class="text-red-500">*</span></label>
                    <select wire:model="role" class="form-input">
                        @foreach($roles as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">UID Kartu RFID</label>
                    <input wire:model="uidRfid" class="form-input font-mono" placeholder="A1:9F:22:0B" />
                    @error("uidRfid") <p class="text-xs text-red-500  mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3 justify-end px-6 py-4 border-t border-gray-100 ">
                <button wire:click="$set(''showModal'', false)" class="btn-secondary text-sm">Batal</button>
                <button wire:click="save" class="btn-primary text-sm">Simpan</button>
            </div>
        </div>
    </div>
    @endif
</div>

