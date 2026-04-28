<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create enums
        DB::statement("CREATE TYPE booking_status AS ENUM ('pending', 'confirmed', 'cancelled')");
        DB::statement("CREATE TYPE occasion_type AS ENUM ('birthday', 'anniversary', 'honeymoon', 'proposal', 'other')");
        DB::statement("CREATE TYPE payment_status AS ENUM ('pending', 'succeeded', 'failed', 'refunded')");

        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('role')->default('admin');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
        });

        // Boats
        Schema::create('boats', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('capacity')->default(10);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
        });

        // Time Slots
        Schema::create('time_slots', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('boat_id');
            $table->string('day')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_capacity')->default(10);
            $table->boolean('is_blocked')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreign('boat_id')->references('id')->on('boats')->cascadeOnDelete();
        });

        // Bookings
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('booking_ref')->unique();
            $table->date('tour_date');
            $table->uuid('time_slot_id');
            $table->string('status')->default('pending');
            $table->integer('photo_upgrade_count')->default(0);
            $table->string('special_occasion')->nullable();
            $table->text('special_comment')->nullable();
            $table->integer('total_price_cents')->default(0);
            $table->integer('total_guests')->default(1);
            $table->boolean('is_confirmed')->default(false);
            $table->boolean('needs_confirmation')->default(false);
            $table->integer('fees_cents')->default(0);
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->cascadeOnDelete();
        });

        // Booking Guests
        Schema::create('booking_guests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('booking_id');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('email');
            $table->text('phone')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });

        // Booking Items
        Schema::create('booking_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('booking_id');
            $table->string('ticket_type')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_cents')->default(0);
            $table->timestampsTz();
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('booking_id');
            $table->text('stripe_intent_id');
            $table->integer('amount_cents')->default(0);
            $table->string('status')->default('pending');
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });

        // Email Logs
        Schema::create('email_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('booking_id')->nullable();
            $table->string('type');
            $table->string('recipient');
            $table->string('status')->default('sent');
            $table->string('provider_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('sent_at')->default(DB::raw('now()'));
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('booking_guests');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('boats');
        Schema::dropIfExists('users');
        DB::statement("DROP TYPE IF EXISTS payment_status");
        DB::statement("DROP TYPE IF EXISTS occasion_type");
        DB::statement("DROP TYPE IF EXISTS booking_status");
    }
};
