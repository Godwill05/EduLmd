<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $MATRICULE
 * @property string $Code_Annee_Etude
 * @property integer $Id_Attr_ue_Sem
 * @property float $Moyenne_UE
 * @property string $Etat_UE
 * @property string $Observation
 * @property string $Auteur
 * @property string $moment
 */
class RecapUe extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recap_ue';

    /**
     * @var array
     */
    protected $fillable = ['Moyenne_UE', 'Etat_UE', 'Observation', 'Auteur', 'moment'];
    /**
     * Get the ueSem that owns the RecapUe
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ueSem()
    {
        return $this->belongsTo(AttrUeClasse::class, 'Id_Attr_ue_Sem', 'Id_Attr_ue_Sem');
    }
    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'MATRICULE', 'MATRICULE');
    }
}
