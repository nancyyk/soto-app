<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('urutan'); // visit order: 0=depot, 1,2,3...
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
