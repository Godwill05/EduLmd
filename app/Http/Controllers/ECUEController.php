<?php

namespace App\Http\Controllers;

use App\Models\Ecue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ECUEController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('ecue',['items'=>Ecue::all()]);
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
            'codeECUE'=>'required',
            'intitule'=>'required'
        ]);
        Ecue::create([
            'codeEcu'=>$request->codeECUE,
            'libelleEcu'=>$request->intitule,
            'Auteur'=>Auth::user()->login
        ]);
        return to_route('ecue.index')->with('succes','ECUE enregistrée !');
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
            'codeECUE'=>'required',
            'intitule'=>'required'
        ]);
        Ecue::find($id)->update([
            'codeEcu'=>$request->codeECUE,
            'libelleEcu'=>$request->intitule,
        ]);
        return to_route('ecue.index')->with('succes','ECUE modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Ecue::find($id)->delete();
        return to_route('ecue.index')->with('succes','ECUE supprimée !');
    }
}
