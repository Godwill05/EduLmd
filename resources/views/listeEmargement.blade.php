@extends('layout')
@section('title')
Impression des listes d'émargement
@endsection
@section('content')
<section class="section">
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
        <div class="p-4 card">
            <form action="{{ route('impression.listeEmargement') }}" method="POST" id="recapAnnuel" target="_blank">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeEtude">Année d'étude</label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-control-sm form-select">
                                <option value="">Sélectionner une année d'étude...</option>
                                @foreach (\App\Models\AnneeEtude::all() as $anneeEtude)
                                <option value="{{$anneeEtude->Code_Annee_Etude}}">{{$anneeEtude->Libelle_AnEtude}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeAcademique">Année académique</label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-control-sm form-select">
                                <option value="">Sélectionner l'année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                <option value="{{$anneeAcademique->id_Annee_Acad}}">{{$anneeAcademique->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <input type="submit" class="btn btn-sm btn-info" value="Liste des étudiants" name="btn"/>
                    <input type="submit" class="btn btn-sm btn-primary" value="Inscrits" name="btn"/>
                    <input type="submit" class="btn btn-sm btn-secondary" name="btn" value="Non inscrits"/>
                </div>
            </form>
        </div>
    </div>
    <div class="row" id="afficher">

    </div>
</div>
</section>
@endsection
@section('js')
<script>
$('#anneeEtude').attr('required','true')
$('#anneeAcademique').attr('required','true')
</script>
@endsection
