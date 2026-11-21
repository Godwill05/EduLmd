<?php

namespace App\Http\Controllers;

use App\Models\FiliereCpl;
use Illuminate\Http\Request;

class FiliereCompletesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('filierescompletes');
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
        $fc=new FiliereCpl();
        $fc->nom=request('nom');
        $fc->annee1=request('annee1');
        $fc->annee2=request('annee2');
        $fc->annee3=request('annee3');
        $fc->save();
        return to_route('filieres.index')->with('succes','Filière constituée !');
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
        $fc=FiliereCpl::find($id);
        $fc->nom=request('nom');
        $fc->annee1=request('annee1');
        $fc->annee2=request('annee2');
        $fc->annee3=request('annee3');
        $fc->save();
        return to_route('filieres.index')->with('succes','Filière modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        FiliereCpl::destroy($id);
        return to_route('filieres.index')->with('succes','Filière supprimée !');
    }
}
