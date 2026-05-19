<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediksi extends Model
{
    protected $table = 'prediksi';

    protected $fillable = [
        'obat_id',
        'user_id',
        'bulan_tahun_prediksi',
        'nilai_a',
        'nilai_b',
        'hasil_prediksi',
        'nilai_mape',
        'kategori_mape',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
