<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codTypProf
 * @property string $libTypProf
 * @property integer $version
 * @property Professeur[] $professeurs
 */
class Typeprofesseur extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'typeprofesseur';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codTypProf';

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
    protected $fillable = ['libTypProf', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function professeurs()
    {
        return $this->hasMany('App\Models\Professeur', 'codTypProf', 'codTypProf');
    }
}
