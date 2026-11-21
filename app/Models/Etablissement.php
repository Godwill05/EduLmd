<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $Id_Ufr
 * @property integer $Univ
 * @property string $Ecol_Ufr
 * @property string $Adres_Post
 * @property string $Site_web
 * @property string $Tel
 * @property string $mail
 * @property string $directeur
 * @property string $directeurAdjoint
 * @property string $ville
 * @property string $obs
 * @property string $Auteur
 * @property string $Moment
 * @property string $created_at
 * @property string $updated_at
 */
class Etablissement extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ufr';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'Id_Ufr';

    /**
     * @var array
     */
    protected $fillable = ['Univ', 'logo_ufr', 'Ecol_Ufr', 'Adres_Post', 'Site_web', 'Tel', 'mail', 'directeur', 'directeurAdjoint', 'ville', 'obs', 'Auteur', 'Moment', 'created_at', 'updated_at'];

    public function universite(){
        return $this->belongsTo('App\Models\Universite','Univ','id');
    }
}
