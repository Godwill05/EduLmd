<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $annee1
 * @property string $annee2
 * @property string $annee3
 * @property string $nom
 * @property string $created_at
 * @property string $updated_at
 * @property AnneeEtude $annee1
 * @property AnneeEtude $annee2
 * @property AnneeEtude $annee3
 */
class FiliereCpl extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'filierecpls';

    /**
     * @var array
     */
    protected $fillable = ['annee1', 'annee2', 'annee3', 'nom', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeEtude3()
    {
        return $this->belongsTo('App\Models\AnneeEtude', 'annee3', 'Code_Annee_Etude');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeEtude2()
    {
        return $this->belongsTo('App\Models\AnneeEtude', 'annee2', 'Code_Annee_Etude');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeEtude1()
    {
        return $this->belongsTo('App\Models\AnneeEtude', 'annee1', 'Code_Annee_Etude');
    }
}
