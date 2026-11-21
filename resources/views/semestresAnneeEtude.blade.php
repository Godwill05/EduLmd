@extends('layout')
@section('title')
Semestres d'année d'étude
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
            <h5 class="fw-bold">Attribuer semestres à année d'étude</h5>
            <form action="{{ route('attribuer-semestres-anneeEtude.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="form-group my-2">
                        <label class="form-label" for="anneeEtude">Année d'étude</label>
                        <select name="anneeEtude" id="anneeEtude" class="form-control form-select
                        @error('anneeEtude')
                        is-invalid
                        @enderror">
                            <option value="">Sélectionner une année d'étude...</option>
                            @foreach ($anneesEtudes as $anEtd)
                            <option value="{{$anEtd->Code_Annee_Etude}}">{{$anEtd->Libelle_AnEtude}}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Ce champ est obligatoire !</div>
                    </div>
                    <div class="form-group my-2">
                        <div class="row">
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <label class="form-label" for="semestre1">Semestre 1</label>
                                <select name="semestre1" id="semestre1" class="form-control form-select
                                @error('semestre1')
                                is-invalid
                                @enderror">
                                    <option value="">Sélectionner un semestre d'étude...</option>
                                    @foreach ($semestres as $sem)
                                    <option value="{{$sem->id_S_Gr_Spe}}">{{$sem->Nom_Classe}}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <label class="form-label" for="semestre2">Semestre 2</label>
                                <select name="semestre2" id="semestre2" class="form-control form-select
                                @error('semestre2')
                                is-invalid
                                @enderror">
                                    <option value="">Sélectionner un semestre d'étude...</option>
                                    @foreach ($semestres as $sem)
                                    <option value="{{$sem->id_S_Gr_Spe}}">{{$sem->Nom_Classe}}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Année d'étude</th>
                    <th>Semestre 1</th>
                    <th>Semestre 2</th>
                </thead>
                <tbody>
                    @foreach ($anneesEtudes as $anEtd)
                        <tr>
                            <td>{{$anEtd->Libelle_AnEtude}}</td>
                            @foreach ($anEtd->anneeEtudeSemestres as $anEt)
                            <td>{{$anEt->classe->Nom_Classe}}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>
@endsection
