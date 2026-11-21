<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $numeroProf
 * @property string $nomProf
 * @property string $prenomsProf
 * @property string $DateNais
 * @property string $LieuNais
 * @property string $Sexe
 * @property string $codeNationalite
 * @property string $telProf
 * @property string $emailProf
 * @property string $codTypProf
 * @property string $Auteurs
 * @property string $Moment
 * @property integer $version
 * @property AttrEcueUe[] $attrEcueUes
 * @property Typeprofesseur $typeprofesseur
 */
class Enseignant extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'professeur';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'numeroProf';

    /**
     * @var array
     */
    protected $fillable = ['nomProf', 'prenomsProf', 'DateNais', 'LieuNais', 'Sexe', 'codeNationalite', 'telProf', 'emailProf', 'codTypProf', 'Auteurs', 'Moment', 'version'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attrEcueUes()
    {
        return $this->hasMany('App\Models\AttrEcueUe', 'numeroProf', 'numeroProf');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function typeprofesseur()
    {
        return $this->belongsTo('App\Models\Typeprofesseur', 'codTypProf', 'codTypProf');
    }

    public function nationalite()
    {
        return $this->belongsTo('App\Models\Nationalite', 'codeNationalite', 'codeNationalite');
    }
}
