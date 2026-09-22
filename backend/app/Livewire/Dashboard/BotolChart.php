<?php

namespace App\Livewire\Dashboard;

use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;

class BotolChart extends Component
{
    public array $chartData = [];

    public function mount(): void
    {
        $this->loadChart();
    }

    #[On('echo-private:admin-dashboard,.TransactionCreated')]
    public function loadChart(): void
    {
        $data = Transaction::query()
            ->selectRaw('DATE(created_at) as tanggal, SUM(jumlah_botol) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->pluck('total', 'tanggal');

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->locale('id')->isoFormat('D MMM');
            $values[] = (int) ($data[$date] ?? 0);
        }

        $this->chartData = ['labels' => $labels, 'values' => $values];
    }

    public function render()
    {
        return view('livewire.dashboard.botol-chart');
    }
}
