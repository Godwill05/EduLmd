@extends('layout')
@section('title')
ECUEs par semestre
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
    <div class="row">
        <div class="p-4 card">
            <h5 class="fw-bold">Affichage des ECUEs par semestre</h5>
            <form action="{{ route('impression.ecues-semestre') }}" method="POST" id="afficher">
                @csrf
                <div class="row">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="anneeacademique">Année académique</label>
                            <select name="anneeacademique" id="anneeacademique" class="form-select form-control">
                                <option value="">Sélectionner une année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anAc)
                                <option value="{{$anAc->id_Annee_Acad}}">{{$anAc->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="semestre">Semestre d'étude</label>
                            <select name="semestre" id="semestre" class="form-select form-control">
                                <option value="">Sélectionner un semestre...</option>
                                @foreach (\App\Models\SemestreEtude::all() as $semestreEtude)
                                <option value="{{$semestreEtude->id_S_Gr_Spe}}">{{$semestreEtude->Nom_Classe}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <input class="btn btn-primary" type="button" value="Afficher" id="affi">
                    <input class="btn btn-dark" value="Imprimer" type="submit">
                </div>
            </form>
        </div>
        <div class="display-es">

        </div>
    </div>
</div>
</section>
@endsection
@section('js')
<script>
    $('#affi').on('click',function(){
        $('.display-es').addClass('card p-4');
        $('.display-es').html('<button class="btn btn-link" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:"{{route('afficherECUEsSemestre')}}",
            type:'POST',
            data:$('#afficher').serialize(),
            success:function(response){
                $('.display-es').html(response);
                const dataTable = new simpleDatatables.DataTable("#tableD", {
                    firstLast: true,
                    lastText:"Fin",
                    firstText:"Début",
                    fixedHeight: true,
                })
            }
        });
    });
</script>
@endsection
