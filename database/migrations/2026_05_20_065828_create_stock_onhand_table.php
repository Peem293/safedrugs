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
        Schema::create('stock_onhand', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')
                  ->constrained('obats')
                  ->onDelete('cascade');
            $table->string('batch_no');
            $table->date('exp_date');
            $table->integer('stock_on_hand')->default(0);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
            $table->index(['obat_id', 'batch_no']);
            $table->index('exp_date'); // Mempercepat pencarian obat yang hampir kedaluwarsa (FEFO)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_onhand');
    }
};
