<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->uuid('time_slot_id')->nullable()->change();
        });

        DB::table('bookings')
            ->where('source_type', 'private')
            ->whereNotNull('time_slot_id')
            ->update(['time_slot_id' => null]);
    }

    public function down(): void
    {
        $hasPrivateWithoutSlot = DB::table('bookings')
            ->where('source_type', 'private')
            ->whereNull('time_slot_id')
            ->exists();

        if (!$hasPrivateWithoutSlot) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->uuid('time_slot_id')->nullable(false)->change();
            });
        }
    }
};
