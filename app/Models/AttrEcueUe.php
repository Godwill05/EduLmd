<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property integer $Id_Attr_ue_Sem
 * @property string $codeEcu
 * @property integer $Seuil_Ecu
 * @property float $Ponderation
 * @property string $Eva_Mode
 * @property float $PoidsDev
 * @property float $PoidsPart
 * @property string $Observation
 * @property integer $numeroProf
 * @property integer $MasseHoraire
 * @property integer $MasseHoraireExec
 * @property string $Auteur
 * @property string $Moment
 * @property AttrUeClasse $attrUeClasse
 * @property Professeur $professeur
 * @property Ecue $ecue
 */
class AttrEcueUe extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attr_ecue_ue';

    /**
     * @var array
     */
    protected $fillable = ['Id_Attr_ue_Sem','pedagogique','codeEcu','Seuil_Ecu', 'Ponderation', 'Eva_Mode', 'PoidsDev', 'PoidsPart', 'Observation', 'numeroProf', 'MasseHoraire', 'MasseHoraireExec', 'Auteur', 'Moment'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ueSemestre()
    {
        return $this->belongsTo('App\Models\AttrUeClasse', 'Id_Attr_ue_Sem', 'Id_Attr_ue_Sem');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function professeur()
    {
        return $this->belongsTo('App\Models\Enseignant', 'numeroProf', 'numeroProf');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ecue()
    {
        return $this->belongsTo('App\Models\Ecue', 'codeEcu', 'codeEcu');
    }
}
