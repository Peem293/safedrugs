<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pemakaian extends Model
{
    protected $fillable = [
        'obat_id',
        'tanggal',
        'jumlah',
        'satuan'
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class);
    }

    // protected static function booted()
    // {
    //     // ✅ CREATE → kurangi stok
    //     static::created(function ($pemakaian) {
    //         if ($pemakaian->obat) {
    //             $pemakaian->obat->decrement('stock', $pemakaian->jumlah);
    //         }
    //     });

    //     // ✅ UPDATE → sesuaikan stok (handle semua kasus)
    //     static::updating(function ($pemakaian) {
    //         $originalJumlah = $pemakaian->getOriginal('jumlah');
    //         $originalObatId = $pemakaian->getOriginal('obat_id');

    //         // 🔹 Jika obat diganti
    //         if ($originalObatId != $pemakaian->obat_id) {
    //             $oldObat = Obat::find($originalObatId);
    //             $newObat = Obat::find($pemakaian->obat_id);

    //             if ($oldObat) {
    //                 $oldObat->increment('stock', $originalJumlah);
    //             }

    //             if ($newObat) {
    //                 $newObat->decrement('stock', $pemakaian->jumlah);
    //             }
    //         } else {
    //             // 🔹 Jika hanya jumlah berubah
    //             $selisih = $pemakaian->jumlah - $originalJumlah;

    //             if ($pemakaian->obat) {
    //                 if ($selisih > 0) {
    //                     $pemakaian->obat->decrement('stock', $selisih);
    //                 } elseif ($selisih < 0) {
    //                     $pemakaian->obat->increment('stock', abs($selisih));
    //                 }
    //             }
    //         }
    //     });

    //     // ✅ DELETE → kembalikan stok
    //     static::deleting(function ($pemakaian) {
    //         if ($pemakaian->obat) {
    //             $pemakaian->obat->increment('stock', $pemakaian->jumlah);
    //         }
    //     });
    // }
}