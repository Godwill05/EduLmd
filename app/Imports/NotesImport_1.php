<?php

namespace App\Imports;

use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class NotesImport implements ToModel,WithStartRow,WithUpserts,WithCustomCsvSettings
{
    public $id_eval;
    public function __construct($eva)
    {
        $this->id_eval=$eva;
    }

    public function uniqueBy() {
        return ['login','numeroEval','numeroInscription'];
    }

    public function model(array $row)
    {
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
