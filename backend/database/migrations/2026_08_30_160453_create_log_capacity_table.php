<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_capacity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('persen_kapasitas');
            $table->decimal('tegangan_baterai', 4, 2)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            // No updated_at — time-series append-only
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_capacity');
    }
};
