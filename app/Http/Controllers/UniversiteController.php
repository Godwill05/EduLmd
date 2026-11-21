<?php

namespace App\Http\Controllers;

use App\Models\Universite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UniversiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('universites',['items'=>Universite::all()]);
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
            'code'=>'required',
            'nom'=>'required',
            'telephone'=>'required',
        ]);

        $logo=$request->file('logo');
        $extension=$logo->extension();
        $logo->storeAs('public/assets/img/univ',strtolower($request->code).'.'.$extension);
        $universite=new Universite();
        $universite->code=$request->code;
        $universite->nom=$request->nom;
        $universite->telephone=$request->telephone;
        $universite->email=$request->email;
        $universite->recteur=$request->recteur;
        $universite->siteWeb=$request->siteWeb;
        $universite->logo=strtolower($request->code).'.'.$extension;
        $universite->adressePostale=$request->adressePostale;
        if($request->observations)
            $universite->observations=$request->observations;
        $universite->save();
        return to_route('universites.index')->with('succes','Université enregistrée');
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
        Universite::find($id)->update($request->all());
        return to_route('universites.index')->with('succes','Université modifiée');
    }

    public function manageLogo($id) {
        $universite=Universite::find($id);
        if(request('btn')=="Supprimer l'image")
        {
            if(Storage::exists('public/assets/img/univ/'.$universite->logo))
            {
                Storage::delete('public/assets/img/univ/'.$universite->logo);
                $universite->logo=NULL;
                $universite->save();
                return to_route('universites.index')->with('succes','Logo supprimé !');
            }
            return to_route('universites.index')->with('echec','Cette université n\'a pas de logo!');
        }
        if(request('btn')=='Enregistrer')
        {
            $logo=request()->file('logo');
            $logo->storeAs('public/assets/img/univ',strtolower($universite->code).'.'.$logo->extension());
            $extension=$logo->extension();
            $universite->logo=strtolower($universite->code).'.'.$extension;
            $universite->save();
            return to_route('universites.index')->with('succes','Logo enregistré !');
        }
        if (request('btn')=='Modifier') {
            if(Storage::exists('public/assets/img/univ/'.$universite->logo))
            {
                Storage::delete('public/assets/img/univ/'.$universite->logo);
                $logo=request()->file('logo');
                $logo->storeAs('public/assets/img/univ',strtolower($universite->code).'.'.$logo->extension());
                $extension=$logo->extension();
                $universite->logo=strtolower($universite->code).'.'.$extension;
                $universite->save();
                return to_route('universites.index')->with('succes','Logo modifié !');
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
        Universite::find($id)->delete();
        return to_route('universites.index')->with('succes','Université supprimée');
    }
}
