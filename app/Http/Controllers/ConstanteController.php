<?php

namespace App\Http\Controllers;

use App\Models\ConstanteCategorie;
use App\Models\ConstanteImportance;
use Illuminate\Http\Request;

class ConstanteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('constantes',['constantesImp'=>ConstanteImportance::all(),'constantesCat'=>ConstanteCategorie::all()]);
    }
}
