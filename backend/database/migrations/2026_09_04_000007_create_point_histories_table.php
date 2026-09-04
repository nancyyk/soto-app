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
        Schema::create('point_histories', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('user_uid');
            $table->integer('points');
            $table->string('type');
            $table->text('description')->nullable();
            $table->string('reference_uid')->nullable();
            $table->timestamps();

            $table->foreign('user_uid')
                ->references('uid')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_histories');
    }
};
