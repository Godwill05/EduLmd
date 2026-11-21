@extends('layout')
@section('title')
Répartition en salle
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
            <h5 class="fw-bold">Répartition en salle de compositions</h5>
            <form action="{{ route('impression.repartir') }}" method="POST" id="afficherSalleCompo">
                @csrf
                <div class="row">
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="mb-3">
                            <label for="dateDebut" class="form-label">Période des compositions</label>
                            <div class="input-group" id="periodeCompo">
                                <input type="text" name="dateDebut" id="dateDebut" class="form-control">
                                <span class="input-group-text">à</span>
                                <input type="text" name="dateFin" id="dateFin" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="mb-3">
                            <label for="salle" class="form-label">Salle de composition</label>
                            <select name="salle" id="salle" class="form-control form-select">
                                <option value="">Sélectionner une salle...</option>
                                @foreach (\App\Models\Salle::all() as $salle)
                                <option value="{{$salle->codeSalle}}">{{$salle->Lib_Salle}} | {{$salle->nombrePlaceCompo}} places</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="mb-3">
                            <label for="anneeAImprimer" class="form-label">Année à imprimer</label>
                            <select name="anneeAImprimer" id="anneeAImprimer" class="form-control form-select">
                                <option value="l1">Licence 1</option>
                                <option value="l2">Licence 2</option>
                                <option value="l3">Licence 3</option>
                                <option value="M1">Master 1</option>
                                <option value="M2">Master 2</option>
                                <option value="PS">Programmes Spéciaux</option>
                                <option value="MN">Mis à Niveau</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary" type="button" onclick="afficher()"><i class="bx bx-show"></i> Afficher</button>
                    <button class="btn btn-dark" title="Imprimer"><i class="bx bx-printer"></i> Imprimer</button>
                </div>
            </form>
        </div>
        <div id="afficher">

        </div>

        @if(!empty(request()->all()) && !request()->isMethod('POST'))
        <div class="modal fade" id="openModal" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitleId">Répartition des etudiants de l'année étude <i style="color:#F00"> {{request()->id_ae}}</i> pour le compte de l'année académique <i style="color:#F00">{{request()->id_ane.' '.request()->id_grpe}}</i></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4">
                        @php
                        $eva=request()->id_eval;
                        $an=request()->id_aa;
                        $fil=request()->id_ae;
                        $rcn=DB::select("SELECT inscriptions.MATRICULE as MATRICULE, nomEtu,prenomsEtu FROM etudiant,`inscriptions` WHERE  (inscriptions.MATRICULE NOT IN (SELECT Matricule FROM `repartir` WHERE datedebut='".request()->Debut."' and datefin='".request()->Fin."'))   AND etudiant.MATRICULE=inscriptions.MATRICULE and Id_Annee=".$an." AND`ID_FILIERE`='".$fil."' AND S_Finance='OUI' ORDER BY nomEtu,prenomsEtu ASC");
                        $nbre=count($rcn);
                        @endphp
                        <form action="{{ route('repartition-salle-post') }}" method="POST">
                            @csrf
                            <i style="color:#F00">Nous avons au total : <b>{{$nbre}} </b> étudiant(e)(s) à répartir</i>
                            <div class="row mt-2">
                                <div class="form-group col-12 col-lg-6">
                                    <label for="salleCompo" class="form-label">Salle de composition</label>
                                    <select name="salleCompo" id="salleCompo" class="form-control form-select">
                                        <option value="">Sélectionner une salle...</option>
                                        @foreach (\App\Models\Salle::all() as $salle)
                                        <option value="{{$salle->codeSalle}}">{{$salle->Lib_Salle}} | {{$salle->nombrePlaceCompo}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-12 col-lg-6">
                                    <label for="salleCompo" class="form-label">Nombre de places prévues</label>
                                    <input type="number" name="pld_Sal" class="form-control" id="salleCompo" max="{{$salle->nombrePlaceCompo}}" min="0" max="{{$nbre}}">
                                </div>
                                <input type="hidden" name="nbre" value="{{$nbre}}" >
                                <input type="hidden" name="anacad" value="{{request()->id_ane.' '.request()->id_grpe}}" >
                                <input type="hidden" name="anetud" value="{{request()->id_ae}}" >
                                <input type="hidden" name="debut" value="{{request()->Debut}}" >
                                <input type="hidden" name="fin" value="{{request()->Fin}}" >
                                <input type="hidden" name="grpee" value="{{request()->id_grpe}}" >
                                <div class="table-responsive mt-4">
                                    <table class="table table-striped datatable">
                                        <thead>
                                            <tr>
                                                <th scope="col">Matricule</th>
                                                <th scope="col">Nom</th>
                                                <th scope="col">Prénom(s)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rcn as $rcnp)
                                            <tr class="">
                                                <td scope="row">
                                                    {{$rcnp->MATRICULE}}
                                                    <input type="hidden" name="Matricule{{$loop->iteration}}" value="{{$rcnp->MATRICULE}}">
                                                </td>
                                                <td>{{$rcnp->nomEtu}}</td>
                                                <td>{{$rcnp->prenomsEtu}}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
</section>
@endsection
@section('js')
<script>
    function afficher(){
        $('#afficher').addClass('card p-4');
        $('#afficher').html('<button class="btn btn-link" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:'/repartitionSalle',
            type:'POST',
            data:$('#afficherSalleCompo').serialize(),
            success:function(response){
                $('#afficher').html(response);
                new simpleDatatables.DataTable(".display", {
                    firstLast: true,
                    lastText:"Fin",
                    firstText:"Début",
                    fixedHeight: true,
                });
            }
        })
    };
    elem = document.querySelector('#periodeCompo');
    const datepicker = new DateRangePicker(elem, {
        autohide:true,
        buttonClass: 'btn',
        todayButton:true,
        todayButtonMode:1,
        allowOneSidedRange:true
    });
    [document.querySelector('#dateDebut'),
    document.querySelector('#dateFin')].forEach(element => {
        element.setAttribute('readonly',true);
    });
    $(document).ready(function() {
        if(window.location.href.indexOf('#openModal') != -1) {
            $('#openModal').modal('show');
        }
    });
    $('#salle').attr('required','true')
    $('#dateDebut').attr('required','true')
    $('#dateFin').attr('required','true')
</script>
@endsection
