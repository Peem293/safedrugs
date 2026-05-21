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

    protected static function booted(): void
    {
        // 1. Memicu kalkulasi ulang setelah data batch sukses dihapus (Single Delete)
        static::deleted(function (StockOnhand $stockOnhand) {
            static::hitungUlangStokMaster($stockOnhand->obat_id);
        });

        // 2. Memicu kalkulasi ulang setelah terjadi perubahan angka lewat Form Edit manual (Opsional & Mengamankan)
        static::saved(function (StockOnhand $stockOnhand) {
            static::hitungUlangStokMaster($stockOnhand->obat_id);
        });
    }

    /**
     * Fungsi pembantu untuk menyinkronkan total kuantitas ke tabel master obat
     */
    public static function hitungUlangStokMaster(int $obatId): void
    {
        $obat = Obat::find($obatId);

        if ($obat) {
            // Hitung total dari seluruh batch yang tersisa di database untuk obat_id ini
            $totalStokSekarang = static::where('obat_id', $obatId)->sum('stock_on_hand');

            // Update ke tabel obats
            $obat->update([
                'stock' => $totalStokSekarang
            ]);
        }
    }

    /**
     *
     * @return BelongsTo
     */
    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id', 'id');
    }
}
