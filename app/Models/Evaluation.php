<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $numeroEval
 * @property string $dateEval
 * @property string $heureDebutEval
 * @property integer $dureeEval
 * @property string $codeGroupe
 * @property integer $Id_Attr_ue_Sem
 * @property string $codeEcu
 * @property string $codTypEval
 * @property integer $effectuer
 * @property integer $corriger
 * @property integer $version
 * @property string $login
 * @property string $moment
 * @property Note[] $notes
 */
class Evaluation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'evaluation';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'numeroEval';

    /**
     * @var array
     */
    protected $fillable = ['dateEval', 'heureDebutEval', 'dureeEval', 'codeGroupe', 'Id_Attr_ue_Sem', 'codeEcu', 'codTypEval', 'effectuer', 'corriger', 'version', 'login', 'created_at','updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\Models\Note', 'numeroEval', 'numeroEval');
    }
    public function ueSem()
    {
        return $this->belongsTo('App\Models\AttrUeClasse','Id_Attr_ue_Sem');
    }
    public function ecue()
    {
        return $this->belongsTo('App\Models\Ecue','codeEcu');
    }
    public function attrEcueUe($a,$b)
    {
        return AttrEcueUe::where('Id_Attr_ue_Sem',$a)
        ->where('codeEcu',$b)->first();
    }
    /**
     * Get the type that owns the Evaluation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function typeEvaluation()
    {
        return $this->belongsTo(TypeEvaluation::class, 'codTypEval');
    }
}
