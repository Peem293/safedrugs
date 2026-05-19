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
        Schema::create('rekap_pemakaian_bulanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obats')->onDelete('cascade');
            $table->string('bulan_tahun', 7);
            $table->integer('tahun');
            $table->integer('bulan');
            $table->decimal('total_jumlah', 15, 2);
            $table->timestamps();
            $table->unique(['obat_id', 'bulan_tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_pemakaian_bulanan');
    }
};
