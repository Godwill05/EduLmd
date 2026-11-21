@extends('layout')
@section('title')
Modifier évaluation
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
            <div class="card p-4">
                <form action="{{ route('evaluations.update',$evaluation->numeroEval) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label class="form-label" for="anneeEtude">Année d'étude</label>
                                <label class="form-control disabled">
                                    {{$evaluation->ueSem->semestre->anneeEtudeSemestres->first()->Code_Annee_Etude}}
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label class="form-label" for="anneeAcademique">Année académique</label>
                                <label class="form-control disabled">
                                    {{$evaluation->ueSem->anneeacademique->ANNEE_ACAD}}
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label class="form-label" for="semestreEtude">Semestre d'étude</label>
                                <label class="disabled form-control">
                                    {{$evaluation->ueSem->semestre->Nom_Classe}}
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-6 col-12 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label class="form-label" for="ue">UE</label>
                                <select name="ue" id="ue" class="form-control ue">
                                    <option value="">Sélectionner l'UE...</option>
                                    @foreach (\App\Models\AttrUeClasse::where('id_S_Gr_Spe',$evaluation->ueSem->semestre->id_S_Gr_Spe)->where('id_Annee_Acad',$evaluation->ueSem->anneeacademique->id_Annee_Acad)->get() as $ue)
                                    <option value="{{$ue->Id_Attr_ue_Sem}}" @selected($ue->Id_Attr_ue_Sem==$evaluation->Id_Attr_ue_Sem)>{{$ue->ue->libelleUe}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label class="form-label" for="ecue">ECUE</label>
                                <select name="ecue" id="ecue" class="form-control ecue">
                                    <option value="">Sélectionner l'ECUE...</option>
                                    @foreach (\App\Models\AttrEcueUe::where('Id_Attr_ue_Sem',$evaluation->Id_Attr_ue_Sem)->get() as $ecue)
                                    <option value="{{$ecue->codeEcu}}" @selected($evaluation->codeEcu==$ecue->codeEcu)>{{$ecue->ecue->libelleEcu}}</option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="requiredUe">Choisissez d'abord l'UE !</div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label for="date" class="form-label">Date</label>
                                <input type="text" class="form-control" name="date" id="date" value={{$evaluation->dateEval}} placeholder="01/01/1970" readonly>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label for="type" class="form-label">Type d'évaluation</label>
                                <select name="type" id="type" class="form-control form-select">
                                    <option value="">Sélectionner le type...</option>
                                    @foreach (DB::select("SELECT * FROM typeevaluation") as $typeEvaluation)
                                    <option value="{{$typeEvaluation->codTypEval}}" @selected($typeEvaluation->codTypEval==$evaluation->typeEvaluation->codTypEval)>{{$typeEvaluation->libTypEval}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label for="heureDebut" class="form-label">Heure début</label>
                                <input type="time" name="heureDebut" id="heureDebut" class="form-control" value="{{$evaluation->heureDebutEval}}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label for="duree" class="form-label">Durée (en h)</label>
                                <input type="number" name="duree" id="duree" class="form-control" min="0" value="{{$evaluation->dureeEval}}">
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
                    </div>
                    <div class="mt-4 text-end">
                        <button class="btn btn-primary" type="submit">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
@section('js')
<script>
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
</script>
@endsection
