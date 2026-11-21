<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $numeroProg
 * @property string $datedebut
 * @property string $datefin
 * @property string $heureDebutCours
 * @property string $heureFinCours
 * @property integer $MHoraireProg
 * @property string $codeGroupe
 * @property integer $Id_Attr_ue_Sem
 * @property string $codeEcu
 * @property string $codeSalle
 * @property string $effectuer
 * @property integer $version
 * @property string $login
 * @property string $moment
 */
class Cours extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'prog_cours';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'numeroProg';

    /**
     * @var array
     */
    protected $fillable = ['datedebut', 'datefin', 'heureDebutCours', 'heureFinCours', 'MHoraireProg', 'codeGroupe', 'Id_Attr_ue_Sem', 'codeEcu', 'codeSalle', 'effectuer', 'version', 'login', 'created_at', 'updated_at'];
}
