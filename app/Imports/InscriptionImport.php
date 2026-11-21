<?php

namespace App\Imports;

use App\Models\AnneeAcademique;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Nationalite;
use App\Models\Statut;
use App\Models\TypeEtudiant;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class InscriptionImport implements ToModel,WithStartRow,WithCustomCsvSettings
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
        \Carbon\Carbon::setLocale('fr');
        if(empty(Etudiant::find($row[0]))){
            $etudiant=new Etudiant();
            $etudiant->MATRICULE=$row[0];
            $etudiant->nomEtu=$row[1];
            $etudiant->prenomsEtu=$row[2];
            $etudiant->dateNaisEtu=\Carbon\Carbon::parse($row[3])->format('Y-m-d');
            $etudiant->lieuNaisEtu=$row[4];
            $etudiant->sexeEtu=$row[5];
            $etudiant->telEtu=$row[6];
            $etudiant->emailEtu=$row[7];
            $etudiant->Observation=$row[8];
            $etudiant->codeTypeEtu=TypeEtudiant::where('libTypeEtu',$row[9])->orWhere('codeTypeEtu',$row[9])->first()->codeTypeEtu;
            $etudiant->codeNationalite=Nationalite::where('libelleNationalite',$row[10])->orWhere('codeNationalite',$row[10])->first()->codeNationalite;
            $etudiant->Auteur=Auth::user()->login;
            $etudiant->save();
        }
        if(empty(Inscription::where('MATRICULE',$row[0])->where('Id_Annee',AnneeAcademique::where('ANNEE_ACAD',$row[11])->first()->id_Annee_Acad)->where('ID_FILIERE',$row[12])->first())){
            return new Inscription([
                'MATRICULE'=>$row[0],
                'Id_Annee'=>AnneeAcademique::where('ANNEE_ACAD',$row[11])->first()->id_Annee_Acad,
                'ID_FILIERE'=>$row[12],
                'codeStatut'=>Statut::where('LibStatut',$row[13])->orWhere('codeStatut',$row[13])->first()->codeStatut,
                'Group_PDGQ'=>$row[14],
                'reste_paye'=>$row[15],
                'frais_paye'=>$row[15],
                'S_Finance'=>$row[16],
                'Observations'=>$row[8],
                'Auteur'=>Auth::user()->login
            ]);
        }
    }
    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'WINDOWS-1254'
        ];
    }
}
