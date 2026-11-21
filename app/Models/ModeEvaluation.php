<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $idMode
 * @property string $lib_mode
 * @property string $Auteuur
 * @property string $Moment
 */
class ModeEvaluation extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'modeval';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'idMode';

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
    protected $fillable = ['lib_mode', 'Auteuur', 'Moment'];
}
