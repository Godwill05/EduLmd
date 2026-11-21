<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeGrade
 * @property string $libelleGrade
 * @property string $Auteur
 * @property string $Moment
 * @property integer $version
 * @property string $created_at
 * @property string $updated_at
 * @property Classe[] $classes
 */
class Grade extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'grade';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codeGrade';

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
    protected $fillable = ['libelleGrade', 'Auteur', 'Moment', 'version', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classes()
    {
        return $this->hasMany('App\Models\Classe', 'codeGrade', 'codeGrade');
    }
}
