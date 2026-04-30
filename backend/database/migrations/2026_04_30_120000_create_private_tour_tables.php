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
        DB::statement("CREATE TYPE private_tour_status AS ENUM ('requested', 'confirmed', 'rejected', 'awaiting_payment', 'completed')");
        DB::statement("CREATE TYPE time_preference AS ENUM ('morning', 'afternoon')");

        // Private Tour Requests
        Schema::create('private_tour_requests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('booking_ref')->unique();
            $table->string('status')->default('requested');
            $table->text('contact_first_name');
            $table->text('contact_last_name');
            $table->text('contact_email');
            $table->text('contact_phone');
            $table->integer('adult_count')->default(0);
            $table->integer('child_count')->default(0);
            $table->integer('infant_count')->default(0);
            $table->boolean('has_occasion')->default(false);
            $table->text('occasion_details')->nullable();
            $table->text('admin_notes')->nullable();
            $table->date('confirmed_tour_date')->nullable();
            $table->uuid('confirmed_time_slot_id')->nullable();
            $table->integer('total_price_cents')->default(0);
            $table->integer('fees_cents')->default(0);
            $table->text('stripe_intent_id')->nullable();
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
            $table->foreign('confirmed_time_slot_id')->references('id')->on('time_slots')->nullOnDelete();
        });

        // Private Tour Preferred Dates
        Schema::create('private_tour_preferred_dates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('private_tour_request_id');
            $table->date('date');
            $table->string('time_preference')->default('morning');
            $table->integer('sort_order')->default(0);
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
            $table->foreign('private_tour_request_id')->references('id')->on('private_tour_requests')->cascadeOnDelete();
        });

        // Private Tour Guests
        Schema::create('private_tour_guests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('private_tour_request_id');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));
            $table->foreign('private_tour_request_id')->references('id')->on('private_tour_requests')->cascadeOnDelete();
        });

        // Add source_type to bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('source_type')->default('regular')->after('booking_ref');
        });

        // Add private_price_cents and available_for to addons
        Schema::table('addons', function (Blueprint $table) {
            $table->integer('private_price_cents')->nullable()->after('price_cents');
            $table->string('available_for')->default('regular')->after('private_price_cents');
        });
    }

    public function down(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            $table->dropColumn(['private_price_cents', 'available_for']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });

        Schema::dropIfExists('private_tour_guests');
        Schema::dropIfExists('private_tour_preferred_dates');
        Schema::dropIfExists('private_tour_requests');

        DB::statement("DROP TYPE IF EXISTS time_preference");
        DB::statement("DROP TYPE IF EXISTS private_tour_status");
    }
};
