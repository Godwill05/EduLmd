<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeNationalite
 * @property string $libelleNationalite
 * @property integer $version
 */
class Nationalite extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'nationalite';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codeNationalite';

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
    protected $fillable = ['libelleNationalite', 'version'];
}
