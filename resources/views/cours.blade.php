@extends('layout')
@section('title')
Cours
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
            <form action="{{ route('cours.store') }}" method="POST">
                @csrf
                <div class="row g-3">
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
                            <label for="dateDebut" class="form-label">Période de cours</label>
                            <div class="input-group" id="periodeCours">
                                <input type="text" name="dateDebut" id="dateDebut" class="form-control" readonly placeholder="01-01-1970">
                                <span class="input-group-text">à</span>
                                <input type="text" name="dateFin" id="dateFin" class="form-control" readonly placeholder="01-01-1970">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="salle" class="form-label">Salle</label>
                            <select name="salle" id="salle" class="form-control form-select">
                                <option value="">Sélectionner une salle...</option>
                                @foreach (DB::select("select * from salle") as $salle)
                                <option value="{{$salle->codeSalle}}">{{$salle->Lib_Salle}} || {{$salle->nombrePlaceCours}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="heureDebut" class="form-label">Heure de début</label>
                            <input type="time" name="heureDebut" id="heureDebut" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="heureFin" class="form-label">Heure de fin</label>
                            <input type="time" name="heureFin" id="heureFin" class="form-control">
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
                    <button type="button" class="btn btn-primary" data-bs-toggle='modal' data-bs-target="#modalevaL">Imprimer</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle='modal' data-bs-target="#modalevaLAE">Imprimer par année d'étude</button>
                </div>
            </form>
            <div class="modal fade" id="modalevaL" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog " role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Spécifier la période de cours</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-4 px-4">
                            <form action="{{ route('impression.cours') }}" method="POST">
                                @csrf
                                <div class="col-lg-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="dateDebut" class="form-label">Période de cours</label>
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
                            <h5 class="modal-title" id="modalTitleId">Spécifier la période de cours</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-4 px-4">
                            <form action="{{ route('impression.cours') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label for="dateDebut" class="form-label">Période de cours</label>
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
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Classe</th>
                    <th>UE / ECUE</th>
                    <th>Enseignant</th>
                    <th>Période</th>
                    <th>Plage Horaire</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $cours)
                        <tr>
                            <td>{{$cours->Code_Annee_Etude}} / {{$cours->Nom_Classe}} {{$cours->ANNEE_ACAD}}</td>
                            <td>{{$cours->libelleUe}} / {{$cours->libelleEcu}}</td>
                            <td>{{$cours->nomProf}} {{$cours->prenomsProf}}<br>Tel : {{$cours->telProf}}</td>
                            <td>Du {{$cours->datedebut}} au {{$cours->datefin}}</td>
                            <td>De {{$cours->heureDebutCours}} à {{$cours->heureFinCours}}</td>
                            <td>
                                <form action="{{ route('cours.destroy', $cours->numeroProg) }}" method="post">
                                    @csrf
                                    @method('DELETE')

                                    <a class="btn" href="{{ route('cours.edit', $cours->numeroProg) }}"><i class=" ri-pencil-line text-secondary"></i></a>

                                    <button type="submit"  onclick="return confirm('Etes vous sûr de vouloir supprimer ce cours?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                </form>
                            </td>
                        </tr>

                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>
@endsection
@section('js')
<script>
    elem = document.querySelector('#periodeCours');
    const datepicker = new DateRangePicker(elem, {
        autohide:true,
        buttonClass: 'btn',
        todayButton:true,
        todayButtonMode:1,
        allowOneSidedRange:true
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
    elem1 = document.querySelector('#periodeCompo');
    const datepicker1 = new DateRangePicker(elem1, {
        autohide:true,
        buttonClass: 'btn',
        todayButton:true,
        todayButtonMode:1,
        allowOneSidedRange:true
    });
    elem2 = document.querySelector('#periodeCompo1');
    const datepicker2 = new DateRangePicker(elem2, {
        autohide:true,
        buttonClass: 'btn',
        todayButton:true,
        todayButtonMode:1,
        allowOneSidedRange:true
    });
</script>
@endsection
