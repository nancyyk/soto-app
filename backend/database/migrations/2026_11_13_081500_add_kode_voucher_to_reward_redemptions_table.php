<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {
            if (!Schema::hasColumn('reward_redemptions', 'kode_voucher')) {
                $table->string('kode_voucher', 100)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {
            if (Schema::hasColumn('reward_redemptions', 'kode_voucher')) {
                $table->dropColumn('kode_voucher');
            }
        });
    }
};
