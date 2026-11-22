@extends('layout')
@section('title')
Échéances à traiter
@endsection

@section('content')
<section class="section">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0 fw-bold text-navy"><i class="ri-time-line me-2 text-warning"></i>Échéances à traiter</h3>
            <p class="text-muted small mb-0">Tranches non payées, classées par urgence. Les échéances déjà passées s'affichent en premier.</p>
        </div>
        <div>
            <a href="{{ route('previsions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ri-table-line me-1"></i> Toutes les prévisions
            </a>
        </div>
    </div>

    @if (session()->has('succes'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-fill me-2"></i>{{ session('succes') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-alert-fill me-2"></i>{{ session('echec') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Cartes récapitulatives -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card p-3 border-0 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold">{{ $stats['total'] }}</div>
                        <div class="text-dark fw-bold">Tranches en attente</div>
                    </div>
                    <span class="badge bg-secondary rounded-pill fs-7">{{ $stats['total'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card p-3 border-0 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-danger small fw-bold">{{ $stats['en_retard'] }}</div>
                        <div class="text-dark fw-bold">En retard</div>
                    </div>
                    <span class="badge bg-danger rounded-pill fs-7">{{ $stats['en_retard'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card p-3 border-0 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-info small fw-bold">{{ $stats['a_venir'] }}</div>
                        <div class="text-dark fw-bold">À venir</div>
                    </div>
                    <span class="badge bg-info rounded-pill fs-7">{{ $stats['a_venir'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card p-3 border-0 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-dark small fw-bold">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F</div>
                        <div class="text-dark fw-bold">Montant total attendu</div>
                    </div>
                    <span class="badge bg-dark rounded-pill fs-7">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres + actions en lot -->
    <div class="card p-3 shadow-sm border-0 mb-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-bold small" for="annee_academique">Année académique</label>
                <select name="annee_academique" id="annee_academique" class="form-control form-control-sm form-select">
                    <option value="">Toutes</option>
                    @foreach ($annees as $a)
                    <option value="{{ $a->id_Annee_Acad }}" {{ (request()->get('annee_academique') ?? '') === (string)$a->id_Annee_Acad ? 'selected' : '' }}>{{ $a->ANNEE_ACAD }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-bold small" for="filiere">Filière</label>
                <select name="filiere" id="filiere" class="form-control form-control-sm form-select">
                    <option value="">Toutes</option>
                    @foreach ($filieres as $f)
                    <option value="{{ $f->Code_Annee_Etude }}" {{ (request()->get('filiere') ?? '') === $f->Code_Annee_Etude ? 'selected' : '' }}>{{ $f->Libelle_AnEtude }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ri-filter-line me-1"></i> Filtrer</button>
            </div>
            <div class="col-lg-3 col-md-6 d-grid">
                <a href="{{ route('previsions.echeances') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="ri-refresh-line me-1"></i> Réinitialiser</a>
            </div>
        </form>

        <!-- Barre d'actions en lot -->
        <div class="mt-3 d-flex align-items-center gap-2 flex-wrap" id="barreActionsLot" style="display:none;">
            <span class="badge bg-dark fs-7" id="compteSelection">0 sélectionné(s)</span>
            <button type="button" class="btn btn-success btn-sm" id="btnMarquerToutPaye">
                <i class="ri-checkbox-circle-line me-1"></i> Marquer comme payé
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAnnulerSelection">
                <i class="ri-close-circle-line me-1"></i> Annuler
            </button>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card p-0 shadow-sm border-0">
        @if ($echeances->isEmpty())
        <div class="p-5 text-center text-muted">
            <i class="ri-checkbox-circle-line fs-3 d-block mb-2 opacity-50"></i>
            <p class="mb-0">Aucune échéance en attente pour ces filtres.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-light text-center small">
                    <tr>
                        <th style="width:30px;"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                        <th>Étudiant</th>
                        <th>Classe / Année</th>
                        <th class="text-center">Échéance</th>
                        <th class="text-end">Montant prévu</th>
                        <th class="text-center">Délai</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($echeances as $e)
                    <tr class="text-center {{ $e->echeance < $aujourdhui ? 'table-danger bg-danger bg-opacity-10' : '' }}" data-id="{{ $e->id }}">
                        <td>
                            <input type="checkbox" class="form-check-input ligneSelect" value="{{ $e->id }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;">
                                    <span class="text-dark fw-bold text-xs">{{ strtoupper(substr($e->etudiant->nomEtu ?? '?', 0, 1)) }}{{ strtoupper(substr($e->etudiant->prenomsEtu ?? '?', 0, 1)) }}</span>
                                </div>
                                <div class="text-start">
                                    <div class="fw-bold text-dark small">{{ $e->etudiant->nomEtu ?? '—' }} {{ $e->etudiant->prenomsEtu ?? '—' }}</div>
                                    <div class="text-muted fw-normal small">{{ $e->MATRICULE }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-start">
                            <div class="fw-bold text-dark small">{{ $e->ID_FILIERE }}</div>
                            <div class="text-muted fw-normal small">{{ $e->anneeacademique->ANNEE_ACAD ?? '—' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $e->echeance < $aujourdhui ? 'bg-danger' : 'bg-info' }} fs-7">
                                {{ \Carbon\Carbon::parse($e->echeance)->format('d/m/Y') }}
                                @if ($e->echeance < $aujourdhui)
                                <br><small class="text-white-50">En retard</small>
                                @endif
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-dark">{{ number_format((int)$e->montant_a_payer, 0, ',', ' ') }} F</span>
                        </td>
                        <td class="text-center">
                            @php
                                $echeance = \Carbon\Carbon::parse($e->echeance);
                                $jours = $echeance->diffInDays(\Carbon\Carbon::today(), false);
                            @endphp
                            @if ($e->echeance < $aujourdhui)
                            <span class="text-danger fw-bold">Il y a {{ $jours }} jour{{ $jours > 1 ? 's' : '' }}</span>
                            @elseif ($jours === 0)
                            <span class="text-warning fw-bold">Aujourd'hui</span>
                            @else
                            <span class="text-muted">Dans {{ $jours }} jour{{ $jours > 1 ? 's' : '' }}</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('previsions.marquer-payee', $e->id) }}" method="POST" class="d-inline" id="form-paye-{{ $e->id }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm btn-marquer-payee" data-id="{{ $e->id }}" title="Marquer comme payée">
                                    <i class="ri-checkbox-circle-line"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $echeances->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
</section>

@section('js')
<script>
/* ========================================================================
   Tableau de bord "Échéances à traiter"
   ------------------------------------------------------------------------
   Sélection multiple, marquage individuel et en lot comme payé.
   ======================================================================== */

const btnMarquerTout = document.getElementById('btnMarquerToutPaye');
const btnAnnuler = document.getElementById('btnAnnulerSelection');
const barreLot = document.getElementById('barreActionsLot');
const compteSelection = document.getElementById('compteSelection');
const checkAll = document.getElementById('checkAll');

function miseAJourSelection() {
    const cochées = document.querySelectorAll('.ligneSelect:checked');
    const n = cochées.length;

    barreLot.style.display = n > 0 ? 'flex' : 'none';
    compteSelection.textContent = n + ' sélectionné' + (n > 1 ? 's' : '') + (n === 1 ? '' : '');
    checkAll.checked = n > 0 && n === document.querySelectorAll('.ligneSelect').length;
}

// Tout cocher / décocher
checkAll.addEventListener('change', function () {
    document.querySelectorAll('.ligneSelect').forEach(cb => cb.checked = this.checked);
    miseAJourSelection();
});

// Cochage individuel
document.querySelectorAll('.ligneSelect').forEach(cb => {
    cb.addEventListener('change', miseAJourSelection);
});

// Marquer une tranche comme payée (AJAX)
document.querySelectorAll('.btn-marquer-payee').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const id = this.dataset.id;
        const ligne = document.querySelector(`tr[data-id="${id}"]`);

        fetch(this.closest('form').action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    ligne.remove();
                    miseAJourSelection();
                    afficherToast('Tranche marquée comme payée.', 'ok');
                } else {
                    afficherToast(data.message || 'Erreur.', 'erreur');
                }
            })
            .catch(() => afficherToast('Erreur réseau.', 'erreur'));
    });
});

// Marquer tout (lot)
btnMarquerTout.addEventListener('click', function () {
    const ids = [...document.querySelectorAll('.ligneSelect:checked')].map(cb => cb.value);

    fetch('{{ route('previsions.marquer-toutes-payees') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ ids: ids }),
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                ids.forEach(id => {
                    const ligne = document.querySelector(`tr[data-id="${id}"]`);
                    if (ligne) ligne.remove();
                });
                miseAJourSelection();
                afficherToast(data.message, 'ok');
            } else {
                afficherToast(data.message || 'Erreur.', 'erreur');
            }
        })
        .catch(() => afficherToast('Erreur réseau.', 'erreur'));
});

btnAnnuler.addEventListener('click', function () {
    document.querySelectorAll('.ligneSelect:checked').forEach(cb => cb.checked = false);
    checkAll.checked = false;
    miseAJourSelection();
});

function afficherToast(message, ton) {
    const el = document.createElement('div');
    el.className = 'ed-toast ' + (ton === 'erreur' ? 'ed-toast-erreur' : 'ed-toast-ok');
    el.innerHTML = '<i class="' + (ton === 'erreur' ? 'ri-error-warning-line' : 'ri-checkbox-circle-line') + '"></i>' +
        '<span></span>';
    el.querySelector('span').textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.classList.add('is-out'), 2600);
    setTimeout(() => el.remove(), 3100);
}
</script>
@endsection
