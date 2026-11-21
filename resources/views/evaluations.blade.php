@extends('layout')
@section('title')
Evaluations
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
    @if (session()->has('erreurs'))
    <div class="row">
        <div class="alert alert-danger">
            <ul>
                @foreach(session('erreurs') as $er)
                <li>{{$er}}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="p-4 card">
            <form action="{{ route('evaluations.store') }}" method="POST">
                @csrf
                <div class="row g-3">

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $item)
                            <li>{{$item}}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeEtude">Année d'étude</label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-select">
                                <option value="">Sélectionner une année d'étude...</option>
                                @foreach (DB::select("SELECT * FROM annee_etude") as $anneeEtude)
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
                                @foreach (DB::select("SELECT * FROM anneeacademique") as $anneeAcademique)
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
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="date" class="form-label">Date</label>
                            <input type="text" class="form-control" name="date" id="date" placeholder="01/01/1970" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="type" class="form-label">Type d'évaluation</label>
                            <select name="type" id="type" class="form-control form-select">
                                <option value="">Sélectionner le type...</option>
                                @foreach (DB::select("SELECT * FROM typeevaluation") as $typeEvaluation)
                                <option value="{{$typeEvaluation->codTypEval}}">{{$typeEvaluation->libTypEval}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="heureDebut" class="form-label">Heure début</label>
                            <input type="time" name="heureDebut" id="heureDebut" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="duree" class="form-label">Durée (en h)</label>
                            <input type="number" name="duree" id="duree" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="groupePedagogique">Groupe pédagogique</label>
                            <select name="groupePedagogique" id="groupePedagogique" class="form-control form-select">
                                <option value="">Aucun</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                    </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary">Enregistrer</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle='modal' data-bs-target="#modalevaL">Imprimer</button>
                    <button type="button" class="btn btn-dark" data-bs-toggle='modal' data-bs-target="#modalevaLAE">Imprimer par année d'étude</button>
                </div>
            </form>
            <div class="modal fade" id="modalevaL" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog " role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Spécifier la période d'évaluation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-4 px-4">
                            <form action="{{ route('impression.evaluation') }}" method="POST">
                                @csrf
                                <div class="col-lg-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="dateDebut" class="form-label">Période de composition</label>
                                        <div class="input-group" id="periodeCompo">
                                            <input type="text" name="dateDebut" id="dateDebut" class="form-control" readonly placeholder="01-01-1970">
                                            <span class="input-group-text">à</span>
                                            <input type="text" name="dateFin" id="dateFin" class="form-control" readonly placeholder="01-01-1970">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    <input type="submit" name="btn" value="Imprimer" class="btn btn-primary">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="modalevaLAE" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog " role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Spécifier la période d'évaluation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-4 px-4">
                            <form action="{{ route('impression.evaluation') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="dateDebut" class="form-label">Période de composition</label>
                                            <div class="input-group" id="periodeCompo1">
                                                <input type="text" name="dateDebut" id="dateDebut" class="form-control" readonly placeholder="01-01-1970">
                                                <span class="input-group-text">à</span>
                                                <input type="text" name="dateFin" id="dateFin" class="form-control" readonly placeholder="01-01-1970">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="anneeEtude">Année d'étude</label>
                                            <select name="anneeEtude" id="anneeEtude" class="form-control form-select">
                                                <option value="">Sélectionner une année d'étude...</option>
                                                @foreach (DB::select("SELECT * FROM annee_etude") as $anneeEtude)
                                                <option value="{{$anneeEtude->Code_Annee_Etude}}">{{$anneeEtude->Libelle_AnEtude}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="anneeAcademique">Année académique</label>
                                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                                <option value="">Sélectionner l'année académique...</option>
                                                @foreach (DB::select("SELECT * FROM anneeacademique") as $anneeAcademique)
                                                <option value="{{$anneeAcademique->id_Annee_Acad}}">{{$anneeAcademique->ANNEE_ACAD}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    <input type="submit" name="btn" value="Imprimer par année d'étude" class="btn btn-primary">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="card p-4 afficher">
            <form action="" id="recher">
                <div class="clearfix mb-4">
                    <div class="float-end">
                        <button class="btn btn-dark" type="button" onclick="rechercheEvaluation()">Valider</button>
                    </div>
                    <div class="float-start">
                        <input type="text" name="recherche" id="recherche" class="form-control" placeholder="Rechercher" required>
                    </div>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <th>Classe</th>
                    <th>UE / ECUE</th>
                    <th>Date et heure</th>
                    <th>Enseignant</th>
                    <th>Nature</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items1 as $evaluation)
                    <tr>
                        <td>{{$evaluation->ueSem->semestre->anneeEtudeSemestres->first()->anneeEtude->Code_Annee_Etude}} / {{$evaluation->ueSem->semestre->Nom_Classe}} {{$evaluation->ueSem->anneeacademique->ANNEE_ACAD}}</td>
                        <td>{{$evaluation->ueSem->ue->libelleUe}} / {{$evaluation->ecue->libelleEcu}}</td>
                        <td>{{$evaluation->dateEval}} de {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->format("H:i")}} à {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->addHours($evaluation->dureeEval)->format("H:i")}}</td>
                        <td>{{$evaluation->attrEcueUe($evaluation->ueSem->Id_Attr_ue_Sem,$evaluation->ecue->codeEcu)->professeur->nomProf}} {{$evaluation->attrEcueUe($evaluation->ueSem->Id_Attr_ue_Sem,$evaluation->ecue->codeEcu)->professeur->prenomsProf}}<br>Tel : {{$evaluation->attrEcueUe($evaluation->ueSem->Id_Attr_ue_Sem,$evaluation->ecue->codeEcu)->professeur->telProf}}</td>
                        <td>{{$evaluation->typeEvaluation->libTypEval}}</td>
                        <td>
                            <form action="{{ route('evaluations.destroy', $evaluation->numeroEval) }}" method="post">
                                @csrf
                                @method('DELETE')

                                <div class="dropstart">
                                    <button
                                        class="btn btn-dark dropdown-toggle"
                                        type="button"
                                        id="triggerId"
                                        data-bs-toggle="dropdown"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        Voir les actions
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="triggerId">
                                        <a title="Insérer notes" href="?id_eval={{$evaluation->numeroEval}}&id_annee={{$evaluation->ueSem->anneeacademique->ANNEE_ACAD}}&id_an_etude={{$evaluation->ueSem->semestre->anneeEtudeSemestres->first()->anneeEtude->Code_Annee_Etude}}#modalNote" target="_blank" class="dropdown-item">Insérer notes</a>
                                        <a title="Importer notes via Excel" href="?id_eval={{$evaluation->numeroEval}}&id_annee={{$evaluation->ueSem->anneeacademique->ANNEE_ACAD}}&id_an_etude={{$evaluation->ueSem->semestre->anneeEtudeSemestres->first()->anneeEtude->Code_Annee_Etude}}#importNote" target="_blank" class="dropdown-item">Importer les notes format Excel</a>
                                        <a title="Exporter les notes format Excel" href="{{route('export-notes')}}?id_eval={{$evaluation->numeroEval}}&id_annee={{$evaluation->ueSem->anneeacademique->ANNEE_ACAD}}&id_an_etude={{$evaluation->ueSem->semestre->anneeEtudeSemestres->first()->anneeEtude->Code_Annee_Etude}}" class="dropdown-item">Exporter les notes format Excel</a>
                                        <a class="dropdown-item" href="{{ route('evaluations.edit', $evaluation->numeroEval) }}">Modifier l'évaluation</a>

                                        <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer cette évaluation?')" class="btn">Supprimer l'évaluation</button>
                                    </div>
                                </div>

                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{$items1->links()}}
            @empty(!request()->except('page'))
            <div
                class="modal fade"
                id="importNote"
                tabindex="-1"
                data-bs-keyboard="true"

                role="dialog"
                aria-labelledby="modalTitleId"
                aria-hidden="true"
            >
                <div
                    class="modal-dialog modal-dialog-scrollable"
                    role="document"
                >
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">
                                Importer les notes format Excel
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            ></button>
                        </div>
                        <div class="modal-body p-4">
                            <form action="{{ route('import-notes') }}" enctype="multipart/form-data" method="POST">
                                @csrf
                                <input type="file" name="excel" id="excel" class="form-control form-control-file">
                                <input type="hidden" name="id_eval" value="{{request('id_eval')}}">
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-primary">Soumettre</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalNote" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Insertion des notes des étudiants de l'année étude <span class="text-danger">{{request()->id_an_etude}}</span> pour le compte de l'année académique <span class="text-danger">{{request()->id_annee}}</span></h5>
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
                                    </thead>
                                    <tbody>
                                        @php
                                            $an=request()->id_annee;
                                            $ane=\App\Models\AnneeAcademique::where('ANNEE_ACAD',$an)->first()->id_Annee_Acad;
                                            $fil=request()->id_an_etude;
                                            $eva=request()->id_eval
                                        @endphp
                                        @foreach (DB::select("SELECT inscriptions.MATRICULE as MATRICULE, nomEtu,prenomsEtu FROM etudiant,`inscriptions` WHERE etudiant.MATRICULE=inscriptions.MATRICULE and Id_Annee=" . $ane . " AND`ID_FILIERE`='" . $fil . "' ORDER BY nomEtu,prenomsEtu ASC") as $inscription)
                                        <tr>
                                            @php
                                                $rcnote=DB::select("SELECT idObtenir,numeroInscription as MATRICULE, numeroEval,Note,loginupdate FROM obtenir WHERE obtenir.numeroInscription='" . $inscription->MATRICULE . "' and obtenir.numeroEval=" . $eva . "")
                                            @endphp
                                            <td>{{$inscription->MATRICULE}}</td>
                                            <td>{{$inscription->nomEtu}}</td>
                                            <td>{{$inscription->prenomsEtu}}</td>
                                            <td><input type="number" min="0" step="0.01" max="20" class="form-control form-control-sm" value="{{$rcnote[0]->Note ?? ''}}" name="{{$inscription->MATRICULE}}"></td>
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
            @endempty
        </div>
    </div>
</div>
</section>
@endsection
@section('js')
<script>
    const datepicker = new Datepicker(document.querySelector('#date'), {
        autohide:true,
        buttonClass: 'btn',
    });
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
            });
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
        if(window.location.href.indexOf('#importNote') != -1) {
            $('#importNote').modal('show');
        }
    });
    $('#insererNotes').on('submit',function(e){
        e.preventDefault();
        $.ajax({
            url:'{{route("evaluations.insererNotes")}}',
            type:'POST',
            dataType:'json',
            data:$(this).serialize(),
            success:alert('Notes insérées !'),
        });
    });
    elem = document.querySelector('#periodeCompo');
    const datepicker1 = new DateRangePicker(elem, {
        autohide:true,
        buttonClass: 'btn',
        todayButton:true,
        todayButtonMode:1,
        allowOneSidedRange:true
    });
    elem1 = document.querySelector('#periodeCompo1');
    const datepicker2 = new DateRangePicker(elem1, {
        autohide:true,
        buttonClass: 'btn',
        todayButton:true,
        todayButtonMode:1,
        allowOneSidedRange:true
    });
    function rechercheEvaluation(){
        $('.afficher').prepend('<button class="btn btn-link text-purple-500" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:"{{route('afficherEvaluations')}}",
            method:'GET',
            data:$('#recher').serialize(),
            success:function(response){
                $('.afficher').html(response);
                const dataTable = new simpleDatatables.DataTable("#tableD");
            }
        });
    }
</script>
@endsection
