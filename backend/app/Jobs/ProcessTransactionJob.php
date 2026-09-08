<?php

namespace App\Jobs;

use App\Events\TransactionCreated;
use App\Models\CardRfid;
use App\Models\Machine;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTransactionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $uidRfid,
        public readonly int    $machineId,
        public readonly int    $jumlahBotol,
        public readonly string $timestamp,
    ) {}

    public function handle(): void
    {
        // Validate RFID card
        $card = CardRfid::where('uid_rfid', $this->uidRfid)
            ->where('status_aktif', true)
            ->with('user')
            ->first();

        if (!$card) {
            Log::warning('ProcessTransactionJob: unknown or inactive RFID', ['uid' => $this->uidRfid]);
            return;
        }

        $machine = Machine::find($this->machineId);
        if (!$machine) {
            Log::warning('ProcessTransactionJob: machine not found', ['machine_id' => $this->machineId]);
            return;
        }

        $pointsPerBottle = (int) Setting::get('poin_per_botol', config('soto.points_per_bottle', 10));
        $poinDiperoleh   = $this->jumlahBotol * $pointsPerBottle;

        DB::transaction(function () use ($card, $poinDiperoleh) {
            Transaction::create([
                'user_id'       => $card->user_id,
                'machine_id'    => $this->machineId,
                'jumlah_botol'  => $this->jumlahBotol,
                'poin_diperoleh'=> $poinDiperoleh,
                'created_at'    => $this->timestamp,
            ]);

            // Update denormalized saldo_poin
            $card->user->increment('saldo_poin', $poinDiperoleh);
        });

        event(new TransactionCreated(
            userId:        $card->user_id,
            machineId:     $this->machineId,
            jumlahBotol:   $this->jumlahBotol,
            poinDiperoleh: $poinDiperoleh,
            namaUser:      $card->user->nama,
            namaLokasi:    $machine->nama_lokasi,
        ));

        Log::info('Transaction processed', [
            'user_id' => $card->user_id,
            'poin'    => $poinDiperoleh,
        ]);
    }
}
