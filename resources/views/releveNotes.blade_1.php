@extends('layout')
@section('title')
Relevé de notes par semestre
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
                <form action="{{ route('impression.releveNotes') }}" method="POST" id="recapUes" class="needs-validation" novalidate target="_blank">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label class="form-label" for="anneeEtude">Année d'étude</label>
                                <select name="anneeEtude" id="anneeEtude" class="form-control @error('anneeEtude') is-invalid @enderror form-control-sm form-select">
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
                                <select name="anneeAcademique" id="anneeAcademique" class="form-control @error('anneeAcademique') is-invalid @enderror form-control-sm form-select" onchange="loadEtudiantAnac()">
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
                                <label class="form-label" for="etudiant">Etudiant</label>
                                <select name="etud" id="etudiant" class="etudiant form-control @error('etudiant') is-invalid @enderror form-control-sm" disabled>
                                    <option value="">Sélectionner l'étudiant...</option>
                                </select>
                                <div class="form-text" id="requiredAnacAnEtd">Choisissez d'abord l'année académique et l'année d'étude!</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <input type="submit" class="btn btn-primary" value="Imprimer" name="btn"/>
                        <input type="submit" class="btn btn-primary" name="btn" value="Imprimer relevé annuel"/>
                    </div>
                </form>
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
        if ($('#anneeAcademique').val()=="") {
            $('#requiredAnacAnEtd').html('Choisissez d\'abord l\'année académique !');
        }
        else{
            $.ajax({
                url:"/api/ins/"+$('#anneeAcademique').val()+"/"+$('#anneeEtude').val(),
                type:'GET',
                success:function(response){
                    $('#requiredSemAnacAnEtd').html('');
                    $('#etudiant').removeAttr('disabled');
                    $('.etudiant').html($('<option>', {
                        value: "",
                        text: "Sélectionner l'étudiant..."
                    }));
                    response.forEach(element => {
                        $('.etudiant').append($('<option>', {
                            value: element.MATRICULE,
                            text: element.nomEtu+' '+element.prenomsEtu
                        }));

                    });
                    $('.etudiant').addClass('form-select');
                }
            })
        }
    });
    function loadEtudiantAnac(){
        if ($('#anneeEtude').val()=="") {
            $('#requiredAnacAnEtd').html('Choisissez d\'abord l\'année d\'étude !');
        }
        else{
            $.ajax({
                url:"/api/ins/"+$('#anneeAcademique').val()+"/"+$('#anneeEtude').val(),
                type:'GET',
                success:function(response){
                    $('#requiredAnacAnEtd').html('');
                    $('#etudiant').removeAttr('disabled');
                    $('.etudiant').html($('<option>', {
                        value: "",
                        text: "Sélectionner l'étudiant..."
                    }));
                    response.forEach(element => {
                        $('.etudiant').append($('<option>', {
                            value: element.MATRICULE,
                            text: element.nomEtu+' '+element.prenomsEtu
                        }));

                    });
                    $('.etudiant').addClass('form-select');
                }
            })
        }
    };
</script>
@endsection
