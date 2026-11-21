<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property integer $idObtenir
 * @property string $numeroInscription
 * @property integer $numeroEval
 * @property float $Note
 * @property string $login
 * @property string $moment
 * @property string $loginupdate
 * @property string $momentupdate
 * @property string $Observation
 * @property integer $version
 * @property Evaluation $evaluation
 * @property Inscription $inscription
 */
class Note extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'obtenir';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'idObtenir';

    /**
     * @var array
     */
    protected $fillable = ['numeroInscription', 'numeroEval', 'Note', 'login', 'moment', 'loginupdate', 'momentupdate', 'Observation', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluation()
    {
        return $this->belongsTo('App\Models\Evaluation', 'numeroEval', 'numeroEval');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inscription()
    {
        return $this->belongsTo('App\Models\Inscription', 'numeroInscription', 'MATRICULE');
    }
}
