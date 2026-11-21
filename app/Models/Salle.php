<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeSalle
 * @property string $Lib_Salle
 * @property integer $nombrePlaceCours
 * @property integer $nombrePlaceCompo
 * @property string $Auteur
 * @property string $Moment
 * @property integer $version
 * @property string $created_at
 * @property string $updated_at
 */
class Salle extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'salle';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codeSalle';

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
    protected $fillable = ['Lib_Salle', 'nombrePlaceCours', 'nombrePlaceCompo', 'Auteur', 'Moment', 'version', 'created_at', 'updated_at'];
}
