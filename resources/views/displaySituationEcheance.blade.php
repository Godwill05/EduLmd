<div class="row g-3 mb-4">
    <!-- Cartes récapitulatives -->
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-light-subtle h-100 p-3 text-center border-start border-primary border-4">
            <span class="text-muted small fw-semibold">Date d'évaluation</span>
            <h5 class="fw-bold text-primary mb-0 mt-1">
                {{ \Carbon\Carbon::parse($dateEcheance)->translatedFormat('d/m/Y') }}
            </h5>
            <small class="text-muted">{{ $annee ? $annee->ANNEE_ACAD : '' }}</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-light-subtle h-100 p-3 text-center border-start border-success border-4">
            <span class="text-muted small fw-semibold">En règle / Soldés</span>
            <h4 class="fw-bold text-success mb-0 mt-1">
                {{ $totauxGlobaux['nb_en_regle'] }}
            </h4>
            <small class="text-muted">sur {{ $totauxGlobaux['total_etudiants'] }} étudiant(s)</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-light-subtle h-100 p-3 text-center border-start border-danger border-4">
            <span class="text-muted small fw-semibold">En retard (Impayés)</span>
            <h4 class="fw-bold text-danger mb-0 mt-1">
                {{ $totauxGlobaux['nb_en_retard'] }}
            </h4>
            <small class="text-danger fw-semibold">{{ number_format($totauxGlobaux['total_retard'], 0, ',', ' ') }} F CFA de retard</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-light-subtle h-100 p-3 text-center border-start border-info border-4">
            <span class="text-muted small fw-semibold">Taux de recouvrement</span>
            @php
                $tauxRecouvr = $totauxGlobaux['total_exigible'] > 0
                    ? round(($totauxGlobaux['total_paye'] / $totauxGlobaux['total_exigible']) * 100, 2)
                    : 100;
            @endphp
            <h4 class="fw-bold text-info mb-0 mt-1">
                {{ $tauxRecouvr }} %
            </h4>
            <small class="text-muted">{{ number_format($totauxGlobaux['total_paye'], 0, ',', ' ') }} F / {{ number_format($totauxGlobaux['total_exigible'], 0, ',', ' ') }} F</small>
        </div>
    </div>
</div>

@if (empty($groupes))
<div class="alert alert-warning text-center py-4">
    <i class=" ri-alert-line fs-3 d-block mb-2"></i>
    Aucun étudiant trouvé correspondant aux critères sélectionnés.
</div>
@else
@foreach ($groupes as $grp)
<div class="card mb-4 border shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold text-dark">
            <i class=" ri-graduation-cap-line text-primary me-2"></i>{{ $grp['filiere']->Libelle_AnEtude }}
        </h5>
        <div>
            <span class="badge bg-success-subtle text-success border border-success me-1">
                <i class=" ri-checkbox-circle-line me-1"></i>{{ $grp['totaux']['nb_en_regle'] }} en règle
            </span>
            <span class="badge bg-danger-subtle text-danger border border-danger me-1">
                <i class=" ri-close-circle-line me-1"></i>{{ $grp['totaux']['nb_en_retard'] }} en retard
            </span>
            @if ($grp['totaux']['nb_sans_echeancier'] > 0)
            <span class="badge bg-secondary-subtle text-secondary border me-1">
                {{ $grp['totaux']['nb_sans_echeancier'] }} sans échéancier
            </span>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0 datatable-situation" style="font-size: 13px;">
            <thead class="table-light">
                <tr class="text-center">
                    <th style="width: 100px;">Matricule</th>
                    <th style="text-align: left;">Nom &amp; Prénoms</th>
                    <th>Scolarité totale</th>
                    <th>Exigible à date</th>
                    <th>Cumul payé à date</th>
                    <th>Retard dû</th>
                    <th>Reste annuel</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grp['etudiants'] as $item)
                <tr class="text-center {{ $item['statut'] === 'en_retard' ? 'table-danger-subtle' : '' }}">
                    <td class="fw-bold">{{ $item['matricule'] }}</td>
                    <td style="text-align: left;" class="fw-semibold">
                        {{ $item['nom'] }} {{ $item['prenoms'] }}
                        @if ($item['nb_echeances'] > 0)
                        <span class="badge bg-light text-muted border ms-1" title="{{ $item['nb_echeances'] }} tranche(s) prévue(s)">
                            <i class=" ri-calendar-line me-1"></i>{{ $item['nb_echeances'] }} tranches
                        </span>
                        @endif
                    </td>
                    <td>{{ number_format($item['frais_total'], 0, ',', ' ') }} F</td>
                    <td class="fw-semibold text-primary">
                        {{ number_format($item['exigible'], 0, ',', ' ') }} F
                    </td>
                    <td class="fw-semibold text-success">
                        {{ number_format($item['paye'], 0, ',', ' ') }} F
                    </td>
                    <td class="fw-bold {{ $item['retard'] > 0 ? 'text-danger' : 'text-muted' }}">
                        @if ($item['retard'] > 0)
                            -{{ number_format($item['retard'], 0, ',', ' ') }} F
                        @else
                            0 F
                        @endif
                    </td>
                    <td class="text-muted">
                        {{ number_format($item['reste_annuel'], 0, ',', ' ') }} F
                    </td>
                    <td>
                        @if ($item['statut'] === 'solde')
                            <span class="badge bg-success"><i class=" ri-check-double-line me-1"></i>Soldé</span>
                        @elseif ($item['statut'] === 'en_regle')
                            <span class="badge bg-success"><i class=" ri-checkbox-circle-line me-1"></i>En règle</span>
                        @elseif ($item['statut'] === 'en_retard')
                            <span class="badge bg-danger"><i class=" ri-alert-line me-1"></i>En retard</span>
                        @else
                            <span class="badge bg-secondary"><i class=" ri-subtract-line me-1"></i>Sans échéancier</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold text-center">
                <tr>
                    <td colspan="2" class="text-end">Sous-total ({{ count($grp['etudiants']) }} étud.) :</td>
                    <td>{{ number_format($grp['totaux']['total_frais'], 0, ',', ' ') }} F</td>
                    <td class="text-primary">{{ number_format($grp['totaux']['total_exigible'], 0, ',', ' ') }} F</td>
                    <td class="text-success">{{ number_format($grp['totaux']['total_paye'], 0, ',', ' ') }} F</td>
                    <td class="text-danger">{{ number_format($grp['totaux']['total_retard'], 0, ',', ' ') }} F</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach

<!-- Tableau récapitulatif global -->
<div class="card border-dark shadow-sm mt-4">
    <div class="card-header bg-dark text-white fw-bold py-2">
        <i class=" ri-pie-chart-fill me-2"></i>RÉCAPITULATIF GLOBAL (Situation arrêtée au {{ \Carbon\Carbon::parse($dateEcheance)->format('d/m/Y') }})
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 text-center align-middle" style="font-size: 14px;">
                <thead class="table-light">
                    <tr>
                        <th>Total Inscrits</th>
                        <th>Scolarité totale</th>
                        <th>Montant exigible à date</th>
                        <th>Cumul payé à date</th>
                        <th>Total des retards / impayés</th>
                        <th>Taux de recouvrement</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fw-bold fs-6">
                        <td>{{ $totauxGlobaux['total_etudiants'] }}</td>
                        <td>{{ number_format($totauxGlobaux['total_frais'], 0, ',', ' ') }} F CFA</td>
                        <td class="text-primary">{{ number_format($totauxGlobaux['total_exigible'], 0, ',', ' ') }} F CFA</td>
                        <td class="text-success">{{ number_format($totauxGlobaux['total_paye'], 0, ',', ' ') }} F CFA</td>
                        <td class="text-danger">{{ number_format($totauxGlobaux['total_retard'], 0, ',', ' ') }} F CFA</td>
                        <td class="text-info">{{ $tauxRecouvr }} %</td>
                    </tr>
                    <tr class="bg-light small">
                        <td colspan="6" class="py-2">
                            <span class="badge bg-success fs-7 me-2"><i class=" ri-checkbox-circle-line me-1"></i>{{ $totauxGlobaux['nb_en_regle'] }} En règle / Soldés</span>
                            <span class="badge bg-danger fs-7 me-2"><i class=" ri-close-circle-line me-1"></i>{{ $totauxGlobaux['nb_en_retard'] }} En retard</span>
                            @if ($totauxGlobaux['nb_sans_echeancier'] > 0)
                            <span class="badge bg-secondary fs-7"><i class=" ri-subtract-line me-1"></i>{{ $totauxGlobaux['nb_sans_echeancier'] }} Sans échéancier</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
