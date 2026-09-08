<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lokasi', 150);
            $table->double('latitude');
            $table->double('longitude');
            $table->boolean('is_simulation')->default(false);
            $table->boolean('status_online')->default(false);
            $table->unsignedTinyInteger('kapasitas_terkini')->default(0); // 0-100
            $table->decimal('tegangan_baterai', 4, 2)->nullable();
            $table->unsignedTinyInteger('threshold_capacity')->default(80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
