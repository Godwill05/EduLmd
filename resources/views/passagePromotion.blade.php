@extends('layout')

@section('title')
Passage de promotion des étudiants
@endsection

@section('css')
<style>
    .stat-card {
        border-radius: 10px;
        padding: 15px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .stat-card h3 {
        font-size: 26px;
        margin-bottom: 0;
        font-weight: 700;
    }
    .stat-card p {
        margin-bottom: 0;
        font-size: 13px;
        opacity: 0.9;
    }
    .stat-icon {
        font-size: 36px;
        opacity: 0.8;
    }
</style>
@endsection

@section('content')
<section class="section">
<div class="container-fluid">
    {{-- Messages flash --}}
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('succes') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('echec') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    {{-- En-tête avec bouton retour --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-arrow-up-right-circle text-primary me-2"></i>Passage de promotion des étudiants
                </h4>
                <p class="text-muted mb-0 small">
                    Transfert automatique d'un niveau vers le niveau supérieur (Licence 1 &rarr; Licence 2, Licence 2 &rarr; Licence 3).
                </p>
            </div>
            <div>
                <a href="{{ route('annees-academiques.index') }}" class="btn btn-outline-secondary btn-sm me-1">
                    <i class="bi bi-calendar3 me-1"></i> Années académiques
                </a>
                <a href="{{ route('inscriptions.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-card-checklist me-1"></i> Inscriptions
                </a>
            </div>
        </div>
    </div>

    {{-- Formulaire de sélection des années --}}
    <div class="row">
        <div class="card p-3 shadow-sm mb-3">
            <form method="GET" action="{{ route('passage-promotion.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5 col-md-5 col-12">
                        <label class="form-label fw-semibold" for="source_year">
                            <i class="bi bi-box-arrow-right text-danger me-1"></i> 1. Année académique d'origine (Passée) <span class="text-danger">*</span>
                        </label>
                        <select name="source_year" id="source_year" class="form-select" required>
                            <option value="">Sélectionner l'année source...</option>
                            @foreach ($annees as $a)
                            <option value="{{ $a->id_Annee_Acad }}" {{ (string)$sourceYearId === (string)$a->id_Annee_Acad ? 'selected' : '' }}>
                                {{ $a->ANNEE_ACAD }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-5 col-md-5 col-12">
                        <label class="form-label fw-semibold" for="target_year">
                            <i class="bi bi-box-arrow-in-right text-success me-1"></i> 2. Année académique de destination (Nouvelle) <span class="text-danger">*</span>
                        </label>
                        <select name="target_year" id="target_year" class="form-select" required>
                            <option value="">Sélectionner la nouvelle année...</option>
                            @foreach ($annees as $a)
                            <option value="{{ $a->id_Annee_Acad }}" {{ (string)$targetYearId === (string)$a->id_Annee_Acad ? 'selected' : '' }}>
                                {{ $a->ANNEE_ACAD }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-2 col-12">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Charger
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($sourceYear && $targetYear)
        @if ($sourceYear->id_Annee_Acad == $targetYear->id_Annee_Acad)
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-circle me-1"></i>
                Veuillez sélectionner deux années académiques différentes (ex: 2023-2024 vers 2024-2025).
            </div>
        @else
            {{-- Statistiques --}}
            <div class="row g-3 mb-3">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="stat-card bg-primary shadow-sm">
                        <div>
                            <p>Total inscrits ({{ $sourceYear->ANNEE_ACAD }})</p>
                            <h3>{{ $stats['total_source'] }}</h3>
                        </div>
                        <i class="bi bi-people stat-icon"></i>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="stat-card bg-success shadow-sm">
                        <div>
                            <p>Prêts pour passage en L2 / L3</p>
                            <h3>{{ $stats['eligible'] }}</h3>
                        </div>
                        <i class="bi bi-arrow-up-circle stat-icon"></i>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="stat-card bg-info shadow-sm">
                        <div>
                            <p>Déjà inscrits ({{ $targetYear->ANNEE_ACAD }})</p>
                            <h3>{{ $stats['already_transferred'] }}</h3>
                        </div>
                        <i class="bi bi-check-all stat-icon"></i>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="stat-card bg-secondary shadow-sm">
                        <div>
                            <p>Fin de cycle / Non transférés</p>
                            <h3>{{ $stats['end_of_cycle'] + $stats['no_next_class'] }}</h3>
                        </div>
                        <i class="bi bi-mortarboard stat-icon"></i>
                    </div>
                </div>
            </div>

            {{-- Statut des UEs pour l'année cible --}}
            @if ($ueStats['target_count'] == 0)
            <div class="alert alert-warning border border-warning shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning me-2 align-middle"></i>
                    <strong>Attention : Aucune UE n'est encore attribuée à l'année {{ $targetYear->ANNEE_ACAD }}.</strong>
                    <div class="small text-muted mt-1">
                        Pour que les relevés et récapitulatifs fonctionnent, les UEs ({{ $ueStats['source_count'] }}) et ECUEs de l'année précédente doivent être reconduites.
                    </div>
                </div>
                <form action="{{ route('passage-promotion.reconduire-ues') }}" method="POST">
                    @csrf
                    <input type="hidden" name="source_year" value="{{ $sourceYear->id_Annee_Acad }}">
                    <input type="hidden" name="target_year" value="{{ $targetYear->id_Annee_Acad }}">
                    <button type="submit" class="btn btn-warning fw-bold shadow-sm" onclick="return confirm('Confirmez-vous l\'attribution automatique de toutes les UEs et ECUEs vers {{ $targetYear->ANNEE_ACAD }} ?');">
                        <i class="bi bi-arrow-repeat me-1"></i> Attribuer automatiquement les UEs maintenant ({{ $ueStats['source_count'] }} UEs)
                    </button>
                </form>
            </div>
            @else
            <div class="alert alert-success border border-success shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 py-2">
                <div class="small">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    <strong>Maquette pédagogique prête :</strong> {{ $ueStats['target_count'] }} UEs et {{ $ueStats['target_ecue_count'] }} ECUEs sont déjà attribuées à l'année <strong>{{ $targetYear->ANNEE_ACAD }}</strong>.
                </div>
                <form action="{{ route('passage-promotion.reconduire-ues') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="source_year" value="{{ $sourceYear->id_Annee_Acad }}">
                    <input type="hidden" name="target_year" value="{{ $targetYear->id_Annee_Acad }}">
                    <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Voulez-vous synchroniser/mettre à jour les UEs vers {{ $targetYear->ANNEE_ACAD }} ?');">
                        <i class="bi bi-arrow-repeat me-1"></i> Synchroniser les UEs ({{ $ueStats['source_count'] }})
                    </button>
                </form>
            </div>
            @endif

            {{-- Bannière d'information et actions --}}
            <div class="card p-3 shadow-sm mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-muted">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        
                    </div>

                    <div class="d-flex gap-2">
                        {{-- Bouton Annuler le transfert / Revenir en arrière --}}
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalRollback">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Revenir en arrière sur le transfert
                        </button>
                    </div>
                </div>
            </div>

            {{-- Formulaire de transfert avec le tableau des étudiants --}}
            <form action="{{ route('passage-promotion.transferer') }}" method="POST" id="formTransfert">
                @csrf
                <input type="hidden" name="source_year" value="{{ $sourceYear->id_Annee_Acad }}">
                <input type="hidden" name="target_year" value="{{ $targetYear->id_Annee_Acad }}">

                <div class="card p-3 shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h5 class="fw-bold mb-0">
                                Liste des étudiants ({{ $sourceYear->ANNEE_ACAD }} &rarr; {{ $targetYear->ANNEE_ACAD }})
                            </h5>
                            <span class="text-muted small">
                                Cochez les étudiants à transférer ou utilisez le bouton pour transférer toute la promotion éligible.
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if ($stats['eligible'] > 0)
                            <button type="submit" class="btn btn-success" onclick="return confirm('Confirmez-vous le transfert des étudiants sélectionnés vers l\'année {{ $targetYear->ANNEE_ACAD }} ?');">
                                <i class="bi bi-arrow-up-right-square me-1"></i> Transférer la sélection vers {{ $targetYear->ANNEE_ACAD }} ({{ $stats['eligible'] }})
                            </button>
                            @else
                            <button type="button" class="btn btn-secondary" disabled>
                                Aucun étudiant en attente de transfert
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Option de reconduction automatique des UEs lors du transfert --}}
                    <div class="form-check form-switch mb-3 bg-light p-2 rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="reconduire_ues" value="1" id="reconduire_ues" checked>
                        <label class="form-check-label fw-semibold small" for="reconduire_ues">
                            <i class="bi bi-book-half text-primary me-1"></i> Attribuer / Reconduire automatiquement les UEs et ECUEs de {{ $sourceYear->ANNEE_ACAD }} vers {{ $targetYear->ANNEE_ACAD }}
                        </label>
                    </div>

                    {{-- Filtres rapides et recherche --}}
                    <div class="row mb-2">
                        <div class="col-md-4 col-12">
                            <input type="text" id="filtreRecherche" class="form-control form-control-sm" placeholder="Rechercher par nom, matricule ou classe..." onkeyup="filtrerTableau()">
                        </div>
                        <div class="col-md-8 col-12 text-md-end text-start mt-2 mt-md-0">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cocherTous(true)">Tout cocher</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cocherTous(false)">Tout décocher</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle" id="tableEtudiants">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" id="checkAll" class="form-check-input" onchange="toggleCheckAll(this)">
                                    </th>
                                    <th>Matricule</th>
                                    <th>Nom & Prénoms</th>
                                    <th>Classe actuelle ({{ $sourceYear->ANNEE_ACAD }})</th>
                                    <th>&rarr; Classe cible ({{ $targetYear->ANNEE_ACAD }})</th>
                                    <th>Statut de transfert</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($students as $stu)
                                <tr class="student-row {{ $stu['can_transfer'] ? 'table-row-eligible' : 'opacity-75' }}">
                                    <td class="text-center">
                                        @if ($stu['can_transfer'])
                                        <input type="checkbox" name="selected_students[]" value="{{ $stu['matricule'] }}" class="form-check-input student-cb" checked>
                                        @else
                                        <input type="checkbox" class="form-check-input" disabled>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $stu['matricule'] }}</span></td>
                                    <td class="fw-semibold">{{ $stu['nom'] }} {{ $stu['prenoms'] }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-dark border">
                                            {{ $stu['current_label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($stu['next_code'])
                                        <span class="badge bg-success-subtle text-success border border-success fw-bold">
                                            <i class="bi bi-arrow-right me-1"></i>{{ $stu['next_label'] }}
                                        </span>
                                        @else
                                        <span class="text-muted small">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $stu['statusBadge'] }}">
                                            {{ $stu['statusMessage'] }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Aucun étudiant inscrit trouvé pour l'année <strong>{{ $sourceYear->ANNEE_ACAD }}</strong>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

            {{-- Modal Rollback (Revenir en arrière sur les modifications) --}}
            <div class="modal fade" id="modalRollback" tabindex="-1" aria-labelledby="modalRollbackLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('passage-promotion.annuler') }}" method="POST">
                            @csrf
                            <input type="hidden" name="source_year" value="{{ $sourceYear->id_Annee_Acad }}">
                            <input type="hidden" name="target_year" value="{{ $targetYear->id_Annee_Acad }}">

                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="modalRollbackLabel">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Annuler le transfert d'étudiants
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    <strong>Attention :</strong> Cette action supprimera les inscriptions automatiques créées pour l'année <strong>{{ $targetYear->ANNEE_ACAD }}</strong>.
                                </div>
                                <p>
                                    Cette opération permet de <strong>revenir en arrière</strong> en cas d'erreur lors du passage de promotion.
                                </p>
                                <ul class="small text-muted">
                                    <li>Les inscriptions créées par le transfert dans <strong>{{ $targetYear->ANNEE_ACAD }}</strong> seront retirées.</li>
                                    <li>Les étudiants qui ont déjà effectué un paiement réel enregistré pour <strong>{{ $targetYear->ANNEE_ACAD }}</strong> ne seront <strong>pas supprimés</strong> (protection financière).</li>
                                    <li>L'historique dans l'année passée <strong>{{ $sourceYear->ANNEE_ACAD }}</strong> reste intact et intouché.</li>
                                </ul>
                                <p class="mb-0 fw-semibold">
                                    Voulez-vous vraiment annuler les modifications pour l'année {{ $targetYear->ANNEE_ACAD }} ?
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-trash me-1"></i> Confirmer l'annulation du transfert
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-info py-4 text-center shadow-sm">
            <i class="bi bi-info-circle fs-2 d-block mb-2 text-primary"></i>
            <h5>Sélectionnez deux années académiques ci-dessus</h5>
            <p class="text-muted mb-0 small">
                Choisissez l'année d'origine (ex: <em>2023-2024</em>) et la nouvelle année de destination (ex: <em>2024-2025</em>) puis cliquez sur <strong>Charger</strong>.
            </p>
        </div>
    @endif
</div>
</section>
@endsection

@section('js')
<script>
    function toggleCheckAll(source) {
        const checkboxes = document.querySelectorAll('.student-cb');
        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            if (row && row.style.display !== 'none') {
                cb.checked = source.checked;
            }
        });
    }

    function cocherTous(val) {
        document.querySelectorAll('.student-cb').forEach(cb => cb.checked = val);
        const checkAll = document.getElementById('checkAll');
        if (checkAll) checkAll.checked = val;
    }

    function filtrerTableau() {
        const input = document.getElementById('filtreRecherche');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('#tableEtudiants tbody tr.student-row');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }
</script>
@endsection

