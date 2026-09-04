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
        Schema::create('deposits', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('user_uid');
            $table->string('machine_uid');
            $table->integer('bottle_count');
            $table->integer('points');
            $table->timestamps();

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('machine_uid')
                ->references('uid')
                ->on('machines')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
