<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $MATRICULE
 * @property integer $Id_Annee
 * @property string $ID_FILIERE
 * @property string $montant_a_payer
 * @property boolean $paye
 * @property string $echeance
 * @property integer $reconduit
 * @property string $created_at
 * @property string $updated_at
 * @property Inscription $inscription
 */
class Prevision extends Model
{
    /* Clés composites (MATRICULE + Id_Annee) : requis par les relations
       utilisées par withCount() et les paiements par tranche. */
    use \Awobaz\Compoships\Compoships;

    /**
     * @var array
     */
    protected $fillable = ['MATRICULE', 'Id_Annee', 'ID_FILIERE', 'montant_a_payer', 'reste_a_payer', 'paye', 'echeance', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /**
     * Inscriptions de la même promotion : permet de compter les prévisions
     * par inscription via withCount, sans requête par ligne.
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class, ['MATRICULE', 'Id_Annee'], ['MATRICULE', 'Id_Annee']);
    }

    public function inscription()
    {
        return Inscription::where('MATRICULE',$this->MATRICULE)
        ->where('Id_Annee',$this->Id_Annee)
        ->where('ID_FILIERE',$this->ID_FILIERE)
        ->first();
    }
    public function etudiant()
    {
        return $this->belongsTo('App\Models\Etudiant', 'MATRICULE', 'MATRICULE');
    }
    public function anneeacademique()
    {
        return $this->belongsTo('App\Models\AnneeAcademique', 'Id_Annee', 'id_Annee_Acad');
    }
}
