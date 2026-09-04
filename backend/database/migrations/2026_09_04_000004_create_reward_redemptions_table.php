<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('user_uid');
            $table->string('reward_uid');
            $table->integer('points_used');
            $table->string('status');
            $table->timestamps();

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('reward_uid')
                ->references('uid')
                ->on('rewards')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
