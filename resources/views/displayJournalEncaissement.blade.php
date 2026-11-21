{{-- Vue partielle : Journal d'encaissement (chargée en AJAX) --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 text-center border-start border-success border-4">
            <span class="text-muted small fw-semibold">Période</span>
            <h6 class="fw-bold text-success mb-0 mt-1">
                {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}
                @if($dateDebut !== $dateFin)
                → {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
                @endif
            </h6>
            <small class="text-muted">{{ $annee ? $annee->ANNEE_ACAD : '' }}</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 text-center border-start border-primary border-4">
            <span class="text-muted small fw-semibold">Nombre de paiements</span>
            <h4 class="fw-bold text-primary mb-0 mt-1">{{ $nbPaiements }}</h4>
            <small class="text-muted">sur {{ count($groupesParDate) }} jour(s)</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 text-center border-start border-success border-4">
            <span class="text-muted small fw-semibold">Total encaissé</span>
            <h4 class="fw-bold text-success mb-0 mt-1">{{ number_format($totalGlobal, 0, ',', ' ') }} F</h4>
            <small class="text-success fw-semibold">CFA</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 text-center border-start border-info border-4">
            <span class="text-muted small fw-semibold">Par mode de paiement</span>
            <div class="mt-1">
                @foreach($totalParMode as $mode => $montant)
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">{{ $mode }}</span>
                    <span class="fw-bold">{{ number_format($montant, 0, ',', ' ') }} F</span>
                </div>
                @endforeach
                @if(empty($totalParMode))
                <span class="text-muted small">—</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if(empty($groupesParDate))
<div class="alert alert-warning text-center py-4">
    <i class=" ri-alert-line fs-3 d-block mb-2"></i>
    Aucun paiement enregistré sur cette période pour les critères sélectionnés.
</div>
@else

@php
    $joursFr = [
        'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
    ];
    $moisFr = [
        'January' => 'janvier', 'February' => 'février', 'March' => 'mars', 'April' => 'avril',
        'May' => 'mai', 'June' => 'juin', 'July' => 'juillet', 'August' => 'août',
        'September' => 'septembre', 'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'
    ];
@endphp

@foreach($groupesParDate as $date => $grp)
@php
    $dt = \Carbon\Carbon::parse($date);
    $jFr = $joursFr[$dt->format('l')] ?? $dt->format('l');
    $mFr = $moisFr[$dt->format('F')] ?? $dt->format('F');
    $dateAffichee = $jFr . ' ' . $dt->format('d') . ' ' . $mFr . ' ' . $dt->format('Y');
@endphp
<div class="card mb-3 border shadow-sm">
    <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 fw-bold text-success">
            <i class=" ri-calendar-2-line me-2"></i>
            {{ $dateAffichee }}
        </h6>
        <span class="badge bg-success fs-6">
            Total : {{ number_format($grp['total'], 0, ',', ' ') }} F CFA
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
                <tr class="text-center">
                    <th style="text-align:left; width:100px;">Matricule</th>
                    <th style="text-align:left;">Nom & Prénoms</th>
                    <th style="text-align:left;">Filière</th>
                    <th>N° Tranche</th>
                    <th>Mode</th>
                    <th>Montant encaissé</th>
                    <th>Reste à payer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grp['paiements'] as $p)
                <tr class="text-center">
                    <td class="fw-bold text-start">{{ $p['matricule'] }}</td>
                    <td class="fw-semibold text-start">{{ $p['nom'] }} {{ $p['prenoms'] }}</td>
                    <td class="text-start">
                        <span class="badge bg-secondary-subtle text-secondary border">{{ $p['filiere'] }}</span>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary">T{{ $p['tranche'] }}</span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $p['mode'] }}</span>
                    </td>
                    <td class="fw-bold text-success">{{ number_format($p['montant'], 0, ',', ' ') }} F</td>
                    <td>
                        @if($p['reste_apres'] !== null)
                            @if($p['reste_apres'] <= 0)
                                <span class="badge bg-success"><i class=" ri-check-double-line me-1"></i>Soldé</span>
                            @else
                                <span class="text-warning fw-semibold">{{ number_format($p['reste_apres'], 0, ',', ' ') }} F</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr class="text-center">
                    <td colspan="5" class="text-end">Sous-total ({{ count($grp['paiements']) }} paiement(s)) :</td>
                    <td class="text-success">{{ number_format($grp['total'], 0, ',', ' ') }} F CFA</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach

{{-- Récapitulatif global --}}
<div class="card border-dark shadow-sm mt-4">
    <div class="card-header bg-dark text-white fw-bold py-2">
        <i class=" ri-calculator-fill me-2"></i>RÉCAPITULATIF GLOBAL — Total encaissé sur la période
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>Mode de paiement</th><th class="text-end">Montant</th></tr>
                    </thead>
                    <tbody>
                        @foreach($totalParMode as $mode => $montant)
                        <tr>
                            <td>{{ $mode }}</td>
                            <td class="text-end fw-bold">{{ number_format($montant, 0, ',', ' ') }} F CFA</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td class="fw-bold">TOTAL</td>
                            <td class="text-end fw-bold">{{ number_format($totalGlobal, 0, ',', ' ') }} F CFA</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-6 d-flex align-items-center justify-content-center">
                <div class="text-center">
                    <div class="display-6 fw-bold text-success">{{ number_format($totalGlobal, 0, ',', ' ') }}</div>
                    <div class="text-muted fs-5">F CFA encaissés</div>
                    <div class="text-muted small mt-1">{{ $nbPaiements }} opération(s) sur {{ count($groupesParDate) }} jour(s)</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
