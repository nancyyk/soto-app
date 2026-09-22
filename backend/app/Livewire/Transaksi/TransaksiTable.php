<?php

namespace App\Livewire\Transaksi;

use App\Models\Machine;
use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TransaksiTable extends Component
{
    use WithPagination;

    public string $filterMachine = '';

    public string $filterDate = '';

    public int $perPage = 20;

    protected $queryString = ['filterMachine', 'filterDate'];

    public function updatingFilterMachine(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDate(): void
    {
        $this->resetPage();
    }

    #[On('echo-private:admin-dashboard,.TransactionCreated')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Transaction::with(['user:id,nama', 'machine:id,nama_lokasi'])
            ->latest('created_at');

        if ($this->filterMachine) {
            $query->where('machine_id', $this->filterMachine);
        }
        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        return view('livewire.transaksi.transaksi-table', [
            'transactions' => $query->paginate($this->perPage),
            'machines' => Machine::orderBy('nama_lokasi')->get(['id', 'nama_lokasi']),
        ]);
    }
}
