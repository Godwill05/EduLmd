@extends('layout')
@section('title')
Attribution ECUE à UE
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
                    <h5 class="fw-bold">Attribuer une ECUE à une UE</h5>
                    <form action="{{ route('attribuer-ecue-ue.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="ueSemestre">Unité d'Enseignement du Semestre <span class="text-danger">*</span></label>
                                    <select name="ueSemestre" id="ueSemestre" class="form-control form-select">
                                        <option value="">Séléctionner une UE...</option>
                                        @foreach (\App\Models\AttrUeClasse::all() as $ueSemestre)
                                        <option value="{{$ueSemestre->Id_Attr_ue_Sem}}">{{$ueSemestre->ue->libelleUe}} | {{$ueSemestre->semestre->Nom_Classe}} | {{$ueSemestre->anneeacademique->ANNEE_ACAD}}</option>
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
                                        <option value="{{$ecue->codeEcu}}">{{$ecue->libelleEcu}}</option>
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
                                        <option value="{{$professeur->numeroProf}}">{{$professeur->nomProf}} {{$professeur->prenomsProf}} - Tél : {{$professeur->telProf}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="modeEvaluation">Mode d'évaluation <span class="text-danger">*</span></label>
                                    <select name="modeEvaluation" id="modeEvaluation" class="form-control form-select">
                                        <option value="">Sélectionner un mode d'évaluation...</option>
                                        @foreach (\App\Models\ModeEvaluation::all() as $modeEvaluation)
                                        <option value="{{$modeEvaluation->idMode}}">{{$modeEvaluation->lib_mode}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="pedagogique">Matière pédagogique <span class="text-danger">*</span></label>
                                    <select name="pedagogique" id="pedagogique" class="form-control form-select">
                                        <option value="0" selected>Non</option>
                                        <option value="1">Oui</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="ponderation">Pondération (en %) <span class="text-danger">*</span></label>
                                    <input type="number" name="ponderation" id="ponderation" class="form-control" placeholder="ex:50" max="100" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="poidsTpDevoir">Poids TP - Devoir (en %) <span class="text-danger">*</span></label>
                                    <input type="number" name="poidsTpDevoir" id="poidsTpDevoir" class="form-control" placeholder="25" max="100" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="poidsPartiel">Poids Partiel (en %) <span class="text-danger">*</span></label>
                                    <input type="number" name="poidsPartiel" id="poidsPartiel" class="form-control" placeholder="75" max="100" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="seuilEcue">Seuil ECUE <span class="text-danger">*</span></label>
                                    <input type="number" name="seuilEcue" id="seuilEcue" class="form-control" placeholder="12" value="12" min="0" max="20">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="masseHoraire">Masse Horaire <span class="text-danger">*</span></label>
                                    <input type="number" name="masseHoraire" id="masseHoraire" class="form-control" placeholder="30" min="0">
                                </div>
                            </div>
                            <div class="col-lg-12 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="observations">Observations</label>
                                    <textarea name="observations" id="observations" class="form-control" placeholder="Aucune"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade p-2" id="importCSV" role="tabpanel" aria-labelledby="profile-tab">
                    <form action="{{ route('importAttrEcueUe') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="csv" id="csv" class="form-control form-control-file mt-3" accept=".csv">
                        <div class="mt-4 text-end">
                          <button class="btn btn-primary">Valider</button>
                          <a href="/modeles/attr_ecue_ue.csv" download class="btn btn-dark">Télécharger un modèle</a>
                        </div>
                    </form>
                </div>
            </div><!-- End Bordered Tabs -->
        </div>
        <div class="card p-4 afficher">
            <form action="" id="recher">
                <div class="clearfix mb-4">
                    <div class="float-end">
                        <button class="btn btn-dark" type="button" onclick="rechercheAttrEcueUe()">Valider</button>
                    </div>
                    <div class="float-start">
                        <input type="text" name="recherche" id="recherche" class="form-control" placeholder="Rechercher" required>
                    </div>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <th>UE</th>
                    <th>ECUE</th>
                    <th>Matière pédagogique</th>
                    <th>Mode d'évaluation</th>
                    <th>Enseignant</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td class="align-middle">{{$item->ueSemestre->ue->libelleUe}} | {{$item->ueSemestre->semestre->Nom_Classe}} | {{$item->ueSemestre->anneeacademique->ANNEE_ACAD}}</td>
                            <td>
                                {{ $item->ecue->libelleEcu }}
                            </td>
                            <td>{{($item->pedagogique==0)?'Non':'Oui'}}</td>
                            <td>{{$item->Eva_Mode}}</td>
                            <td>{{$item->professeur->nomProf}} {{$item->professeur->prenomsProf}}</td>
                            <td class="text-nowrap">


                                    <div class="row">
                                        <div class="col">
                                            <button type="button" class="btn"><i class="bi bi-eye" data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"></i></button>
                                        </div>
                                        <div class="col">
                                            <form action="{{ route('attribuer-ecue-ue.edit') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="uesem" value="{{$item->Id_Attr_ue_Sem}}">
                                                <input type="hidden" name="ecUe" value="{{$item->codeEcu}}">
                                                <button type="submit" class="btn"><i class="bi bi-pencil text-secondary"></i></button>
                                            </form>
                                        </div>
                                        <div class="col">
                                            <form action="{{ route('attribuer-ecue-ue.destroy') }}" method="post">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="uesem" value="{{$item->Id_Attr_ue_Sem}}">
                                                <input type="hidden" name="ecUe" value="{{$item->codeEcu}}">
                                                <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer cette relation entre  l\'UE {{$item->ueSemestre->ue->libelleUe}} et l\'ECUE {{$item->ecue->libelleEcu}}')" class="btn"><i class="bi bi-trash text-danger"></i></button>
                                            </form>
                                        </div>
                                    </div>


                            </td>
                        </tr>
                        <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">Détails</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Année académique : {{$item->ueSemestre->anneeacademique->ANNEE_ACAD}}
                                        <hr>
                                        Semestre : {{$item->ueSemestre->semestre->Nom_Classe}}
                                        <hr>
                                        UE : {{$item->ueSemestre->ue->libelleUe}}
                                        <hr>
                                        ECUE : {{$item->ecue->libelleEcu}}
                                        <hr>
                                        Note minimale : {{$item->Seuil_Ecu}}
                                        <hr>
                                        Pondération : {{$item->Ponderation*100}} %
                                        <hr>
                                        Poids Devoir : {{$item->PoidsDev*100}} %
                                        <hr>
                                        Poids Partiel: {{$item->PoidsPart*100}} %
                                        <hr>
                                        Enseignant responsable : {{$item->professeur->nomProf}} {{$item->professeur->prenomsProf}}
                                        <hr>
                                        Masse horaire : {{$item->MasseHoraire}}
                                        <hr>
                                        Masse horaire exécuté : {{$item->MasseHoraireExec}}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
            {{$items->links()}}
        </div>
    </div>
</div>
</section>
@endsection
@section('js')
<script>
    function rechercheAttrEcueUe(){
        $('.afficher').prepend('<button class="btn btn-link text-purple-500" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:"{{route('afficherAttrEcueUe')}}",
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
