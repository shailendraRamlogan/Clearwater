<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_tour_requests', function (Blueprint $table) {
            $table->time('confirmed_start_time')->nullable()->after('confirmed_tour_date');
            $table->time('confirmed_end_time')->nullable()->after('confirmed_start_time');
        });

        // Drop the FK and column if it exists
        if (Schema::hasColumn('private_tour_requests', 'confirmed_time_slot_id')) {
            Schema::table('private_tour_requests', function (Blueprint $table) {
                $table->dropForeign(['confirmed_time_slot_id']);
                $table->dropColumn('confirmed_time_slot_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('private_tour_requests', function (Blueprint $table) {
            $table->uuid('confirmed_time_slot_id')->nullable()->after('confirmed_tour_date');
            $table->foreign('confirmed_time_slot_id')->references('id')->on('time_slots')->nullOnDelete();
            $table->dropColumn(['confirmed_start_time', 'confirmed_end_time']);
        });
    }
};
