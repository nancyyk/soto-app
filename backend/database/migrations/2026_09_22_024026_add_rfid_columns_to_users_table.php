<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'rfid_uid')) {
                $table->string('rfid_uid', 50)->nullable()->unique()->after('email');
                $table->timestamp('rfid_registered_at')->nullable()->after('rfid_uid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rfid_uid', 'rfid_registered_at']);
        });
    }
};