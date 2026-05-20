<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOnhand extends Model
{
    use HasFactory;

    /**
     * Mendefinisikan nama tabel secara eksplisit karena menggunakan format snake_case
     * dan tidak menggunakan akhiran jamak 's' (plural).
     *
     * @var string
     */
    protected $table = 'stock_onhand';

    /**
     * Kolom-kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     * SANGAT berguna saat kita melakukan insert/update berulang dari hasil scraping.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'obat_id',
        'batch_no',
        'exp_date',
        'stock_on_hand',
        'last_scraped_at',
    ];

    /**
     * Mengubah tipe data kolom database ke tipe data asli PHP secara otomatis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exp_date' => 'date',
        'stock_on_hand' => 'integer',
        'last_scraped_at' => 'datetime',
    ];

    /**
     * Relasi Balikan (Inverse Relationship): Setiap satu baris stok batch
     * pasti dimiliki oleh satu data Obat induk.
     * * Di aplikasi/view, Anda bisa memanggilnya dengan cara: $stok->obat->nama_obat
     *
     * @return BelongsTo
     */
    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id', 'id');
    }
}
