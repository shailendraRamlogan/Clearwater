<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('booking_agent_id')->nullable()->constrained('booking_agents')->nullOnDelete();
            $table->integer('commission_cents')->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->string('sales_rep_name')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('booking_agent_id')->nullable()->constrained('booking_agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_agent_id');
            $table->dropColumn(['commission_cents', 'commission_percent', 'sales_rep_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_agent_id');
        });
    }
};
