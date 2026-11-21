@extends('layout')
@section('title')
Récapitulatif du grade
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
                <ul class="nav nav-tabs nav-tabs-bordered" id="borderedTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#enregistrement" type="button" role="tab" aria-controls="home" aria-selected="true">Récapitulatif</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#importCSV" type="button" role="tab" aria-controls="profile" aria-selected="false">Attestation de rang</button>
                    </li>
                </ul>
                <div class="tab-content pt-2" id="borderedTabContent">
                    <div class="tab-pane fade show active p-2" id="enregistrement" role="tabpanel" aria-labelledby="home-tab">
                        <form action="{{ route('impression.grade') }}" method="POST" id="recapGrade" target="_blank">
                            @csrf
                            <div class="row g-3">
                                <div class="col-lg-6 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="filiere">Filière</label>
                                        <select name="filiere" id="filiere" class="form-control form-control-sm form-select" @if(\App\Models\FiliereCpl::all()->isEmpty()) @disabled(true) @endif>
                                            <option value="">Sélectionner une filière...</option>
                                            @foreach (\App\Models\FiliereCpl::all() as $fc)
                                            <option value="{{$fc->id}}">{{$fc->nom}}</option>
                                            @endforeach
                                        </select>
                                        @if(\App\Models\FiliereCpl::all()->isEmpty())
                                        <span class="form-text text-muted">Aucune filière complète trouvée | <a href="{{ route('filieres.index', ['id'=>1]) }}">Ajouter ?</a></span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="anneeAcademique">Année académique</label>
                                        <select name="anneeAcademique" id="anneeAcademique" class="form-control form-control-sm form-select">
                                            <option value="">Sélectionner l'année académique de début...</option>
                                            @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                            <option value="{{$anneeAcademique->id_Annee_Acad}}">{{$anneeAcademique->ANNEE_ACAD}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="dateDeliberation">Date de délibération</label>
                                        <input type="text" name="dateDeliberation" id="dateDeliberation" class="form-control" placeholder="JJ/MM/AAAA">
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button class="btn btn-primary" type="button" onclick="afficher()">Afficher</button>
                                <input type="submit" value="Ordre de mérite" class="btn btn-secondary" name="btn">
                                <input type="submit" value="Imprimer" class="btn btn-dark" name="btn">
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade p-2" id="importCSV" role="tabpanel" aria-labelledby="profile-tab">
                        <form action="{{ route('impression.rang') }}" method="POST" id="recapGradeSolo" target="_blank">
                            @csrf
                            <div class="row g-3">
                                <div class="col-lg-6 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="annee_etude">Année d'étude</label>
                                        <select name="annee_etude" id="annee_etude" class="form-control form-control-sm form-select">
                                            <option value="">Sélectionner une année d'étude...</option>
                                            @foreach (\App\Models\AnneeEtude::all() as $ae)
                                            <option value="{{$ae->Code_Annee_Etude}}">{{$ae->Libelle_AnEtude}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="anneeAcademique">Année académique</label>
                                        <select name="anneeAcademique" id="anneeAcademique" class="form-control form-control-sm form-select">
                                            <option value="">Sélectionner l'année académique de début...</option>
                                            @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                            <option value="{{$anneeAcademique->id_Annee_Acad}}">{{$anneeAcademique->ANNEE_ACAD}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="dateDeliberation">Date de délibération</label>
                                        <input type="text" name="dateDeliberation" id="dateDeliberation" class="form-control" placeholder="JJ/MM/AAAA">
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button class="btn btn-primary" type="button" onclick="afficherSolo()">Afficher</button>
                                <input type="submit" value="Imprimer" class="btn btn-secondary" name="btn">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" id="afficher">

        </div>
    </div>
</section>
@endsection
@section('js')
<script>
    const datepicker = new Datepicker(document.querySelector('#dateDeliberation'), {
        autohide:true,
        buttonClass: 'btn',
    });
    function afficher(){
        $('#afficher').addClass('card p-4');
        $('#afficher').html('<button class="btn btn-link" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:'{{route("affichage-recap-grade")}}',
            type:'POST',
            data:$('#recapGrade').serialize(),
            success:function(response){
                $('#afficher').html(response);
            }
        });
    }
    function afficherSolo(){
        $('#afficher').addClass('card p-4');
        $('#afficher').html('<button class="btn btn-link" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:'{{route("affichage-recap-gradeSolo")}}',
            type:'POST',
            data:$('#recapGradeSolo').serialize(),
            success:function(response){
                $('#afficher').html(response);
            }
        });
    }
</script>
@endsection
