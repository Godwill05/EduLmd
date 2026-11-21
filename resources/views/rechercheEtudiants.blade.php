{{--
    Recherche d'étudiants — cible de la route /dEtu.

    Cette vue manquait : la route existait mais Laravel répondait
    « View [rechercheEtudiants] not found » (erreur 500).
    Elle reprend la présentation des autres écrans de recherche du projet
    (rechercheEvaluations, rechercheAttrEcueUe).
--}}
<form action="" id="recher">
    <div class="clearfix mb-4">
        <div class="float-end">
            <button class="btn btn-dark" type="button" onclick="rechercheEtudiants()">Valider</button>
            <button class="btn btn-secondary" type="button" onclick="location.reload()">Voir tous les étudiants</button>
        </div>
        <div class="float-start">
            <input type="search" name="recherche" id="recherche" class="form-control" placeholder="Rechercher">
        </div>
    </div>
</form>

<div class="mb-3 text-[0.8rem] text-encre-muted">
    {{ $items->count() }} étudiant(s) affiché(s)
</div>

<table class="table table-striped" id="tableD">
    <thead>
        <tr>
            <th>Matricule</th>
            <th>Nom et prénoms</th>
            <th>Sexe</th>
            <th>Naissance</th>
            <th>Contact</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $etudiant)
            <tr>
                <td class="align-middle">{{ $etudiant->MATRICULE }}</td>
                <td class="align-middle">
                    <strong>{{ $etudiant->nomEtu }}</strong>
                    {{ $etudiant->prenomsEtu }}
                </td>
                <td class="align-middle">{{ $etudiant->sexeEtu === 'M' ? 'Masculin' : ($etudiant->sexeEtu === 'F' ? 'Féminin' : '—') }}</td>
                <td class="align-middle">{{ $etudiant->dateNaisEtu ?: '—' }}</td>
                <td class="align-middle">
                    @if ($etudiant->telEtu)
                        <span class="block text-[0.78rem]">{{ $etudiant->telEtu }}</span>
                    @endif
                    @if ($etudiant->emailEtu)
                        <span class="block text-[0.72rem] text-encre-muted">{{ $etudiant->emailEtu }}</span>
                    @endif
                    @if (!$etudiant->telEtu && !$etudiant->emailEtu)
                        —
                    @endif
                </td>
                <td class="text-nowrap">
                    <a class="btn" title="Voir la fiche"
                       href="{{ route('etudiants.fiche', ['matricule' => $etudiant->MATRICULE]) }}">
                        <i class="ri-file-user-line"></i>
                    </a>
                    <a class="btn" title="Paiements"
                       href="{{ route('paiements.index') }}?matricule={{ urlencode($etudiant->MATRICULE) }}">
                        <i class="ri-hand-coin-line"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-encre-muted">
                    Aucun étudiant ne correspond à la recherche.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@section('js')
<script>
    function rechercheEtudiants() {
        var terme = document.getElementById('recherche').value;
        window.location.href = '{{ url('/dEtu') }}?recherche=' + encodeURIComponent(terme);
    }
</script>
@endsection
