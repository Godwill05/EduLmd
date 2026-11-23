@extends('layout')
@section('title')
Étudiants en retard
@endsection

@section('content')
<section class="section">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0 fw-bold text-navy"><i class="ri-time-line me-2 text-warning"></i>Étudiants en retard</h3>
            <p class="text-muted small mb-0">Tranches non payées et échues, groupées par étudiant.</p>
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
                        <div class="text-muted small fw-bold">{{ $stats['nb_etudiants'] }}</div>
                        <div class="text-dark fw-bold">Étudiants en retard</div>
                    </div>
                    <span class="badge bg-warning rounded-pill fs-7">{{ $stats['nb_etudiants'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card p-3 border-0 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold">{{ $stats['nb_tranches'] }}</div>
                        <div class="text-dark fw-bold">Tranches en retard</div>
                    </div>
                    <span class="badge bg-warning rounded-pill fs-7">{{ $stats['nb_tranches'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card p-3 border-0 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-danger small fw-bold">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F</div>
                        <div class="text-dark fw-bold">Montant total dû</div>
                    </div>
                    <span class="badge bg-danger rounded-pill fs-7">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card p-3 border-0 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold">{{ \Carbon\Carbon::today()->format('d/m/Y') }}</div>
                        <div class="text-dark fw-bold">À la date du</div>
                    </div>
                    <span class="badge bg-info rounded-pill fs-7">{{ \Carbon\Carbon::today()->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
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
            <div class="col-lg-3 col-md-6 d-grid">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ri-filter-line me-1"></i> Filtrer</button>
            </div>
            <div class="col-lg-3 col-md-6 d-grid">
                <a href="{{ route('previsions.etudiants-en-retard') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="ri-refresh-line me-1"></i> Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="card p-0 shadow-sm border-0">
        @if (empty($etudiants))
        <div class="p-5 text-center text-muted">
            <i class="ri-checkbox-circle-line fs-3 d-block mb-2 opacity-50"></i>
            <p class="mb-0">Aucun étudiant en retard pour ces filtres.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-light text-center small">
                    <tr>
                        <th>Étudiant</th>
                        <th>Matricule</th>
                        <th>Classe / Année</th>
                        <th class="text-center">Tranches en retard</th>
                        <th class="text-end">Montant total dû</th>
                        <th class="text-center">Plus vieux retard</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($etudiants as $e)
                    <tr class="text-center">
                        <td class="text-start">
                            <div class="fw-bold text-dark small">{{ $e['nom'] ?? '—' }} {{ $e['prenoms'] ?? '—' }}</div>
                        </td>
                        <td><span class="badge bg-dark text-white">{{ $e['matricule'] }}</span></td>
                        <td class="text-start">
                            <div class="fw-bold text-dark small">{{ $e['filiere'] ?? '—' }}</div>
                            <div class="text-muted fw-normal small">{{ $e['annee_acad'] ?? '—' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">{{ $e['nb_tranches'] }}</span>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-danger">{{ number_format((int)$e['montant_total'], 0, ',', ' ') }} F</span>
                        </td>
                        <td class="text-center">
                            <span class="text-muted small">
                                {{ \Carbon\Carbon::parse($e['plus_vieux_retard'])->format('d/m/Y') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $stats['nb_etudiants'] }} étudiant(s) en retard
        </div>
        @endif
    </div>

</div>
</section>
@endsection