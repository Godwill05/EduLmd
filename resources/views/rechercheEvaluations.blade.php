@extends('layout')

@section('title', 'Évaluations')

@section('content')
<form action="" id="recher">
    <div class="clearfix mb-4">
        <div class="float-end">
            <button class="btn btn-dark" type="button" onclick="rechercheEvaluation()">Valider</button>
            <button class="btn btn-secondary" type="button" onclick="location.reload()">Voir toutes les évaluations</button>
        </div>
        <div class="float-start">
            <input type="search" name="recherche" id="recherche" class="form-control" placeholder="Rechercher">
        </div>
    </div>
</form>
<table class="table table-striped" id="tableD">
    <thead>
        <th>Classe</th>
        <th>UE / ECUE</th>
        <th>Date et heure</th>
        <th>Enseignant</th>
        <th>Nature</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @foreach ($items1 as $evaluation)
        <tr>
            <td>{{ optional(optional(optional($evaluation->ueSem)->semestre)->anneeEtudeSemestres)->first()->anneeEtude->Code_Annee_Etude ?? 'Non disponible'}} / {{ optional(optional($evaluation->ueSem)->semestre)->Nom_Classe ?? 'Non disponible'}} {{ optional(optional($evaluation->ueSem)->anneeacademique)->ANNEE_ACAD ?? 'Non disponible'}}</td>
            <td>{{ optional(optional($evaluation->ueSem)->ue)->libelleUe ?? 'Non disponible'}} / {{$evaluation->ecue->libelleEcu ?? 'Non disponible'}}</td>
            <td>{{$evaluation->dateEval}} de {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->format("H:i")}} à {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->addHours($evaluation->dureeEval)->format("H:i")}}</td>
            <td>
                @if ($evaluation->ecue && $evaluation->ueSem)
                @php $lienEnseignant = $evaluation->attrEcueUe($evaluation->ueSem->Id_Attr_ue_Sem, $evaluation->ecue->codeEcu); @endphp
                {{ optional(optional($lienEnseignant)->professeur)->nomProf ?? 'Non disponible'}} {{ optional(optional($lienEnseignant)->professeur)->prenomsProf ?? 'Non disponible'}}<br>Tel : {{ optional(optional($lienEnseignant)->professeur)->telProf ?? 'Non disponible'}}
                @else
                {{'Non disponible'}}
                @endif
            </td>
            <td>{{ optional($evaluation->typeEvaluation)->libTypEval ?? 'Non disponible'}}</td>
            <td>
                <form action="{{ route('evaluations.destroy', $evaluation->numeroEval) }}" method="post">
                    @csrf
                    @method('DELETE')

                    <div class="dropstart">
                        <button
                            class="btn btn-dark dropdown-toggle"
                            type="button"
                            id="triggerId"
                            data-bs-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false"
                        >
                            Voir les actions
                        </button>
                        <div class="dropdown-menu" aria-labelledby="triggerId">
                            <a title="Insérer notes" href="?id_eval={{$evaluation->numeroEval}}&id_annee={{optional(optional($evaluation->ueSem)->anneeacademique)->ANNEE_ACAD ?? ''}}&id_an_etude={{optional(optional(optional($evaluation->ueSem)->semestre)->anneeEtudeSemestres)->first()->anneeEtude->Code_Annee_Etude ?? ''}}#modalNote" target="_blank" class="dropdown-item">Insérer notes</a>
                            <a title="Importer notes via Excel" href="?id_eval={{$evaluation->numeroEval}}&id_annee={{optional(optional($evaluation->ueSem)->anneeacademique)->ANNEE_ACAD ?? ''}}&id_an_etude={{optional(optional(optional($evaluation->ueSem)->semestre)->anneeEtudeSemestres)->first()->anneeEtude->Code_Annee_Etude ?? ''}}#importNote" target="_blank" class="dropdown-item">Importer les notes format Excel</a>
                            <a title="Exporter les notes format Excel" href="{{route('export-notes')}}?id_eval={{$evaluation->numeroEval}}&id_annee={{optional(optional($evaluation->ueSem)->anneeacademique)->ANNEE_ACAD ?? ''}}&id_an_etude={{optional(optional(optional($evaluation->ueSem)->semestre)->anneeEtudeSemestres)->first()->anneeEtude->Code_Annee_Etude ?? ''}}" class="dropdown-item">Exporter les notes format Excel</a>
                            <a class="dropdown-item" href="{{ route('evaluations.edit', $evaluation->numeroEval) }}">Modifier l'évaluation</a>

                            <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer cette évaluation?')" class="btn">Supprimer l'évaluation</button>
                        </div>
                    </div>

                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if (method_exists($items1, 'links') && $items1->hasPages())
    <div class="mt-4">
        @include('partials.pagination', ['paginateur' => $items1])
    </div>
@endif
@endsection
