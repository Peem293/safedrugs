<?php

namespace App\Models;

use App\Models\StockOnhand;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_obat',
        'nama_obat',
        'stock',
        'buffer_stock',              // Kolom baru untuk nilai safety stock
        'min_stock',                 // Kolom baru untuk batas Reorder Point (kritis)
        'satuan',
        'last_buffer_calculated_at',
    ];

    /**
     * Otomatis mengubah string database menjadi Carbon Instance
     */
    protected $casts = [
        'last_buffer_calculated_at' => 'datetime',
    ];

    public function pemakaian(): HasMany
    {
        return $this->hasMany(Pemakaian::class);
    }

    public function prediksi(): HasMany
    {
        return $this->hasMany(Prediksi::class, 'obat_id');
    }

    /**
     * Cukup gunakan relasi ini untuk mengambil seluruh data rekap bulanan obat
     */
    public function rekapBulanans(): HasMany
    {
        return $this->hasMany(RekapPemakaianBulanan::class, 'obat_id');
    }

    public function stockOnhands(): HasMany
    {
        return $this->hasMany(StockOnhand::class, 'obat_id', 'id');
    }
}
