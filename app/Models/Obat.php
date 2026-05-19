<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    protected $fillable = [
        'kode_obat',
        'nama_obat',
        'stock',
        'satuan'
    ];

    public function pemakaian()
    {
        return $this->hasMany(Pemakaian::class);
    }

    public function prediksi()
    {
        return $this->hasMany(Prediksi::class, 'obat_id');
    }

    public function rekapBulanans()
    {
        return $this->hasMany(RekapPemakaianBulanan::class, 'obat_id');
    }
}
