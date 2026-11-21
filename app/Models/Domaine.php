<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $Id_domaine
 * @property string $lib_domaine
 * @property string $Auteur
 * @property string $Moment
 * @property Mention[] $mentions
 */
class Domaine extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'domaine';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'Id_domaine';

    /**
     * @var array
     */
    protected $fillable = ['lib_domaine', 'Auteur', 'Moment'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mentions()
    {
        return $this->hasMany('App\Models\Mention', 'Id_domaine', 'Id_domaine');
    }
}
