<?php

namespace App\Http\Controllers;

use App\Models\AnneeEtude;
use App\Models\SemestreAnneeEtude;
use App\Models\SemestreEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SemestresAnneeEtudeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('semestresAnneeEtude',['anneesEtudes'=>AnneeEtude::all(),'semestres'=>SemestreEtude::all(),'anneeEtudeSemestres'=>SemestreAnneeEtude::all()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'anneeEtude'=>'required',
            'semestre1'=>'required',
            'semestre2'=>'required'
        ]);
        if (SemestreAnneeEtude::where('Code_Annee_Etude',$request->anneeEtude)
        ->where('id_S_Gr_Spe',$request->semestre1)->first()) {
            return to_route('attribuer-semestres-anneeEtude.index')->with('echec','Cette attribution existe déjà !');
        }
        if (SemestreAnneeEtude::where('Code_Annee_Etude',$request->anneeEtude)
        ->where('id_S_Gr_Spe',$request->semestre2)->first()) {
            return to_route('attribuer-semestres-anneeEtude.index')->with('echec','Cette attribution existe déjà !');
        }
        if(AnneeEtude::find($request->anneeEtude)->anneeEtudeSemestres->count()>=2)
            return to_route('attribuer-semestres-anneeEtude.index')->with('echec','Impossible d\'attribuer plus de deux semestres à une année d\'étude');
        SemestreAnneeEtude::create([
            'Code_Annee_Etude'=>$request->anneeEtude,
            'id_S_Gr_Spe'=>$request->semestre1,
            'Auteur'=>Auth::user()->login
        ]);
        SemestreAnneeEtude::create([
            'Code_Annee_Etude'=>$request->anneeEtude,
            'id_S_Gr_Spe'=>$request->semestre2,
            'Auteur'=>Auth::user()->login
        ]);
        return to_route('attribuer-semestres-anneeEtude.index')->with('succes','Semestres attribués !');
    }
}
