<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property string $MATRICULE
 * @property string $nomEtu
 * @property string $prenomsEtu
 * @property string $dateNaisEtu
 * @property string $lieuNaisEtu
 * @property string $sexeEtu
 * @property string $telEtu
 * @property string $emailEtu
 * @property string $Observation
 * @property string $codeTypeEtu
 * @property string $codeNationalite
 * @property string $Auteur
 * @property string $Moment
 * @property integer $version
 * @property Inscription[] $inscriptions
 */
class Etudiant extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'etudiant';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'MATRICULE';

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
    protected $fillable = ['MATRICULE', 'nomEtu', 'prenomsEtu', 'photo', 'dateNaisEtu', 'lieuNaisEtu', 'sexeEtu', 'telEtu', 'emailEtu', 'Observation', 'codeTypeEtu', 'codeNationalite', 'Auteur', 'Moment', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscriptions()
    {
        return $this->hasMany('App\Models\Inscription', 'MATRICULE', 'MATRICULE');
    }

    public function typeEtudiant()
    {
        return $this->belongsTo('App\Models\TypeEtudiant', 'codeTypeEtu', 'codeTypeEtu');
    }

    public function nationalite()
    {
        return $this->belongsTo('App\Models\Nationalite', 'codeNationalite', 'codeNationalite');
    }
    public function fullname()
    {
        return $this->nomEtu.' '.$this->prenomsEtu;
    }
    public function recapues()
    {
	return $this->hasMany('App\Models\RecapUe', 'MATRICULE', 'MATRICULE');
    }
}
