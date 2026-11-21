<?php

namespace App\Imports;

use App\Models\AnneeAcademique;
use App\Models\AttrEcueUe;
use App\Models\AttrUeClasse;
use App\Models\Ecue;
use App\Models\SemestreEtude;
use App\Models\Ue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AttrEcueUeImport implements ToModel,WithStartRow,WithCustomCsvSettings
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
        $row[0]=AnneeAcademique::where('ANNEE_ACAD',$row[0])->first()->id_Annee_Acad;
        $data=[
            'Id_Attr_ue_Sem'=>AttrUeClasse::where('id_Annee_Acad',$row[0])
                                          ->where('id_S_Gr_Spe',SemestreEtude::where('Nom_Classe',$row[1])->first()->id_S_Gr_Spe)
                                          ->where('codeUe',$row[2])->first()->Id_Attr_ue_Sem,
            'codeEcu'=>$row[3],
            'Seuil_Ecu'=>$row[4],
            'Ponderation'=>$row[5],
            'Eva_Mode'=>$row[6] ?? 'CC+ET',
            'PoidsDev'=>$row[7],
            'PoidsPart'=>$row[8],
            'Observation'=>$row[9],
            'numeroProf'=>$row[10],
            'MasseHoraire'=>$row[11],
            'MasseHoraireExec'=>$row[12],
            'pedagogique'=>$row[13],
            'Auteur'=>Auth::user()->login
        ];
        return new AttrEcueUe($data);
    }
    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'WINDOWS-1254'
        ];
    }
}
