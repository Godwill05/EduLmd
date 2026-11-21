@extends('layout')
@section('title')
Gestion des notes
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
            <form action="{{ route('afficherNotes') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeEtude">Année d'étude</label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-select">
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
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                <option value="">Sélectionner l'année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                <option value="{{$anneeAcademique->id_Annee_Acad}}">{{$anneeAcademique->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="semestreEtude">Semestre d'étude</label>
                            <select name="semestreEtude" id="semestreEtude" class="semestre form-control" disabled>
                                <option value="">Sélectionner le semestre...</option>
                            </select>
                            <div class="form-text" id="requiredAnEtd">Choisissez d'abord une année d'étude !</div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ue">UE</label>
                            <select name="ue" id="ue" class="ue form-control" disabled>
                                <option value="">Sélectionner l'UE...</option>
                            </select>
                            <div class="form-text" id="requiredSemAnac">Choisissez d'abord le semestre et l'année académique !</div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ecue">ECUE</label>
                            <select name="ecue" id="ecue" class="form-control ecue" disabled>
                                <option value="">Sélectionner l'ECUE...</option>
                            </select>
                            <div class="form-text" id="requiredUe">Choisissez d'abord l'UE !</div>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button class="btn btn-primary mt-4">Afficher</button>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="p-4 card">
            <table class="table table-striped datatable">
                <thead>
                    <th>Classe</th>
                    <th>UE / ECUE</th>
                    <th>Enseignant</th>
                    <th>Date et heure</th>
                    <th>Nature</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @isset($evaluations)
                    @foreach ($evaluations as $evaluation)
                        <tr>
                            <td>{{$evaluation->Code_Annee_Etude}} / {{$evaluation->Nom_Classe}} {{$evaluation->ANNEE_ACAD}}</td>
                            <td>{{$evaluation->libelleUe}} / {{$evaluation->libelleEcu}}</td>
                            <td>{{$evaluation->nomProf}} {{$evaluation->prenomsProf}}<br>Tel : {{$evaluation->telProf}}</td>
                            <td>{{$evaluation->dateEval}} de {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->format("H:i")}} à {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->addHours($evaluation->dureeEval)->format("H:i")}}</td>
                            <td>{{$evaluation->libTypEval}}</td>
                            <td>
                                {{-- <form action="/test?id_eval={{$evaluation->numeroEval}}&id_annee={{$evaluation->an}}&id_an_etude={{$evaluation->Code_Annee_Etude}}">
                                    @csrf --}}
                                    <a target="_blank" title="Insérer notes" href="?id_eval={{$evaluation->numeroEval}}&id_annee={{$evaluation->an}}&id_an_etude={{$evaluation->Code_Annee_Etude}}#modalNote" target="_blank" class="btn"><i class="bi bi-list"></i></a>
                                {{-- </form> --}}
                            </td>
                        </tr>
                    @endforeach
                    @endisset
                </tbody>
            </table>
            @if(!empty(request()->all()) && !request()->isMethod('POST'))
            <div class="modal fade" id="modalNote" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Insertion des notes des étudiants de l'année étude <span class="text-danger">{{request()->id_an_etude}}</span> pour le <span class="text-lowercase">{{\App\Models\Evaluation::find(request()->id_eval)->typeEvaluation->libTypEval}}</span> de l'ECUE <span class="text-danger">"{{\App\Models\Evaluation::find(request()->id_eval)->ecue->libelleEcu}}"</span> pour le compte de l'année académique <span class="text-danger">{{\App\Models\AnneeAcademique::find(request()->id_annee)->ANNEE_ACAD}}</span></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="insererNotes">
                                @csrf
                                <input type="hidden" name="numeroEvaluation" value="{{request()->id_eval}}">
                                <table class="table table-striped">
                                    <thead>
                                        <th>Matricule</th>
                                        <th>Nom</th>
                                        <th>Prénoms</th>
                                        <th>Note obtenue</th>
                                        @can('Supprimer les notes')
                                        <th>Actions</th>
                                        @endcan
                                    </thead>
                                    <tbody>
                                        @php
                                            $an=request()->id_annee;
                                            $fil=request()->id_an_etude;
                                            $eva=request()->id_eval
                                        @endphp
                                        @foreach (DB::select("SELECT inscriptions.MATRICULE as MATRICULE, nomEtu,prenomsEtu FROM etudiant,`inscriptions` WHERE etudiant.MATRICULE=inscriptions.MATRICULE and Id_Annee=" . $an . " AND`ID_FILIERE`='" . $fil . "' ORDER BY nomEtu,prenomsEtu ASC") as $inscription)
                                        <tr>
                                            @php
                                                $rcnote=DB::select("SELECT idObtenir,numeroInscription as MATRICULE, numeroEval,Note,loginupdate FROM obtenir WHERE obtenir.numeroInscription='" . $inscription->MATRICULE . "' and obtenir.numeroEval=" . $eva . "")
                                            @endphp
                                            <td>{{$inscription->MATRICULE}}</td>
                                            <td>{{$inscription->nomEtu}}</td>
                                            <td>{{$inscription->prenomsEtu}}</td>
                                            @if(isset($rcnote[0]->idObtenir))
                                            <td><input id="note{{$rcnote[0]->idObtenir}}" type="number" min="0" step="0.01" max="20" class="form-control form-control-sm" value="{{$rcnote[0]->Note ?? ''}}" name="{{$inscription->MATRICULE}}"></td>
                                            @can('Supprimer les notes')
                                            <td id="supprimerNote{{$rcnote[0]->idObtenir}}"><i data-reach="{{$rcnote[0]->idObtenir}}" onclick="supprimerNote({{$rcnote[0]->idObtenir}})" class="btn bi bi-trash text-danger" title="Supprimer note"></i></td>
                                            @endcan
                                            @else
                                            <td><input type="number" min="0" step="0.01" max="20" class="form-control form-control-sm" value="{{$rcnote[0]->Note ?? ''}}" name="{{$inscription->MATRICULE}}"></td>
                                            @can('Supprimer les notes')
                                            <td><span class="bi bi-trash btn disabled"></span></td>
                                            @endcan
                                            @endif
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="button" onclick="$('#insererNotes').submit()" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</section>
@endsection
@section('js')
<script>
    $('#anneeEtude').on('change',function(){
        $.ajax({
            url:"/api/semestre-etude/"+this.value,
            type:'GET',
            success:function(response){
                $('#requiredAnEtd').remove();
                $('.semestre').removeAttr('disabled');
                $('.semestre').html($('<option>', {
                    value: "",
                    text: "Sélectionner le semestre..."
                }));
                response.forEach(element => {
                    $('.semestre').append($('<option>', {
                        value: element.id_S_Gr_Spe,
                        text: element.Nom_Classe
                    }));

                });
                $('.semestre').addClass('form-select');
            }
        })
    });
    $('#anneeAcademique').on('change',function(){
        if ($('#semestreEtude').val()=="") {
            $('#requiredSemAnac').html('Choisissez d\'abord le semestre !');
        }
        else{
            $.ajax({
                url:"/api/ue/"+$('#anneeAcademique').val()+"/"+$('#semestreEtude').val(),
                type:'GET',
                success:function(response){
                    $('#requiredSemAnac').remove();
                    $('#ue').removeAttr('disabled');
                    $('.ue').html($('<option>', {
                        value: "",
                        text: "Sélectionner l'UE..."
                    }));
                    response.forEach(element => {
                        $('.ue').append($('<option>', {
                            value: element.Id_Attr_ue_Sem,
                            text: element.libelleUe
                        }));

                    });
                    $('.ue').addClass('form-select');
                }
            })
        }
    });
    $('#semestreEtude').on('change',function(){
        if ($('#anneeAcademique').val()=="") {
            $('#requiredSemAnac').html('Choisissez d\'abord l\'année académique !');
        }
        else{
            $.ajax({
                url:"/api/ue/"+$('#anneeAcademique').val()+"/"+$('#semestreEtude').val(),
                type:'GET',
                success:function(response){
                    $('#requiredSemAnac').remove();
                    $('#ue').removeAttr('disabled');
                    $('.ue').html($('<option>', {
                        value: "",
                        text: "Sélectionner l'UE..."
                    }));
                    response.forEach(element => {
                        $('.ue').append($('<option>', {
                            value: element.Id_Attr_ue_Sem,
                            text: element.libelleUe
                        }));

                    });
                    $('.ue').addClass('form-select');
                }
            })
        }
    });
    $('#ue').on('change',function(){
        $.ajax({
            url:"/api/ecue/"+this.value,
            type:'GET',
            success:function(response){
                $('#requiredUe').remove();
                $('.ecue').removeAttr('disabled');
                $('.ecue').html($('<option>', {
                    value: "",
                    text: "Sélectionner l'ECUE..."
                }));
                response.forEach(element => {
                    $('.ecue').append($('<option>', {
                        value: element.ecue,
                        text: element.libelle
                    }));

                });
                $('.ecue').addClass('form-select');
            }
        })
    });
    $(document).ready(function() {
        if(window.location.href.indexOf('#modalNote') != -1) {
            $('#modalNote').modal('show');
        }
    });
    @can('Supprimer les notes')
    function supprimerNote(note){
        if(confirm('Voulez-vous vraiment retirer cette note ?')){
            $.ajax({
                url:"/deleteNote/"+note,
                type:'GET',
                success:function(){
                    $('#note'+note).val('');
                    $('#supprimerNote'+note).html('<span class="bi bi-trash btn disabled"></span>');
                }
            })
        }
    }
    @endcan
    $('#insererNotes').on('submit',function(e){
        e.preventDefault();
        $.ajax({
            url:'{{route("notes.insererNotes")}}',
            type:'POST',
            dataType:'json',
            data:$(this).serialize(),
            success:alert('Notes insérées !'),
        });
    });


</script>
@endsection
