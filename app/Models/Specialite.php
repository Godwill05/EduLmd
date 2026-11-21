<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeSpe
 * @property string $libelleSpe
 * @property integer $codeMention
 * @property string $Auteur
 * @property string $Moment
 * @property integer $version
 * @property Classe[] $classes
 * @property Mention $mention
 */
class Specialite extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'specialite';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codeSpe';

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
    protected $fillable = ['libelleSpe', 'codeMention', 'Auteur', 'Moment', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classes()
    {
        return $this->hasMany('App\Models\Classe', 'codeSpe', 'codeSpe');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mention()
    {
        return $this->belongsTo('App\Models\Mention', 'codeMention', 'codeMention');
    }
}
