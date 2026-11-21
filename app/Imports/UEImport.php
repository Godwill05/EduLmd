<?php

namespace App\Imports;

use App\Models\Ue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class UEImport implements ToModel,WithStartRow,WithCustomCsvSettings
{
    public function startRow(): int
    {
        return 2;
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Ue([
            'codeUe'=>$row[0],
            'libelleUe'=>$row[1],
            'Auteur'=>Auth::user()->login
        ]);
    }
    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'WINDOWS-1254'
        ];
    }
}
