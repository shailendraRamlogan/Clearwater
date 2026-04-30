<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("addons", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("title");
            $table->text("description")->nullable();
            $table->integer("price_cents");
            $table->boolean("is_active")->default(true);
            $table->integer("sort_order")->default(0);
            $table->integer("max_quantity")->nullable();
            $table->string("icon_name")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("addons");
    }
};
