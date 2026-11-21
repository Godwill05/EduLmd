<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id_Annee_Acad
 * @property string $ANNEE_ACAD
 * @property string $Date_Debut
 * @property string $Date_Fin
 * @property float $Ptge_Validation
 * @property float $Moyenne
 * @property float $MoyenneUE
 * @property float $MoyenneECUE
 * @property string $Auteur
 * @property string $Moment
 * @property AttrUeClasse[] $attrUeClasses
 * @property Inscription[] $inscriptions
 */
class AnneeAcademique extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anneeacademique';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_Annee_Acad';

    /**
     * @var array
     */
    protected $fillable = ['ANNEE_ACAD', 'Date_Debut', 'Date_Fin', 'Ptge_Validation', 'Moyenne', 'MoyenneUE', 'MoyenneECUE', 'Auteur', 'Moment'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrUeClasses()
    {
        return $this->hasMany('App\Models\AttrUeClasse', 'id_Annee_Acad', 'id_Annee_Acad');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscriptions()
    {
        return $this->hasMany('App\Models\Inscription', 'Id_Annee', 'id_Annee_Acad');
    }
}
