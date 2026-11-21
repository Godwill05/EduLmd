@extends('layout')
@section('title')
Modifier attribution ECUE
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">
        @if (session()->has('succes'))
        <div class="row">
            <div class="alert alert-success">
                <h5 class="alert-heading"><i class="bx bx-check-circle"></i> Succès</h5>
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
        <div class="p-4 card">
            <h5 class="fw-bold">Modification d'une attribution d'ECUE</h5>
            <form method="POST" action="{{ route('attribuer-ecue-ue.update') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ueSemestre">Unité d'Enseignement du Semestre <span class="text-danger">*</span></label>
                            <select name="ueSemestre" id="ueSemestre" class="form-control form-select">
                                <option value="">Séléctionner une UE...</option>
                                @foreach (\App\Models\AttrUeClasse::all() as $ueSemestre)
                                <option value="{{$ueSemestre->Id_Attr_ue_Sem}}" @selected($item->ueSemestre->Id_Attr_ue_Sem==$ueSemestre->Id_Attr_ue_Sem)>{{$ueSemestre->ue->libelleUe}} | {{$ueSemestre->semestre->Nom_Classe}} | {{$ueSemestre->anneeacademique->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ecue">Elément Constitutif de l'Unité <span class="text-danger">*</span></label>
                            <select name="ecue" id="ecue" class="form-control form-select">
                                <option value="">Séléctionner une ECUE...</option>
                                @foreach (\App\Models\Ecue::all() as $ecue)
                                <option value="{{$ecue->codeEcu}}" @selected($ecue->codeEcu==$item->ecue->codeEcu)>{{$ecue->libelleEcu}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="enseignant">Enseignant <span class="text-danger">*</span></label>
                            <select name="enseignant" id="enseignant" class="form-control form-select">
                                <option value="">Sélectionner un enseignant...</option>
                                @foreach (\App\Models\Enseignant::all() as $professeur)
                                <option value="{{$professeur->numeroProf}}" @selected($item->professeur->numeroProf==$professeur->numeroProf)>{{$professeur->nomProf}} {{$professeur->prenomsProf}} - Tél : {{$professeur->telProf}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="modeEvaluation">Mode d'évaluation <span class="text-danger">*</span></label>
                            <select name="modeEvaluation" id="modeEvaluation" class="form-control form-select">
                                <option value="">Sélectionner un mode d'évalution...</option>
                                @foreach (\App\Models\ModeEvaluation::all() as $modeEvaluation)
                                <option value="{{$modeEvaluation->idMode}}" @selected($modeEvaluation->idMode==$item->Eva_Mode)>{{$modeEvaluation->lib_mode}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="pedagogique">Matière pédagogique <span class="text-danger">*</span></label>
                            <select name="pedagogique" id="pedagogique" class="form-control form-select">
                                <option value="0" @selected($item->pedagogique==0)>Non</option>
                                <option value="1" @selected($item->pedagogique==1)>Oui</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ponderation">Pondération (en %) <span class="text-danger">*</span></label>
                            <input type="number" name="ponderation" id="ponderation" class="form-control" placeholder="ex:50" max="100" min="0" value="{{old('ponderation',$item->Ponderation*100)}}" step="0.01">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="poidsTpDevoir">Poids TP - Devoir (en %) <span class="text-danger">*</span></label>
                            <input type="number" name="poidsTpDevoir" id="poidsTpDevoir" class="form-control" placeholder="25" max="100" min="0" value="{{old('poidsTpDevoir',$item->PoidsDev*100)}}" step="0.01">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="poidsPartiel">Poids Partiel (en %) <span class="text-danger">*</span></label>
                            <input type="number" name="poidsPartiel" id="poidsPartiel" class="form-control" placeholder="75" max="100" min="0" value="{{old('poidsPartiel',$item->PoidsPart*100)}}" step="0.01">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="seuilEcue">Seuil ECUE <span class="text-danger">*</span></label>
                            <input type="number" name="seuilEcue" id="seuilEcue" class="form-control" placeholder="12" value="{{old('seuilEcue',$item->Seuil_Ecu)}}" min="0" max="20">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="masseHoraire">Masse Horaire <span class="text-danger">*</span></label>
                            <input type="number" name="masseHoraire" id="masseHoraire" class="form-control" placeholder="30" min="0" value="{{old('masseHoraire',$item->MasseHoraire)}}">
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="observations">Observations</label>
                            <textarea name="observations" id="observations" class="form-control" placeholder="Aucune">{{old('observations',$item->Observation)}}</textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary">Modifier</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
