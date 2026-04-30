<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("booking_addons", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("booking_id");
            $table->uuid("addon_id");
            $table->integer("quantity")->default(1);
            $table->integer("unit_price_cents");
            $table->timestamps();

            $table->foreign("booking_id")->references("id")->on("bookings")->cascadeOnDelete();
            $table->foreign("addon_id")->references("id")->on("addons")->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("booking_addons");
    }
};
