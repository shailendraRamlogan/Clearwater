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
            $table->uuid('payment_id')->nullable();
            $table->integer('amount_cents')->default(0);
            $table->integer('fees_deducted_cents')->default(0);
            $table->string('type')->default('partial'); // full, partial
            $table->string('status')->default('pending'); // pending, approved, processing, completed, rejected, agent_handled
            $table->string('reason')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->uuid('initiated_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->foreign('initiated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['booking_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
