@extends('layout')
@section('title')
Récapitulatif des notes
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
            <form action="{{ route('impression.ue') }}" method="POST" id="recapUes" class="needs-validation" novalidate target="_blank">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeEtude">Année d'étude</label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control @error('anneeEtude') is-invalid @enderror form-control-sm form-select" onchange="loadSemestre()">
                                <option value="">Sélectionner une année d'étude...</option>
                                @foreach (\App\Models\AnneeEtude::all() as $anneeEtude)
                                <option value="{{$anneeEtude->Code_Annee_Etude}}" @selected($anneeEtude->Code_Annee_Etude==old('anneeEtude'))>{{$anneeEtude->Libelle_AnEtude}}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Ce champ est requis !</div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeAcademique">Année académique</label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control @error('anneeAcademique') is-invalid @enderror form-control-sm form-select" onchange="loadUe()">
                                <option value="">Sélectionner l'année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                <option value="{{$anneeAcademique->id_Annee_Acad}}" @selected($anneeAcademique->id_Annee_Acad==old('anneeAcademique'))>{{$anneeAcademique->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Ce champ est requis !</div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="semestreEtude">Semestre d'étude</label>
                            <select name="semestreEtude" id="semestreEtude" class="semestre @error('semestreEtude') is-invalid @enderror form-control form-control-sm" disabled>
                                <option value="">Sélectionner le semestre...</option>
                            </select>
                            <div class="form-text" id="requiredAnEtd">Choisissez d'abord une année d'étude !</div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ue">UE</label>
                            <select name="ue" id="ue" class="ue form-control @error('ue') is-invalid @enderror form-control-sm" disabled>
                                <option value="">Sélectionner l'UE...</option>
                            </select>
                            <div class="form-text" id="requiredSemAnac">Choisissez d'abord le semestre et l'année académique !</div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary" type="button" onclick="afficher()">Afficher</button>
                    <input type="submit" class="btn btn-primary" value="Imprimer" name="btn"/>
                    <input type="submit" class="btn btn-primary" name="btn" value="Imprimer reprises"/>
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
    if($('#anneeEtude').val()!==""){
        loadSemestre();
    }
    if($('#anneeAcademique').val()!==""){
        loadUe();
    }
    function loadSemestre(){
        $.ajax({
            url:"/api/semestre-etude/"+$('#anneeEtude').val(),
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
    };
    function loadUe(){
        if ($('#semestreEtude').val()=="") {
            $('#requiredSemAnac').html('Choisissez d\'abord le semestre !');
        }
        else{
            $.ajax({
                url:"/api/ue/"+$('#anneeAcademique').val()+"/"+$('#semestreEtude').val(),
                type:'GET',
                success:function(response){
                    $('#requiredSemAnac').html('');
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
                    if(document.getElementById('ue').length==1){
                        $('#ue').attr('disabled','true');
                        $('#requiredSemAnac').html('Aucune UE attribuée à ce semestre d\'étude | <a href="/attribuer-ue-semestre">Attribuer</a>');
                    }
                    $('.ue').addClass('form-select');
                }
            })
        }
    };
    $('#semestreEtude').on('change',function(){
        if ($('#anneeAcademique').val()=="") {
            $('#requiredSemAnac').html('Choisissez d\'abord l\'année académique !');
        }
        else if($('#semestreEtude').val()==""){
            $('#requiredSemAnac').html('Choisissez d\'abord le semestre !');
            $('#ue').attr('disabled','true');
        }
        else{
            $.ajax({
                url:"/api/ue/"+$('#anneeAcademique').val()+"/"+$('#semestreEtude').val(),
                type:'GET',
                success:function(response){
                    $('#requiredSemAnac').html('');
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
                    if(document.getElementById('ue').length==1){
                        $('#ue').attr('disabled','true');
                        $('#requiredSemAnac').html('Aucune UE attribuée à ce semestre d\'étude | <a href="/attribuer-ue-semestre">Attribuer</a>');
                    }
                    $('.ue').addClass('form-select');
                }
            })
        }
    });
    function afficher(){
        $('#afficher').addClass('card p-4');
        $('#afficher').html('<button class="btn btn-link" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:'{{route("affichage-recap-ues")}}',
            type:'POST',
            data:$('#recapUes').serialize(),
            success:function(response){
                $('#afficher').html(response);
            }
        });
    }
</script>
@endsection
