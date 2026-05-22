<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('obats', function (Blueprint $table) {
            $table->integer('buffer_stock')->default(0)->after('stock');
            $table->integer('min_stock')->default(0)->after('buffer_stock');
            $table->date('last_buffer_calculated_at')->nullable()->after('satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obats', function (Blueprint $table) {
            // Menghapus kembali ketiga kolom baru saat melakukan rollback migration
            $table->dropColumn([
                'buffer_stock',
                'min_stock',
                'last_buffer_calculated_at'
            ]);
        });
    }
};
