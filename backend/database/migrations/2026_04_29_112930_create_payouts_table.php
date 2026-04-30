<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('amount_cents')->comment('Total payout amount in cents');
            $table->string('status')->default('pending')
                ->comment('pending = super admin initiated, confirmed = admin confirmed funds received');
            $table->foreignUuid('initiated_by')->constrained('users');
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
