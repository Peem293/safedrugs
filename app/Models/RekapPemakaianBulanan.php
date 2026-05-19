<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapPemakaianBulanan extends Model
{
    protected $table = 'rekap_pemakaian_bulanan';
    protected $fillable = [
        'obat_id',
        'bulan_tahun',
        'tahun',
        'bulan',
        'total_jumlah',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class);
    }
}
