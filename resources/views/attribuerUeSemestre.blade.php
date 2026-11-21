@extends('layout')
@section('title')
UE -> Semestre
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
            <!-- Bordered Tabs -->
            <ul class="nav nav-tabs nav-tabs-bordered" id="borderedTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#enregistrement" type="button" role="tab" aria-controls="home" aria-selected="true">Enregistrement</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#importCSV" type="button" role="tab" aria-controls="profile" aria-selected="false">Importer un fichier csv</button>
                </li>
            </ul>
            <div class="tab-content pt-2" id="borderedTabContent">
                <div class="tab-pane fade show active p-2" id="enregistrement" role="tabpanel" aria-labelledby="home-tab">
                    <h5 class="fw-bold">Attribuer une UE à un semestre</h5>
                    <form action="{{ route('attribuer-ue-semestre.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="anneeAcademique">Année académique <span class="text-danger">*</span></label>
                                    <select name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                        <option value="">Séléctionner une année académique...</option>
                                        @foreach (\App\Models\AnneeAcademique::all() as $anAc)
                                        <option value="{{$anAc->id_Annee_Acad}}">{{$anAc->ANNEE_ACAD}}</option>
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
                                        <option value="{{$constImp->idImportance}}">{{$constImp->libelleImp}}</option>
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
                                        <option value="{{$semestreEtude->id_S_Gr_Spe}}">{{$semestreEtude->Nom_Classe}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="credit">Crédit <span class="text-danger">*</span></label>
                                    <input type="number" name="credit" id="credit" class="form-control" placeholder="5">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="ue">Unité d'Enseignement <span class="text-danger">*</span></label>
                                    <select name="ue" id="ue" class="form-control form-select">
                                        <option value="">Séléctionner une UE...</option>
                                        @foreach (\App\Models\Ue::all() as $ue)
                                        <option value="{{$ue->codeUe}}">{{$ue->libelleUe}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="seuil">Seuil de l'UE <span class="text-danger">*</span></label>
                                    <input type="number" name="seuil" id="seuil" class="form-control" placeholder="12" value="12">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="categorie">Catégorie de l'UE</label>
                                    <select name="categorie" id="categorie" class="form-control form-select">
                                        <option value="">Séléctionner une catégorie...</option>
                                        @foreach (\App\Models\CategorieUe::all() as $categorieUe)
                                        <option value="{{$categorieUe->Id_Cat_Ue}}">{{$categorieUe->Lib_Cat_Ue}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade p-2" id="importCSV" role="tabpanel" aria-labelledby="profile-tab">
                    <form action="{{ route('importAttrUeSemestre') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="csv" id="csv" class="form-control form-control-file mt-3" accept=".csv">
                        <div class="mt-4 text-end">
                          <button class="btn btn-primary">Valider</button>
                          <a href="/modeles/attr_ue_classe.csv" download class="btn btn-dark">Télécharger un modèle</a>
                        </div>
                    </form>
                </div>
            </div><!-- End Bordered Tabs -->
        </div>
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Année académique</th>
                    <th>Semestre</th>
                    <th>UE</th>
                    <th>Note minimale</th>
                    <th>Crédit</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{$item->anneeacademique->ANNEE_ACAD}}</td>
                            <td>{{$item->semestre->Nom_Classe}}</td>
                            <td>
                                Intitulé : {{$item->ue->libelleUe}}<br>
                                Catégorie de l'UE : {{$item->categorieUe->Lib_Cat_Ue}}<br>
                                Importance : {{$item->importance->libelleImp}}<br>
                            </td>
                            <td>{{$item->Seuil_Ue}}</td>
                            <td>{{$item->Credit}}</td>
                            <td class="text-nowrap">
                                <form action="{{ route('attribuer-ue-semestre.destroy',$item->Id_Attr_ue_Sem) }}" method="post">
                                    @csrf
                                    @method('DELETE')

                                    <a class="btn" href="{{ route('attribuer-ue-semestre.edit', $item->Id_Attr_ue_Sem) }}"><i class="bi bi-pencil text-secondary"></i></a>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer cette relation entre  l\'UE {{$item->ue->libelleUe}} et le semestre {{$item->semestre->Nom_Classe}}')" class="btn"><i class="bi bi-trash text-danger"></i></button>
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
