<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property integer $Id_Attr_ue_Sem
 * @property integer $id_Annee_Acad
 * @property integer $id_S_Gr_Spe
 * @property string $codeUe
 * @property string $Id_Cat_Ue
 * @property string $idImportance
 * @property float $Seuil_Ue
 * @property float $Credit
 * @property string $Auteur
 * @property string $Moment
 * @property string $created_at
 * @property string $updated_at
 * @property AttrEcueUe[] $attrEcueUes
 * @property SemestreEtude $semestre
 * @property CategorieUe $categorieUe
 * @property AnneeAcademique $anneeacademique
 * @property Ue $ue
 * @property ConstanteImportance $importance
 */
class AttrUeClasse extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attr_ue_classe';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'Id_Attr_ue_Sem';

    /**
     * @var array
     */
    protected $fillable = ['id_Annee_Acad', 'id_S_Gr_Spe', 'codeUe', 'Id_Cat_Ue', 'idImportance', 'Seuil_Ue', 'Credit', 'Auteur', 'Moment', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrEcueUes()
    {
        return $this->hasMany('App\Models\AttrEcueUe', 'Id_Attr_ue_Sem', 'Id_Attr_ue_Sem');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function semestre()
    {
        return $this->belongsTo('App\Models\SemestreEtude', 'id_S_Gr_Spe', 'id_S_Gr_Spe');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categorieUe()
    {
        return $this->belongsTo('App\Models\CategorieUe', 'Id_Cat_Ue', 'Id_Cat_Ue');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeacademique()
    {
        return $this->belongsTo('App\Models\AnneeAcademique', 'id_Annee_Acad', 'id_Annee_Acad');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ue()
    {
        return $this->belongsTo('App\Models\Ue', 'codeUe', 'codeUe');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function importance()
    {
        return $this->belongsTo('App\Models\ConstanteImportance', 'idImportance', 'idImportance');
    }
}
