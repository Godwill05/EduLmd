<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $montantPaye
 * @property string $datePaiement
 * @property string $modePaiement
 * @property integer $numeroTranche
 * @property string $MATRICULE
 * @property integer $Id_Annee
 * @property string $ID_FILIERE
 * @property string $created_at
 * @property string $updated_at
 * @property Inscription $inscription
 * @property Inscription $inscription
 * @property Inscription $inscription
 */
class Paiement extends Model
{
    use \Awobaz\Compoships\Compoships;
    /**
     * @var array
     */
    protected $fillable = ['montantPaye', 'datePaiement', 'modePaiement', 'numeroTranche', 'MATRICULE', 'Id_Annee', 'ID_FILIERE', 'created_at', 'updated_at'];

    public function inscription()
    {
        return Inscription::where('MATRICULE',$this->MATRICULE)
        ->where('Id_Annee',$this->Id_Annee)
        ->where('ID_FILIERE',$this->ID_FILIERE)
        ->first();
    }
}
