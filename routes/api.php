<?php

use App\Models\AttrEcueUe;
use App\Models\AttrUeClasse;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Prevision;
use App\Models\SemestreAnneeEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/semestre-etude/{id}', function ($id) {
    $sae = SemestreAnneeEtude::where('Code_Annee_Etude', $id)->get();
    $semestres = [];
    foreach ($sae as $s)
        array_push($semestres, $s->classe);
    return response($semestres);
});
Route::get('/ue/{a}/{b}', function ($a,$b) {
    if(request()->ajax()){
        $aUS=AttrUeClasse::where('id_Annee_Acad',$a)
        ->where('id_S_Gr_Spe',$b)->get();
        $ues=[];
        foreach($aUS as $a)
            array_push($ues,['Id_Attr_ue_Sem'=>$a->Id_Attr_ue_Sem,'libelleUe'=>$a->ue->libelleUe]);
        return response($ues);
    }
    abort('401');
});
Route::get('/ecue/{a}', function ($a) {
    if(request()->ajax()){
        $aEU=AttrEcueUe::where('Id_Attr_ue_Sem',$a)->get();
        $ecues=[];
        foreach($aEU as $a)
            array_push($ecues,['ecue'=>$a->codeEcu,'libelle'=>$a->ecue->libelleEcu,'enseignant'=>$a->professeur->nomProf.' '.$a->professeur->prenomsProf]);
        return response($ecues);
    }
    abort('401');
});
Route::get('/etudiants',function(){
    return response(Etudiant::orderBy('nomEtu')->get());
});
Route::get('/etudiant/{a}',function($a){
    return response(Etudiant::where('nomEtu','LIKE',"%$a%")->orWhere('prenomsEtu','LIKE',"%$a%")->get());
});
Route::get('/ins/{a}',function($a){
    if(request()->ajax()){
        if(!function_exists('comparer_par_nom')){
            function comparer_par_nom($a, $b) {
                return strcasecmp($a["nomEtu"], $b["nomEtu"]);
            }
        }
        $inscriptions=Inscription::where('Id_Annee',$a)->get();
        $etudiants=[];
        foreach($inscriptions as $s)
            array_push($etudiants,$s->etudiant);
        usort($etudiants,'comparer_par_nom');
        return response($etudiants);
    }
    abort('401');
});
Route::get('/ins/{a}/{b}',function($a,$b){
    if(!function_exists('comparer_par_nom')){
        function comparer_par_nom($a, $b) {
            return strcasecmp($a["nomEtu"], $b["nomEtu"]);
        }
    }
    if(request()->ajax()){
        $query = Inscription::where('Id_Annee',$a)->where('ID_FILIERE',$b)->whereHas('etudiant');
        if(request('statut') && request('statut') !== 'all'){
            $query->where('codeStatut', request('statut'));
        }
        $inscriptions=$query->get();
        $etudiants=[];
        foreach($inscriptions as $s)
            array_push($etudiants,$s->etudiant);
        usort($etudiants,'comparer_par_nom');
        return response($etudiants);
    }
    abort('401');
});
Route::middleware('can:Supprimer les notes')->delete('/deleteNote/{note}',function($note){
    Note::destroy($note);
});
Route::post('/prev',function(){
    return response();
});
