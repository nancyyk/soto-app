<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('jumlah_botol');
            $table->unsignedInteger('poin_diperoleh');
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — append-only log
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
