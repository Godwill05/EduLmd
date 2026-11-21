<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NotesExport implements FromCollection,WithHeadings,ShouldAutoSize
{
    public $an,$fil,$eva,$ane;

    public function __construct($an,$fil,$eva)
    {
        $this->an = $an;
        $this->ane=\App\Models\AnneeAcademique::where('ANNEE_ACAD',$an)->first()->id_Annee_Acad;
        $this->fil = $fil;
        $this->eva = $eva;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $inscriptions=DB::select("SELECT inscriptions.MATRICULE as MATRICULE, nomEtu,prenomsEtu FROM etudiant,`inscriptions` WHERE etudiant.MATRICULE=inscriptions.MATRICULE and Id_Annee=" . $this->ane . " AND`ID_FILIERE`='" . $this->fil . "' ORDER BY nomEtu,prenomsEtu ASC");

        $data=[];
        foreach ($inscriptions as  $inscription) {
            $rcnote=DB::select("SELECT idObtenir,numeroInscription as MATRICULE, numeroEval,Note,loginupdate FROM obtenir WHERE obtenir.numeroInscription='" . $inscription->MATRICULE . "' and obtenir.numeroEval=" . $this->eva . "");
            array_push($data,[
                'matricule'=>$inscription->MATRICULE,
                'nom'=>$inscription->nomEtu,
                'prenom'=>$inscription->prenomsEtu,
                'note'=>$rcnote[0]->Note ?? ''
            ]);
        }
        return collect($data);
    }
    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prénom(s)',
            'Note obtenue',
        ];
    }
}
