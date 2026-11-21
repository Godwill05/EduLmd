@extends('layout')

@section('title', 'Inscriptions')

@section('content')
<table class="table table-striped datatable" id="tableD">
    <thead>
        <th>Matricule</th>
        <th>Nom complet</th>
        <th>Année académique</th>
        <th>Année d'étude</th>
        <th>Reste à payer</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{$item->MATRICULE}}</td>
                <td>{{$item->etudiant ? $item->etudiant->nomEtu . ' ' . $item->etudiant->prenomsEtu : 'N/A'}}</td>
                <td>{{$item->anneeacademique?->ANNEE_ACAD ?? 'N/A'}}</td>
                <td>{{$item->ID_FILIERE}}</td>
                <td>
                    @if($item->reste_paye > 0)
                        <span class="badge bg-warning text-dark">{{number_format($item->reste_paye, 0, ',', ' ')}} F</span>
                    @elseif($item->frais_paye > 0)
                        <span class="badge bg-success">Soldé</span>
                    @else
                        <span class="badge bg-secondary">À configurer</span>
                    @endif
                </td>
                <td class="text-nowrap">
                    <form action="{{ route('inscriptions.destroy') }}" method="post" id="form-del-displayins-{{$loop->iteration}}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn"  data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"><i title="Voir" class=" ri-eye-line"></i></button>
                        @if ($item->reste_paye > 0)
                        <button
                            type="button"
                            class="btn paiement"
                            data-bs-toggle="modal"
                            data-bs-target="#paiement"
                            data-bs-matricule="{{$item->MATRICULE}}"
                            data-bs-anneeAcademique="{{$item->Id_Annee}}"
                            data-bs-anneeEtude="{{$item->ID_FILIERE}}"
                            data-bs-montantPaye="{{$item->reste_paye}}"
                            title="Enregistrer paiement">
                            <i class="text-green-500 ri-hand-coin-line"></i>
                        </button>
                        @elseif($item->frais_paye > 0)
                        <span>Soldé!</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                        <a class="btn" href="{{ route('inscriptions.edit') }}?m={{$item->MATRICULE}}&aa={{$item->Id_Annee}}&ae={{$item->ID_FILIERE}}"><i title="Modifier" class=" ri-pencil-line text-secondary"></i></a>
                        <button type="button" onclick="downloadpdf({{$item->MATRICULE}},{{$item->Id_Annee}},'{{$item->ID_FILIERE}}','{{\App\Models\AnneeAcademique::find($item->Id_Annee)->ANNEE_ACAD}}')" class="btn" title="Carte étudiant"><i class=" ri-id-card-line"></i></button>
                        <a href="{{ route('engagement.imprimer', ['matricule' => $item->MATRICULE, 'annee' => $item->Id_Annee, 'filiere' => $item->ID_FILIERE]) }}" target="_blank" class="btn" title="Fiche d'engagement"><i class=" ri-file-text-line text-primary"></i></a>
                        <input type="hidden" name="matricule" value="{{$item->MATRICULE}}">
                        <input type="hidden" name="anneeAcademique" value="{{$item->Id_Annee}}">
                        <input type="hidden" name="anneeEtude" value="{{$item->ID_FILIERE}}">

                        <button type="button" onclick="confirmDelete(document.getElementById('form-del-displayins-{{$loop->iteration}}'), 'l\'inscription en <strong>{{$item->ID_FILIERE}}</strong> de <strong>{{$item->etudiant ? $item->etudiant->nomEtu . ' ' . $item->etudiant->prenomsEtu : 'l\'étudiant'}}</strong>')" class="btn"><i title="Supprimer" class=" ri-delete-bin-line text-danger"></i></button>
                    </form>
                </td>
            </tr>
            <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">A propos de l'étudiant</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Matricule : {{$item->MATRICULE}}
                            <hr>
                            Nom : {{$item->etudiant->nomEtu}}
                            <hr>
                            Prénom(s) : {{$item->etudiant->prenomsEtu}}
                            <hr>
                            Année académique : {{$item->anneeacademique->ANNEE_ACAD}}
                            <hr>
                            Année d'étude : {{$item->ID_FILIERE}}
                            <hr>
                            Statut : {{$item->statut->LibStatut}}
                            <hr>
                            Groupe pédagogique : {{$item->Group_PDGQ??"Aucun"}}
                            <hr>
                            Scolarité : {{number_format($item->frais_paye, 0, ',', ' ')}} F
                            <hr>
                            Reste à payer : {{number_format($item->reste_paye, 0, ',', ' ')}} F
                            <hr>
                            Situation financière : {{($item->reste_paye <= 0) ? "Inscrit" : "Non inscrit"}}
                            <hr>
                            Observations : {{$item->Observations}}
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
