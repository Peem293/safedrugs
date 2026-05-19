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
        Schema::create('prediksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('obats')->onDelete('cascade');
            $table->string('bulan_tahun_prediksi', 7);
            $table->decimal('nilai_a', 15, 4);
            $table->decimal('nilai_b', 15, 4);
            $table->decimal('hasil_prediksi', 15, 2);
            $table->decimal('nilai_mape', 8, 2)->nullable();
            $table->string('kategori_mape', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediksi');
    }
};
