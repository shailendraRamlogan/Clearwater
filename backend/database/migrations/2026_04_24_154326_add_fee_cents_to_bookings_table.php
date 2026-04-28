<?php

use Illuminate\Database\Migrations\Migration;

// Column now included in 2026_04_22_000001_create_base_tables.php
return new class extends Migration
{
    public function up(): void
    {
        // No-op: fees_cents already in base table
    }

    public function down(): void
    {
        // No-op
    }
};
