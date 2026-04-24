<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE booking_fees DROP CONSTRAINT IF EXISTS booking_fees_type_check");
        DB::statement("ALTER TABLE booking_fees ALTER COLUMN type TYPE VARCHAR(20) USING type::varchar(20)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE booking_fees ALTER COLUMN type TYPE VARCHAR(20)");
    }
};
