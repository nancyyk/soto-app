<?php

namespace App\Livewire\Pengguna;

use App\Enums\UserRole;
use App\Models\CardRfid;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class PenggunaTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterRole = '';

    // Modal state
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'user';

    public string $uidRfid = '';

    protected $queryString = ['search', 'filterRole'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'nama', 'email', 'password', 'role', 'uidRfid']);
        $this->role = 'user';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::with('cardsRfid')->findOrFail($id);
        $this->editingId = $id;
        $this->nama = $user->nama;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role->value;
        $this->uidRfid = $user->cardsRfid->first()?->uid_rfid ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'nama' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email'.($this->editingId ? ",{$this->editingId}" : ''),
            'role' => 'required|in:user,admin,petugas',
            'uidRfid' => 'nullable|string|max:50|unique:cards_rfid,uid_rfid'.($this->editingId ? ",NULL,id,user_id,{$this->editingId}" : ''),
        ];
        if (! $this->editingId) {
            $rules['password'] = 'required|min:8';
        }

        $this->validate($rules);

        $data = ['nama' => $this->nama, 'email' => $this->email, 'role' => $this->role];
        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        // Sync RFID card
        if ($this->uidRfid) {
            CardRfid::updateOrCreate(
                ['user_id' => $user->id],
                ['uid_rfid' => $this->uidRfid, 'status_aktif' => true]
            );
        }

        $this->showModal = false;
        session()->flash('success', $this->editingId ? 'Pengguna berhasil diperbarui.' : 'Pengguna berhasil ditambahkan.');
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
        session()->flash('success', 'Pengguna berhasil dihapus.');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q->where('nama', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->filterRole, fn ($q) => $q->where('role', $this->filterRole))
            ->withCount('transactions')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.pengguna.pengguna-table', ['users' => $users, 'roles' => UserRole::cases()]);
    }
}
