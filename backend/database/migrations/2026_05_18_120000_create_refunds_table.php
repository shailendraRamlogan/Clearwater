<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('booking_id');
            $table->uuid('payment_id');
            $table->string('stripe_refund_id')->nullable();
            $table->integer('amount_cents');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, processed, failed
            $table->string('initiated_by')->nullable();
            $table->timestampsTz();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
