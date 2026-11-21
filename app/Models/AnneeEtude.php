<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $Code_Annee_Etude
 * @property string $Libelle_AnEtude
 * @property string $Auteur
 * @property string $Moment
 * @property AnneeEtudeSemestre[] $anneeEtudeSemestres
 */
class AnneeEtude extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'annee_etude';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'Code_Annee_Etude';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = ['Libelle_AnEtude', 'Auteur', 'Moment'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function anneeEtudeSemestres()
    {
        return $this->hasMany('App\Models\SemestreAnneeEtude', 'Code_Annee_Etude', 'Code_Annee_Etude');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscriptions()
    {
        return $this->hasMany('App\Models\Inscription', 'ID_FILIERE', 'Code_Annee_Etude');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function filierescompletes1()
    {
        return $this->hasOne(FiliereCpl::class,'annee1');
    }
    public function filierescompletes2()
    {
        return $this->hasOne(FiliereCpl::class,'annee2');
    }
    public function filierescompletes3()
    {
        return $this->hasOne(FiliereCpl::class,'annee3');
    }

}
