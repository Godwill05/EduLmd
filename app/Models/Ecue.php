<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeEcu
 * @property string $libelleEcu
 * @property string $Auteur
 * @property string $Moment
 * @property AttrEcueUe[] $attrEcueUes
 */
class Ecue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecue';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'codeEcu';

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
    protected $fillable = ['codeEcu', 'libelleEcu', 'Auteur', 'Moment'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrEcueUes()
    {
        return $this->hasMany('App\Models\AttrEcueUe', 'codeEcu', 'codeEcu');
    }
}
