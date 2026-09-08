<?php

namespace App\Livewire\Dashboard;

use App\Models\Machine;
use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;

class StatsOverview extends Component
{
    public int $totalBotolHariIni = 0;
    public int $totalPoinHariIni  = 0;
    public int $nodeAktif         = 0;
    public int $nodeKritis        = 0; // >= threshold
    public int $totalBotolAll     = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    #[On("echo-private:admin-dashboard,.TransactionCreated")]
    public function refresh(): void
    {
        $this->totalBotolHariIni = Transaction::whereDate("created_at", today())->sum("jumlah_botol");
        $this->totalPoinHariIni  = Transaction::whereDate("created_at", today())->sum("poin_diperoleh");
        $this->nodeAktif         = Machine::where("status_online", true)->count();
        $this->nodeKritis        = Machine::aboveThreshold()->count();
        $this->totalBotolAll     = Transaction::sum("jumlah_botol");
    }

    public function render()
    {
        return view("livewire.dashboard.stats-overview");
    }
}
