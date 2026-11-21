@extends('layout')
@section('title')
Modifier attribution UE
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
            <h5 class="fw-bold">Modification d'une attribution d'UE</h5>
            <form method="POST" action="{{ route('attribuer-ue-semestre.update',$item->Id_Attr_ue_Sem) }}">
                @csrf
                @method('PATCH')
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeAcademique">Année académique <span class="text-danger">*</span></label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                <option value="">Séléctionner une année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anAc)
                                <option value="{{$anAc->id_Annee_Acad}}" @selected($anAc->id_Annee_Acad==$item->id_Annee_Acad)>{{$anAc->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="importanceUe">Importance de l'UE <span class="text-danger">*</span></label>
                            <select name="importanceUe" id="importanceUe" class="form-control form-select">
                                <option value="">Spécifier l'importance de l'UE...</option>
                                @foreach (\App\Models\ConstanteImportance::all() as $constImp)
                                <option value="{{$constImp->idImportance}}" @selected($constImp->idImportance==$item->idImportance)>{{$constImp->libelleImp}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="semestre">Semestre <span class="text-danger">*</span></label>
                            <select name="semestre" id="semestre" class="form-control form-select">
                                <option value="">Spécifier le semestre...</option>
                                @foreach (\App\Models\SemestreEtude::all() as $semestreEtude)
                                <option value="{{$semestreEtude->id_S_Gr_Spe}}" @selected($semestreEtude->id_S_Gr_Spe==$item->id_S_Gr_Spe)>{{$semestreEtude->Nom_Classe}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="credit">Crédit <span class="text-danger">*</span></label>
                            <input type="number" name="credit" id="credit" class="form-control" placeholder="5" value="{{$item->Credit}}">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ue">Unité d'Enseignement <span class="text-danger">*</span></label>
                            <select name="ue" id="ue" class="form-control form-select">
                                <option value="">Séléctionner une UE...</option>
                                @foreach (\App\Models\Ue::all() as $ue)
                                <option value="{{$ue->codeUe}}" @selected($ue->codeUe==$item->codeUe)>{{$ue->libelleUe}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="seuil">Seuil de l'UE <span class="text-danger">*</span></label>
                            <input type="number" name="seuil" id="seuil" class="form-control" placeholder="12" value="{{$item->Seuil_Ue}}">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="categorie">Catégorie de l'UE</label>
                            <select name="categorie" id="categorie" class="form-control form-select">
                                <option value="">Séléctionner une catégorie...</option>
                                @foreach (\App\Models\CategorieUe::all() as $categorieUe)
                                <option value="{{$categorieUe->Id_Cat_Ue}}" @selected($categorieUe->Id_Cat_Ue==$item->Id_Cat_Ue)>{{$categorieUe->Lib_Cat_Ue}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mt-5 text-end">
                    <button type="submit"   class="btn btn-primary">Modifier</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>

            </form>
        </div>
    </div>
</section>
@endsection
