<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $numeroSemestre
 * @property string $Lib_Semestre
 * @property string $Auteur
 * @property string $Moment
 * @property integer $Version
 * @property Classe[] $classes
 */
class Semestre extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'semestre';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'numeroSemestre';

    /**
     * @var array
     */
    protected $fillable = ['Lib_Semestre', 'Auteur', 'Moment', 'Version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classes()
    {
        return $this->hasMany('App\Models\Classe', 'numeroSemestre', 'numeroSemestre');
    }
}
