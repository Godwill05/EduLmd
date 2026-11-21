<?php

namespace App\Http\Controllers;

use App\Models\Domaine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomaineResaoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('domainesresao',['domaines'=>Domaine::all()]);
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
            'libelleDomaine'=>'required'
        ]);
        Domaine::create([
            'lib_domaine'=>$request->libelleDomaine,
            'Auteur'=>'Admin'
        ]);
        return to_route('domainesresao.index')->with('succes','Domaine enregistré !');
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Domaine::find($id)->update([
            'lib_domaine'=>$request->libelleDomaine
        ]);
        return to_route('domainesresao.index')->with('succes','Domaine modifié !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Domaine::find($id)->delete();
        return to_route('domainesresao.index')->with('succes','Domaine supprimé avec succès');
    }
}
