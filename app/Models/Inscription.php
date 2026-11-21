<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property string $MATRICULE
 * @property integer $Id_Annee
 * @property string $ID_FILIERE
 * @property string $codeStatut
 * @property string $Group_PDGQ
 * @property integer $frais_paye
 * @property integer $reste_paye
 * @property string $S_Finance
 * @property string $Observations
 * @property string $Auteur
 * @property string $Moment
 * @property string $created_at
 * @property string $updated_at
 * @property Anneeacademique $anneeacademique
 * @property Etudiant $etudiant
 * @property Statut $statut
 * @property AnneeEtude $anneeEtude
 * @property Obtenir[] $obtenirs
 * @property Paiement[] $paiements
 * @property Paiement[] $paiements
 * @property Paiement[] $paiements
 */
class Inscription extends Model implements Auditable
{
    use \Awobaz\Compoships\Compoships;
    use \OwenIt\Auditing\Auditable;
    /**
     * @var array
     */
    protected $fillable = ['MATRICULE', 'Id_Annee', 'ID_FILIERE', 'codeStatut', 'Group_PDGQ', 'frais_paye', 'reste_paye', 'S_Finance', 'Observations', 'Auteur', 'Moment', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeacademique()
    {
        return $this->belongsTo('App\Models\AnneeAcademique', 'Id_Annee', 'id_Annee_Acad');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo('App\Models\Etudiant', 'MATRICULE', 'MATRICULE');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function statut()
    {
        return $this->belongsTo('App\Models\Statut', 'codeStatut', 'codeStatut');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeEtude()
    {
        return $this->belongsTo('App\Models\AnneeEtude', 'ID_FILIERE', 'Code_Annee_Etude');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function obtenirs()
    {
        return $this->hasMany('App\Models\Obtenir', 'numeroInscription', 'MATRICULE');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paiements()
    {
        return Paiement::where('MATRICULE',$this->MATRICULE)
        ->where('Id_Annee',$this->Id_Annee)
        ->where('ID_FILIERE',$this->ID_FILIERE)
        ->get();
    }
    public function pmts() {
        return $this->hasMany('App\Models\Paiement',['MATRICULE', 'Id_Annee', 'ID_FILIERE'], ['MATRICULE', 'Id_Annee', 'ID_FILIERE']);
    }

    /**
     * Prévisions de paiement de l'inscription, en vraie relation Eloquent :
     * indispensable à withCount() (l'accès à $this->paiements() renvoie une
     * collection, pas une relation, et coûtait une requête par ligne).
     */
    public function previsions()
    {
        return $this->hasMany('App\Models\Prevision', ['MATRICULE', 'Id_Annee'], ['MATRICULE', 'Id_Annee']);
    }

}
