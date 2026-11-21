<?php

namespace App\Http\Controllers;

use App\Models\Etablissement;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EtablissementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('etablissements',['items'=>Etablissement::all()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'universite'=>'required',
            'nom'=>'required',
            'telephone'=>'required',
            'email'=>'required',
            'directeur'=>'required',
            'directeurAdjoint'=>'required',
        ]);
        $logo=$request->file('logo');
        $extension=$logo->extension();
        $logo->storeAs('public/assets/img/ufr',strtolower(Str::camel($request->nom)).'.'.$extension);
        $model=new Etablissement();
        $model->Univ=$request->universite;
        $model->Ecol_Ufr=$request->nom;
        $model->Adres_Post=$request->adressePostale;
        $model->Site_web=$request->siteWeb;
        $model->logo_ufr=strtolower(Str::camel($request->nom)).'.'.$extension;
        $model->Tel=$request->telephone;
        $model->mail=$request->email;
        $model->directeur=$request->directeur;
        $model->directeurAdjoint=$request->directeurAdjoint;
        $model->ville=$request->ville;
        $model->obs=$request->observations;
        $model->Auteur=Auth::user()->login;
        $model->save();
        return to_route('etablissements.index')->with('succes','Établissement ajouté');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'universite'=>'required',
            'nom'=>'required',
            'telephone'=>'required',
        ]);
        $model=Etablissement::find($id);
        $model->Univ=$request->universite;
        $model->Ecol_Ufr=$request->nom;
        $model->Adres_Post=$request->adressePostale;
        $model->Site_web=$request->siteWeb;
        $model->Tel=$request->telephone;
        $model->mail=$request->email;
        $model->directeur=$request->directeur;
        $model->directeurAdjoint=$request->directeurAdjoint;
        $model->ville=$request->ville;
        $model->obs=$request->observations;
        $model->save();
        return to_route('etablissements.index')->with('succes','Établissement modifié');
    }

    public function manageLogo($id) {
        $etablissement=Etablissement::find($id);
        $code_ufr=Str::camel($etablissement->Ecol_Ufr);
        if(request('btn')=="Supprimer l'image")
        {
            if(Storage::exists('public/assets/img/ufr/'.$etablissement->logo_ufr))
            {
                Storage::delete('public/assets/img/ufr/'.$etablissement->logo_ufr);
                $etablissement->logo_ufr=NULL;
                $etablissement->save();
                return to_route('etablissements.index')->with('succes','Logo supprimé !');
            }
            return to_route('etablissements.index')->with('echec','Cet établissement n\'a pas de logo!');
        }
        if(request('btn')=='Enregistrer')
        {
            $logo=request()->file('logo');
            $logo->storeAs('public/assets/img/ufr',strtolower($code_ufr).'.'.$logo->extension());
            $extension=$logo->extension();
            $etablissement->logo_ufr=strtolower($code_ufr).'.'.$extension;
            $etablissement->save();
            return to_route('etablissements.index')->with('succes','Logo enregistré !');
        }
        if (request('btn')=='Modifier') {
            if(Storage::exists('public/assets/img/ufr/'.$etablissement->logo_ufr))
            {
                Storage::delete('public/assets/img/ufr/'.$etablissement->logo_ufr);
                $logo=request()->file('logo');
                $logo->storeAs('public/assets/img/ufr',strtolower($code_ufr).'.'.$logo->extension());
                $extension=$logo->extension();
                $etablissement->logo_ufr=strtolower($code_ufr).'.'.$extension;
                $etablissement->save();
                return to_route('etablissements.index')->with('succes','Logo modifié !');
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Etablissement::find($id)->delete();
        return to_route('etablissements.index')->with('succes','Établissement supprimé');
    }
}
