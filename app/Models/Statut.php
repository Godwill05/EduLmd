<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codeStatut
 * @property string $LibStatut
 * @property integer $version
 * @property Inscription[] $inscriptions
 */
class Statut extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'statut';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codeStatut';

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
    protected $fillable = ['LibStatut', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscriptions()
    {
        return $this->hasMany('App\Models\Inscription', 'codeStatut', 'codeStatut');
    }
}
