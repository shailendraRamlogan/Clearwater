<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('transfer_name')->nullable()->after('status')
                ->comment('Identifier/name for this transfer');
            $table->string('receipt_image')->nullable()->after('notes')
                ->comment('Path to the receipt image uploaded during initiation');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['transfer_name', 'receipt_image']);
        });
    }
};
