<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_distance_km', 6, 2)->default(0);
            $table->unsignedSmallInteger('total_duration_min')->default(0);
            $table->string('status', 20)->default('pending')->index(); // pending|selesai
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
