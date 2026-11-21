<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codTypEval
 * @property string $libTypEval
 * @property integer $version
 */
class TypeEvaluation extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'typeevaluation';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codTypEval';

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
    protected $fillable = ['libTypEval', 'version'];
}
