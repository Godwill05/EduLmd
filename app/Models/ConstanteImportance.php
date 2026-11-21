<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $idImportance
 * @property string $libelleImp
 * @property integer $version
 * @property AttrUeClasse[] $attrUeClasses
 */
class ConstanteImportance extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'importance';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'idImportance';

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
    protected $fillable = ['libelleImp', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrUeClasses()
    {
        return $this->hasMany('App\Models\AttrUeClasse', 'idImportance', 'idImportance');
    }
}
