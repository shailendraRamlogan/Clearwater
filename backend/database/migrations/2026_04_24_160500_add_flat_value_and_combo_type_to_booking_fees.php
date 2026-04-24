<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_fees', function (Blueprint $table) {
            $table->decimal('flat_value', 10, 2)->default(0)->after('value');
        });

        // Update enum to include 'both' type
        DB::statement("ALTER TABLE booking_fees ALTER COLUMN type TYPE VARCHAR(20)");
        // No need to add a real enum constraint — varchar with app-level validation is safer
    }

    public function down(): void
    {
        Schema::table('booking_fees', function (Blueprint $table) {
            $table->dropColumn('flat_value');
        });

        DB::statement("ALTER TABLE booking_fees ALTER COLUMN type TYPE VARCHAR(20)");
    }
};
