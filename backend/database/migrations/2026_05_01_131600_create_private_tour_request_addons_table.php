<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_tour_request_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('private_tour_request_id');
            $table->uuid('addon_id');
            $table->integer('unit_price_cents')->nullable();
            $table->timestamps();

            $table->foreign('private_tour_request_id')->references('id')->on('private_tour_requests')->cascadeOnDelete();
            $table->foreign('addon_id')->references('id')->on('addons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_tour_request_addons');
    }
};
