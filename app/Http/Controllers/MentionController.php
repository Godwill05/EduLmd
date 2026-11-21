<?php

namespace App\Http\Controllers;

use App\Models\Domaine;
use App\Models\Mention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MentionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('mentions',['mentions'=>Mention::all(),'domaines'=>Domaine::all()]);
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
            'libelleMention'=>'required',
            'domaineMention'=>'required',
        ]);
        Mention::create([
            'Id_domaine'=>$request->domaineMention,
            'libelleMention'=>$request->libelleMention,
            'Auteur'=>Auth::user()->login
        ]);
        return to_route('mentions.index')->with('succes','Mention enregistrée !');
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
        Mention::find($id)->update([
            'Id_domaine'=>$request->domaineMention,
            'libelleMention'=>$request->libelleMention,
        ]);
        return to_route('mentions.index')->with('succes','Mention modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Mention::find($id)->delete();
        return to_route('mentions.index')->with('succes','Mention supprimée avec succès');
    }
}
