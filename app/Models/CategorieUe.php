<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $Id_Cat_Ue
 * @property string $Lib_Cat_Ue
 * @property AttrUeClasse[] $attrUeClasses
 */
class CategorieUe extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'categorie_ue';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'Id_Cat_Ue';

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
    protected $fillable = ['Lib_Cat_Ue'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrUeClasses()
    {
        return $this->hasMany('App\Models\AttrUeClasse', 'Id_Cat_Ue', 'Id_Cat_Ue');
    }
}
