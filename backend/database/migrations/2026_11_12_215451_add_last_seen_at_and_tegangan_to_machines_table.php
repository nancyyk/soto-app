<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            // Cek dulu apakah kolom sudah ada, kalau belum tambahkan
            if (!Schema::hasColumn('machines', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('status_online');
            }
            if (!Schema::hasColumn('machines', 'tegangan_baterai')) {
                $table->decimal('tegangan_baterai', 5, 2)->nullable()->after('kapasitas_terkini');
            }
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            if (Schema::hasColumn('machines', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
            if (Schema::hasColumn('machines', 'tegangan_baterai')) {
                $table->dropColumn('tegangan_baterai');
            }
        });
    }
};