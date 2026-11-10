<?php

namespace App\Console\Commands;

use App\Models\Machine;
use Illuminate\Console\Command;

class MarkStaleMachinesOffline extends Command
{
    protected $signature = 'machines:mark-offline';

    protected $description = 'Set status_online=false untuk mesin yang tidak mengirim telemetri (heartbeat) lagi';

    public function handle(): int
    {
        $count = Machine::stale()->update(['status_online' => false]);
        $this->info("{$count} mesin ditandai offline.");

        return self::SUCCESS;
    }
}
