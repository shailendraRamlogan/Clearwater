<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignUuid('rebooked_from_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestampTz('rebooked_at')->nullable();
            $table->foreignUuid('rebooked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('rebook_fee_cents')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rebooked_from_booking_id');
            $table->dropConstrainedForeignId('rebooked_by');
            $table->dropColumn(['rebooked_at', 'rebook_fee_cents']);
        });
    }
};
