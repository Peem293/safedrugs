<?php

namespace App\Imports;

use App\Models\Obat;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ObatImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
        public function model(array $row)
    {
        return new Obat([
            'kode_obat' => $row['item_code'] ?? null,
            'nama_obat' => $row['item_name'] ?? null,
            'stock'     => $this->transformStock($row['on_hand_stock'] ?? 0),
            'satuan'    => $row['unit'] ?? '-',
        ]);
    }

    public function rules(): array
    {
        return [
            '*.item_code' => 'required',
            '*.item_name' => 'required',
            '*.on_hand_stock' => 'required',
            '*.unit' => 'required',
        ];
    }

    private function transformStock($value)
    {
        if (is_numeric($value)) return $value;

        // Menghilangkan titik (ribuan) dan mengubah koma menjadi titik (desimal)
        $clean = str_replace('.', '', $value);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }
}
