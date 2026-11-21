<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $Code_Annee_Etude
 * @property integer $id_S_Gr_Spe
 * @property string $Auteur
 * @property string $Moment
 * @property Classe $classe
 * @property AnneeEtude $anneeEtude
 * @property Inscription[] $inscriptions
 */
class SemestreAnneeEtude extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'annee_etude_semestre';

    /**
     * @var array
     */
    protected $fillable = ['Code_Annee_Etude', 'id_S_Gr_Spe', 'Auteur', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo('App\Models\SemestreEtude', 'id_S_Gr_Spe', 'id_S_Gr_Spe');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeEtude()
    {
        return $this->belongsTo('App\Models\AnneeEtude', 'Code_Annee_Etude', 'Code_Annee_Etude');
    }

    
}
