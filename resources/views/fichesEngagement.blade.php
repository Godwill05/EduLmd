@extends('layout')
@section('title')
Fiches d'engagement financier
@endsection
@section('content')
<section class="section">
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class=" ri-checkbox-circle-line me-1"></i> {{ session('succes') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class=" ri-alert-line me-1"></i> {{ session('echec') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="card p-4 shadow-sm border-0 border-top border-primary border-3">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary-subtle text-primary p-2 rounded me-3">
                    <i class=" ri-file-text-line fs-3"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold">Fiches d'engagement financier &amp; Échéanciers</h4>
                    <p class="text-muted small mb-0">Imprimez les fiches d'engagement financier en 2 volets détachables (Établissement &amp; Étudiant).</p>
                </div>
            </div>

            <!-- Formulaire de recherche et filtrage -->
            <form action="{{ route('engagement.index') }}" method="GET" class="border rounded p-3 bg-light-subtle mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-4 col-sm-12">
                        <label class="form-label fw-bold" for="anneeAcademique">Année académique <span class="text-danger">*</span></label>
                        <select name="anneeAcademique" id="anneeAcademique" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($annees as $annee)
                            <option value="{{ $annee->id_Annee_Acad }}" {{ $selectedAnnee == $annee->id_Annee_Acad ? 'selected' : '' }}>
                                {{ $annee->ANNEE_ACAD }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-4 col-sm-12">
                        <label class="form-label fw-bold" for="anneeEtude">Filière / Classe</label>
                        <select name="anneeEtude" id="anneeEtude" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Toutes les filières</option>
                            @foreach ($anneesEtudes as $ae)
                            <option value="{{ $ae->Code_Annee_Etude }}" {{ $selectedFiliere == $ae->Code_Annee_Etude ? 'selected' : '' }}>
                                {{ $ae->Libelle_AnEtude }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-4 col-sm-12">
                        <label class="form-label fw-bold" for="recherche">Recherche rapide</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="recherche" id="recherche" class="form-control" placeholder="Nom, prénom ou matricule..." value="{{ $search }}">
                            <button type="submit" class="btn btn-primary"><i class=" ri-search-line"></i></button>
                            @if ($search || $selectedFiliere)
                            <a href="{{ route('engagement.index', ['anneeAcademique' => $selectedAnnee]) }}" class="btn btn-outline-secondary" title="Réinitialiser filtres">
                                <i class=" ri-close-line"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if ($inscriptions->isNotEmpty())
            <!-- Barre d'outils et boutons d'impression globale -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 p-3 bg-light rounded border">
                <div>
                    <span class="badge bg-primary fs-7 me-1">
                        <i class=" ri-team-line me-1"></i>{{ $inscriptions->count() }} étudiant(s) affiché(s)
                    </span>
                    @if ($selectedFiliere)
                    <span class="badge bg-secondary fs-7 me-1">
                        {{ $anneesEtudes->where('Code_Annee_Etude', $selectedFiliere)->first()?->Libelle_AnEtude }}
                    </span>
                    @endif
                    <span class="text-muted small">
                        (Total année : <b>{{ $totalAnnee }}</b> inscrits)
                    </span>
                    <div class="text-muted small mt-1">
                        <i class=" ri-information-line text-primary me-1"></i> Les boutons d'impression génèrent la <b>totalité</b> des étudiants en base (indépendamment de la pagination de 25 par page du tableau).
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if ($selectedFiliere)
                    <!-- Bouton pour imprimer toute la classe (sans pagination) -->
                    <form action="{{ route('engagement.imprimer-classe') }}" method="POST" target="_blank" class="d-inline">
                        @csrf
                        <input type="hidden" name="anneeAcademique" value="{{ $selectedAnnee }}">
                        <input type="hidden" name="anneeEtude" value="{{ $selectedFiliere }}">
                        <input type="hidden" name="mode_impression" value="tous">
                        <button type="submit" class="btn btn-sm btn-dark" title="Imprime l'intégralité des fiches de cette classe sans limite de pagination">
                            <i class=" ri-printer-fill me-1"></i> Imprimer TOUTE la classe ({{ $inscriptions->count() }} fiches)
                        </button>
                    </form>
                    @endif

                    <!-- Bouton pour imprimer TOUS les étudiants de l'année -->
                    <form action="{{ route('engagement.imprimer-classe') }}" method="POST" target="_blank" class="d-inline">
                        @csrf
                        <input type="hidden" name="anneeAcademique" value="{{ $selectedAnnee }}">
                        <input type="hidden" name="anneeEtude" value="">
                        <input type="hidden" name="mode_impression" value="tous">
                        <button type="submit" class="btn btn-sm btn-primary" title="Imprime toutes les fiches d'engagement de tous les étudiants de l'année académique">
                            <i class=" ri-stack-fill me-1"></i> Imprimer TOUTE l'année ({{ $totalAnnee }} fiches)
                        </button>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle" id="tableEngagement" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Matricule</th>
                            <th>Nom &amp; Prénoms</th>
                            <th>Filière</th>
                            <th>Scolarité annuelle</th>
                            <th>Échéancier</th>
                            <th>Déjà versé</th>
                            <th>Reste dû</th>
                            <th class="text-end">Fiche d'engagement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inscriptions as $ins)
                        @php
                            $nbPrevs = \App\Models\Prevision::where('MATRICULE', $ins->MATRICULE)->where('Id_Annee', $ins->Id_Annee)->count();
                            $cumul = $ins->paiements()->sum('montantPaye');
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $ins->MATRICULE }}</td>
                            <td class="fw-semibold">
                                {{ $ins->etudiant ? $ins->etudiant->fullname() : 'N/A' }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $ins->anneeEtude ? $ins->anneeEtude->Libelle_AnEtude : $ins->ID_FILIERE }}
                                </span>
                            </td>
                            <td>{{ number_format($ins->frais_paye, 0, ',', ' ') }} F CFA</td>
                            <td>
                                @if ($nbPrevs > 0)
                                    <span class="badge bg-info-subtle text-info border border-info">
                                        <i class=" ri-calendar-check-line me-1"></i>{{ $nbPrevs }} tranche(s)
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border">
                                        Standard (3 tranches)
                                    </span>
                                @endif
                            </td>
                            <td class="text-success fw-bold">{{ number_format($cumul, 0, ',', ' ') }} F</td>
                            <td class="fw-bold {{ $ins->reste_paye > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($ins->reste_paye, 0, ',', ' ') }} F
                            </td>
                            <td class="text-end">
                                <a href="{{ route('engagement.imprimer', ['matricule' => $ins->MATRICULE, 'annee' => $ins->Id_Annee, 'filiere' => $ins->ID_FILIERE]) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Imprimer la fiche d'engagement en 2 volets">
                                    <i class=" ri-printer-line me-1"></i> Imprimer
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="alert alert-info text-center py-4 my-3">
                <i class=" ri-inbox-line fs-2 d-block mb-2"></i>
                Aucun étudiant trouvé pour l'année <strong>{{ $annees->where('id_Annee_Acad', $selectedAnnee)->first()?->ANNEE_ACAD }}</strong>.
            </div>
            @endif
        </div>
    </div>
</div>
</section>
@endsection

@section('js')
<script>
    if (document.querySelector("#tableEngagement")) {
        new simpleDatatables.DataTable("#tableEngagement", {
            perPage: 25,
            perPageSelect: [10, 25, 50, 100]
        });
    }
</script>
@endsection
