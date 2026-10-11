<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            // Polyline coords [[lat,lng],...] dari OSRM route API
            $table->json('route_geometry')->nullable()->after('status');
            // 'osrm' atau 'haversine' — untuk info transparansi sumber jarak
            $table->string('distance_source', 20)->default('haversine')->after('route_geometry');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['route_geometry', 'distance_source']);
        });
    }
};
