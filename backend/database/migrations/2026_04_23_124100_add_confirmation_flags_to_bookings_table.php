<?php

use Illuminate\Database\Migrations\Migration;

// Columns now included in 2026_04_22_000001_create_base_tables.php
return new class extends Migration
{
    public function up(): void
    {
        // No-op: is_confirmed and needs_confirmation already in base table
    }

    public function down(): void
    {
        // No-op
    }
};
