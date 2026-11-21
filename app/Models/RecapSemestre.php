<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $MATRICULE
 * @property integer $id_S_Gr_Spe
 * @property integer $id_Annee_Acad
 * @property integer $Nbre_Cr_Capi
 * @property float $Ptage_Obt
 * @property float $MoyenneSem
 * @property string $Observation
 * @property string $Auteur
 * @property string $Moment
 */
class RecapSemestre extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'recap_sem';

    /**
     * @var array
     */
    protected $fillable = ['Nbre_Cr_Capi', 'Ptage_Obt', 'MoyenneSem', 'Observation', 'Auteur', 'Moment'];
}
