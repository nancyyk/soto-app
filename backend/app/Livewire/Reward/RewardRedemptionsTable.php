<?php

namespace App\Livewire\Reward;

use App\Models\RewardRedemption;
use Livewire\Component;
use Livewire\WithPagination;

class RewardRedemptionsTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    // Modal state for editing redemption status
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $status = 'Proses';
    public ?string $ekspedisi = '';
    public ?string $nomor_resi = '';

    // Selected redemption info for modal display
    public ?string $selectedUserName = '';
    public ?string $selectedRewardName = '';
    public ?string $selectedRewardCategory = '';
    public int $selectedPointsUsed = 0;
    public ?string $selectedDate = '';
    public ?string $selectedShippingAddress = '';
    public ?string $selectedRecipientNumber = '';

    protected $queryString = ['search', 'filterStatus'];

    public function updatingSearch(): void
    {
        $this->resetPage('redemptionsPage');
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage('redemptionsPage');
    }

    public function openEdit(int $id): void
    {
        $redemption = RewardRedemption::with(['user', 'reward'])->findOrFail($id);
        $this->editingId = $redemption->id;
        $this->status = $redemption->status ?? 'Proses';
        $this->ekspedisi = $redemption->ekspedisi ?? '';
        $this->nomor_resi = $redemption->nomor_resi ?? '';
        $this->selectedUserName = $redemption->user?->nama ?? 'Pengguna #' . $redemption->user_id;
        $this->selectedRewardName = ($redemption->reward?->kode ? $redemption->reward->kode . ' • ' : '') . ($redemption->reward?->nama ?? 'Reward #' . $redemption->reward_id);
        $this->selectedPointsUsed = (int) $redemption->poin;
        $this->selectedRewardCategory = $redemption->reward?->kategori;
        $this->selectedShippingAddress = $redemption->shipping_address ?: null;
        $this->selectedRecipientNumber = $redemption->recipient_number ?: null;
        if ($this->selectedRewardCategory !== 'Merchandise' && $this->status === 'Pengiriman') {
            $this->status = 'Proses';
        }
        $this->selectedDate = $redemption->created_at ? $redemption->created_at->format('d M Y, H:i') : '-';
        
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openUpdateModal(int $id): void
    {
        $this->openEdit($id);
    }

    public function save(): void
    {
        if ($this->editingId === null) {
            throw new \LogicException('Pilih penukaran yang akan diperbarui.');
        }

        // Decide shipping rules from the persisted reward category, not client state.
        $redemption = RewardRedemption::with('reward')->findOrFail($this->editingId);
        $isPhysicalReward = $redemption->reward?->kategori === 'Merchandise';
        $rules = ['status' => $isPhysicalReward
            ? 'required|in:Proses,Pengiriman,Selesai'
            : 'required|in:Proses,Selesai'];

        if ($isPhysicalReward && $this->status === 'Pengiriman') {
            $rules['ekspedisi'] = 'required|in:POS,J&T';
            $rules['nomor_resi'] = 'required|string|max:100';
        } elseif ($isPhysicalReward) {
            $rules['ekspedisi'] = 'nullable|string|max:50';
            $rules['nomor_resi'] = 'nullable|string|max:100';
        }

        $messages = [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus berupa Proses, Pengiriman, atau Selesai.',
            'ekspedisi.required' => 'Ekspedisi wajib dipilih saat status Pengiriman.',
            'ekspedisi.in' => 'Pilih ekspedisi POS atau J&T.',
            'nomor_resi.required' => 'Nomor resi wajib diisi saat status Pengiriman.',
        ];

        $this->validate($rules, $messages);

        $data = ['status' => $this->status];

        if (! $isPhysicalReward) {
            // Digital rewards never use shipping, including stale legacy values.
            $data['ekspedisi'] = null;
            $data['nomor_resi'] = null;
        } elseif ($this->status === 'Pengiriman') {
            $data['ekspedisi'] = $this->ekspedisi;
            $data['nomor_resi'] = $this->nomor_resi;
        } elseif ($this->status === 'Proses') {
            $data['ekspedisi'] = $this->ekspedisi ?: null;
            $data['nomor_resi'] = $this->nomor_resi ?: null;
        }

        // When completed, only update status so saved shipment details remain intact.
        $redemption->update($data);

        if ($redemption->user) {
            $redemption->user->notify(new \App\Notifications\RedemptionStatusUpdatedNotification($redemption->fresh()));
        }

        session()->flash('success', 'Status penukaran #' . $redemption->id . ' berhasil diperbarui.');

        $this->closeModal();
    }

    public function updateStatus(): void
    {
        $this->save();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editingId', 'status', 'ekspedisi', 'nomor_resi', 'selectedUserName', 'selectedRewardName', 'selectedRewardCategory', 'selectedPointsUsed', 'selectedDate', 'selectedShippingAddress', 'selectedRecipientNumber']);
        $this->status = 'Proses';
        $this->resetValidation();
    }

    public function render()
    {
        $redemptions = RewardRedemption::with([
            'user:id,nama,email',
            'reward:id,kode,nama,kategori,gambar',
        ])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('user', fn ($u) => $u->where('nama', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                        ->orWhereHas('reward', fn ($r) => $r->where('nama', 'like', "%{$this->search}%")->orWhere('kode', 'like', "%{$this->search}%"))
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('ekspedisi', 'like', "%{$this->search}%")
                        ->orWhere('nomor_resi', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'redemptionsPage');

        return view('livewire.reward.reward-redemptions-table', [
            'redemptions' => $redemptions,
        ]);
    }
}
