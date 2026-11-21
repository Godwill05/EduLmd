<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeTypeEtu
 * @property string $libTypeEtu
 * @property integer $version
 */
class TypeEtudiant extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'typeetudiant';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'codeTypeEtu';

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
    protected $fillable = ['libTypeEtu', 'version'];

    public function etudiants()
    {
        return $this->hasMany('App\Models\Etudiant', 'MATRICULE', 'MATRICULE');
    }
}
