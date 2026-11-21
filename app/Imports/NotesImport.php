<?php

namespace App\Imports;

use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Note;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class NotesImport implements ToModel,WithStartRow,WithUpserts,WithCustomCsvSettings
{
    use Importable;
    public $id_eval;
    public $erreurs;
    public function __construct($eva)
    {
        $this->id_eval=$eva;
        $this->erreurs=[];
    }

    public function uniqueBy() {
        return ['login','numeroEval','numeroInscription'];
    }

    public function model(array $row)
    {
        if (!Etudiant::where('MATRICULE',$row[0])->exists() || !Inscription::where('MATRICULE',$row[0])->exists()) {
            array_push($this->erreurs,"L'étudiant de matricule $row[0] n'est pas inscrit.");
            return null;
        }
        if($row[3]!==NULL){
            return new Note([
                'numeroInscription'=>$row[0],
                'numeroEval'=>$this->id_eval,
                'Note'=>$row[3],
                'login'=>Auth::user()->login,
            ]);
        }elseif (Note::where('numeroInscription',$row[0])->where('numeroEval',$this->id_eval)->first()) {
            Note::where('numeroInscription',$row[0])->where('numeroEval',$this->id_eval)->delete();
        }
    }

    public function startRow(): int
    {
        return 2;
    }
    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'WINDOWS-1254'
        ];
    }
}
