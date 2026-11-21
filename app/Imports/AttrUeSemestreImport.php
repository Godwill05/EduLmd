<?php

namespace App\Imports;

use App\Models\AnneeAcademique;
use App\Models\AttrUeClasse;
use App\Models\CategorieUe;
use App\Models\SemestreEtude;
use App\Models\Ue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AttrUeSemestreImport implements ToModel,WithStartRow,WithCustomCsvSettings
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
        return new AttrUeClasse([
            'id_Annee_Acad'=>AnneeAcademique::where('ANNEE_ACAD',$row[0])->first()->id_Annee_Acad,
            'id_S_Gr_Spe'=>SemestreEtude::where('Nom_Classe',$row[1])->first()->id_S_Gr_Spe,
            'codeUe'=>$row[2],
            'Id_Cat_Ue'=>$row[3],
            'idImportance'=>$row[4],
            'Seuil_Ue'=>$row[5],
            'Credit'=>$row[6],
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
