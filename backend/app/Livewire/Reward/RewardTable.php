<?php

namespace App\Livewire\Reward;

use App\Models\Reward;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class RewardTable extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public string $filterKategori = '';
    public string $filterStatus = '';

    // Modal state for Add/Edit
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $kode = '';
    public string $nama = '';
    public ?string $deskripsi = '';
    public int $poin = 0;
    public int $stok = 0;
    public string $kategori = 'Voucher';
    public $gambar = null;
    public ?string $gambarLama = null;
    public bool $is_active = true;

    protected $queryString = ['search', 'filterKategori', 'filterStatus'];

    public function updatingSearch(): void
    {
        $this->resetPage('rewardsPage');
    }

    public function updatingFilterKategori(): void
    {
        $this->resetPage('rewardsPage');
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage('rewardsPage');
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'kode', 'nama', 'deskripsi', 'poin', 'stok', 'kategori', 'gambar', 'gambarLama']);
        $this->gambarLama = null;
        $this->is_active = true;
        $this->kategori = 'Voucher';
        
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openCreateModal(): void
    {
        $this->openCreate();
    }

    public function openEdit(int $id): void
    {
        $reward = Reward::findOrFail($id);
        $this->editingId = $reward->id;
        $this->kode = $reward->kode;
        $this->nama = $reward->nama;
        $this->deskripsi = $reward->deskripsi ?? '';
        $this->poin = (int) $reward->poin;
        $this->stok = (int) $reward->stok;
        $this->kategori = $reward->kategori ?? 'Voucher';
        $this->gambar = null;
        $this->gambarLama = $reward->gambar;
        $this->is_active = (bool) $reward->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->openEdit($id);
    }

    public function save(): void
    {
        $rules = [
            'kode' => 'required|string|max:50|unique:rewards,kode' . ($this->editingId ? ",{$this->editingId}" : ''),
            'nama' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'poin' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'kategori' => ['required', 'string', Rule::in(['Voucher', 'E-Wallet', 'Pulsa', 'Merchandise'])],
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
        ];

        $this->validate($rules);

        $data = [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi ?: null,
            'poin' => $this->poin,
            'stok' => $this->stok,
            'kategori' => $this->kategori,
            'is_active' => $this->is_active,
        ];

        $oldImagePath = $this->editingId ? Reward::findOrFail($this->editingId)->gambar : null;
        $newImagePath = null;

        if ($this->gambar) {
            $newImagePath = $this->gambar->store('rewards', 'public');
            if (! $newImagePath) {
                throw new \RuntimeException('Gambar reward gagal disimpan.');
            }
            $data['gambar'] = $newImagePath;
        } elseif ($this->editingId === null) {
            $data['gambar'] = null;
        }

        try {
            if ($this->editingId) {
                $reward = Reward::findOrFail($this->editingId);
                $oldStock = (int) $reward->stok;
                $reward->update($data);

                \App\Services\NotificationService::checkAndNotifyRewardStockTransition($reward->fresh(), $oldStock);
            } else {
                $reward = Reward::create($data);

                // Notify active normal users about new reward (Constraint 10)
                \App\Services\NotificationService::notifyNewRewardAvailable($reward);

                // Check initial low stock if created with low stock (Constraint 6)
                if ($reward->stok <= 2) {
                    \App\Services\NotificationService::checkAndNotifyRewardStockTransition($reward, 10);
                }
            }
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath && $oldImagePath && $oldImagePath !== $newImagePath && $this->isManagedImage($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $wasEditing = $this->editingId !== null;
        $this->closeModal();
        session()->flash('success', $wasEditing ? 'Reward berhasil diperbarui.' : 'Reward berhasil ditambahkan.');
    }

    public function update(): void
    {
        if ($this->editingId === null) {
            throw new \LogicException('Pilih reward yang akan diperbarui.');
        }

        $this->save();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editingId', 'kode', 'nama', 'deskripsi', 'poin', 'stok', 'kategori', 'gambar', 'gambarLama']);
        $this->is_active = true;
        $this->kategori = 'Voucher';
        $this->resetValidation();
    }

    public function removeSelectedImage(): void
    {
        $this->gambar = null;
        $this->resetValidation('gambar');
    }

    private function isManagedImage(string $path): bool
    {
        return str_starts_with($path, 'rewards/') && ! str_contains($path, '..');
    }

    public function toggleStatus(int $id): void
    {
        $reward = Reward::findOrFail($id);
        $reward->is_active = ! $reward->is_active;
        $reward->save();

        session()->flash('success', 'Status reward "' . $reward->nama . '" berhasil diubah.');
    }

    public function delete(int $id): void
    {
        $reward = Reward::findOrFail($id);
        $rewardNama = $reward->nama;
        $reward->delete();

        session()->flash('success', 'Reward "' . $rewardNama . '" berhasil dihapus.');
    }

    public function render()
    {
        $rewards = Reward::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('kode', 'like', "%{$this->search}%")
                        ->orWhere('deskripsi', 'like', "%{$this->search}%")
                        ->orWhere('kategori', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterKategori, fn ($q) => $q->where('kategori', $this->filterKategori))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', (bool) $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'rewardsPage');

        $categories = collect(['Voucher', 'E-Wallet', 'Pulsa', 'Merchandise']);

        return view('livewire.reward.reward-table', [
            'rewards' => $rewards,
            'categories' => $categories,
        ]);
    }
}
