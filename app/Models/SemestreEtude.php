<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id_S_Gr_Spe
 * @property string $Nom_Classe
 * @property integer $numeroSemestre
 * @property string $codeSpe
 * @property string $options
 * @property string $codeGrade
 * @property string $Auteur
 * @property string $Moment
 * @property integer $version
 * @property AnneeEtudeSemestre[] $anneeEtudeSemestres
 * @property AttrUeClasse[] $attrUeClasses
 * @property Semestre $semestre
 * @property Grade $grade
 * @property Specialite $specialite
 */
class SemestreEtude extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classe';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_S_Gr_Spe';

    /**
     * @var array
     */
    protected $fillable = ['Nom_Classe', 'numeroSemestre', 'codeSpe', 'options', 'codeGrade', 'Auteur', 'Moment', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function anneeEtudeSemestres()
    {
        return $this->hasMany('App\Models\SemestreAnneeEtude', 'id_S_Gr_Spe', 'id_S_Gr_Spe');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrUeClasses()
    {
        return $this->hasMany('App\Models\AttrUeClasse', 'id_S_Gr_Spe', 'id_S_Gr_Spe');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function semestre()
    {
        return $this->belongsTo('App\Models\Semestre', 'numeroSemestre', 'numeroSemestre');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function grade()
    {
        return $this->belongsTo('App\Models\Grade', 'codeGrade', 'codeGrade');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function specialite()
    {
        return $this->belongsTo('App\Models\Specialite', 'codeSpe', 'codeSpe');
    }
}
