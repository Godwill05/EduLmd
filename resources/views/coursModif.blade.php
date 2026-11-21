@extends('layout')
@section('title')
Modification des détails d'un cours
@endsection
@section('content')
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success">
            {{session('succes')}}
        </div>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger">
            {{session('echec')}}
        </div>
    </div>
    @endif
    <div class="row">
        <div class="card p-4">
            <form action="{{ route('cours.update',$cours->numeroProg) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="row g-3">
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="dateDebut" class="form-label">Période de cours</label>
                            <div class="input-group" id="periodeCours">
                                <input type="text" name="dateDebut" id="dateDebut" class="form-control" readonly placeholder="01-01-1970" value="{{$cours->datedebut}}">
                                <span class="input-group-text">à</span>
                                <input type="text" name="dateFin" id="dateFin" class="form-control" readonly placeholder="01-01-1970" value="{{$cours->datefin}}">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="salle" class="form-label">Salle</label>
                            <select name="salle" id="salle" class="form-control form-select">
                                @foreach (DB::select("select * from salle") as $salle)
                                <option value="{{$salle->codeSalle}}" @selected($salle->codeSalle==$cours->codeSalle)>{{$salle->Lib_Salle}} || {{$salle->nombrePlaceCours}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="heureDebut" class="form-label">Heure de début</label>
                            <input type="time" name="heureDebut" id="heureDebut" class="form-control" value="{{$cours->heureDebutCours}}">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="heureFin" class="form-label">Heure de fin</label>
                            <input type="time" name="heureFin" id="heureFin" class="form-control" value="{{$cours->heureFinCours}}">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="groupePedagogique">Groupe pédagogique</label>
                            <select name="groupePedagogique" id="groupePedagogique" class="form-control form-select">
                                <option value="" @selected($cours->codeGroupe=="")>Aucun</option>
                                <option value="A" @selected($cours->codeGroupe=="A")>A</option>
                                <option value="B" @selected($cours->codeGroupe=="B")>B</option>
                                <option value="C" @selected($cours->codeGroupe=="C")>C</option>
                                <option value="D" @selected($cours->codeGroupe=="D")>D</option>
                            </select>
                        </div>
                    </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
