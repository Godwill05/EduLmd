@extends('layout')

@section('title', 'Attributions ECUE–UE')

@section('content')
<form action="" id="recher">
    <div class="clearfix mb-4">
        <div class="float-end">
            <button class="btn btn-dark" type="button" onclick="rechercheAttrEcueUe()">Valider</button>
            <button class="btn btn-secondary" type="button" onclick="location.reload()">Voir toutes les attributions d'ECUE à UE</button>
        </div>
        <div class="float-start">
            <input type="search" name="recherche" id="recherche" class="form-control" placeholder="Rechercher">
        </div>
    </div>
</form>
<table class="table table-striped" id="tableD">
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
                                <button type="button" class="btn"><i class=" ri-eye-line" data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"></i></button>
                            </div>
                            <div class="col">
                                <form action="{{ route('attribuer-ecue-ue.edit') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="uesem" value="{{$item->Id_Attr_ue_Sem}}">
                                    <input type="hidden" name="ecUe" value="{{$item->codeEcu}}">
                                    <button type="submit" class="btn"><i class=" ri-pencil-line text-secondary"></i></button>
                                </form>
                            </div>
                            <div class="col">
                                <form action="{{ route('attribuer-ecue-ue.destroy') }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="uesem" value="{{$item->Id_Attr_ue_Sem}}">
                                    <input type="hidden" name="ecUe" value="{{$item->codeEcu}}">
                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer cette relation entre  l\'UE {{$item->ueSemestre->ue->libelleUe}} et l\'ECUE {{$item->ecue->libelleEcu}}')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
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

@if (method_exists($items, 'links') && $items->hasPages())
    <div class="mt-4">
        @include('partials.pagination', ['paginateur' => $items])
    </div>
@endif
@endsection
