<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $MATRICULE
 * @property string $Code_Annee_Etude
 * @property integer $id_Annee_Acad
 * @property integer $Nbre_Cr_Capi
 * @property float $Ptage_Obt
 * @property float $MoyenneSem
 * @property integer $Nbre_UE_Val
 * @property string $DecisionJury
 * @property string $Observation
 * @property string $Auteur
 * @property string $Moment
 */
class RecapAnnuel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recap_annuel';

    /**
     * @var array
     */
    protected $fillable = ['Nbre_Cr_Capi', 'Ptage_Obt', 'MoyenneSem', 'Nbre_UE_Val', 'DecisionJury', 'Observation', 'Auteur', 'Moment'];
    /**
     * Get the etu that owns the RecapAnnuel
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etu()
    {
        return $this->belongsTo(Etudiant::class, 'MATRICULE', 'MATRICULE');
    }
}
