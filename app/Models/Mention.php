<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $codeMention
 * @property string $libelleMention
 * @property integer $Id_domaine
 * @property string $Auteur
 * @property string $Moment
 * @property integer $version
 * @property Domaine $domaine
 */
class Mention extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'mention';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codeMention';

    /**
     * @var array
     */
    protected $fillable = ['libelleMention', 'Id_domaine', 'Auteur', 'Moment', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domaine()
    {
        return $this->belongsTo('App\Models\Domaine', 'Id_domaine', 'Id_domaine');
    }
}
