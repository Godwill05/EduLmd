<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeUe
 * @property string $libelleUe
 * @property string $Auteur
 * @property string $Moment
 * @property integer $version
 * @property AttrUeClasse[] $attrUeClasses
 */
class Ue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ue';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'codeUe';

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
    protected $fillable = ['codeUe','libelleUe', 'Auteur', 'Moment', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrUeClasses()
    {
        return $this->hasMany('App\Models\AttrUeClasse', 'codeUe', 'codeUe');
    }
}
