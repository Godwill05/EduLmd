@if ($paiements->isEmpty())
    <div class="alert alert-info text-center my-3 border-0 shadow-sm">
        <i class="bi bi-info-circle fs-5 me-2 align-middle"></i>
        <span>Aucun paiement trouvé pour les critères sélectionnés.</span>
    </div>
@else
<div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
    <div>
        <span class="badge bg-primary fs-6">{{ $paiements->count() }} paiement(s) trouvé(s)</span>
    </div>
    <div>
        <span class="text-muted small">Total encaissé :</span>
        <strong class="text-success fs-6 ms-1">{{ number_format($paiements->sum('montantPaye'), 0, ',', ' ') }} F CFA</strong>
    </div>
</div>
<table class="table table-striped table-hover align-middle" id="tableD">
    <thead class="table-light">
        <tr>
            <th>Étudiant / Filière</th>
            <th>Montant payé</th>
            <th>Mode</th>
            <th>Soldé</th>
            <th>Date de paiement</th>
            <th>N° Tranche</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($paiements as $paiement)
        @php
            $ins   = $paiement->inscription();
            $reste = $ins ? $ins->reste_paye : null;
            $modalId = 'paiementModal_' . $paiement->id;
            $fmtMontant = number_format($paiement->montantPaye, 0, ',', ' ');
        @endphp
        <tr>
            <td>
                <span class="badge bg-secondary-subtle text-secondary fw-semibold me-1">{{ $ins ? $ins->anneeEtude->Libelle_AnEtude : '—' }}</span><br>
                <span class="fw-bold">{{ $paiement->MATRICULE }}</span>
                @if($ins && $ins->etudiant)
                    — {{ $ins->etudiant->nomEtu }} {{ $ins->etudiant->prenomsEtu }}
                @endif
            </td>
            <td class="fw-bold text-success">{{ number_format($paiement->montantPaye, 0, ',', ' ') }} F CFA</td>
            <td><span class="badge bg-light text-dark border">{{ $paiement->modePaiement ?? '—' }}</span></td>
            <td>
                @if($reste !== null)
                    @if($reste <= 0)
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Soldé</span>
                    @else
                        <span class="badge bg-warning text-dark">Reste : {{ number_format($reste, 0, ',', ' ') }} F</span>
                    @endif
                @else
                    <span class="badge bg-secondary">—</span>
                @endif
            </td>
            <td>{{ \Carbon\Carbon::parse($paiement->datePaiement)->format('d/m/Y') }}</td>
            <td class="text-center"><span class="badge bg-primary-subtle text-primary border border-primary">T{{ $paiement->numeroTranche }}</span></td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-secondary me-1" type="button"
                    data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
                    title="Modifier">
                    <i class="bi bi-pencil"></i>
                </button>
                <form action="{{ route('paiements.destroy', $paiement->id) }}" method="post" class="d-inline" id="form-del-pmt-{{ $paiement->id }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="button"
                        onclick="confirmDelete(document.getElementById('form-del-pmt-{{ $paiement->id }}'), 'ce paiement de <strong>{{ $fmtMontant }} F CFA</strong>')"
                        title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
        </tr>

        {{-- Modale unique par paiement (ID distinct) --}}
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}_label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $modalId }}_label">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>Modifier le paiement
                            <small class="text-muted ms-2">— {{ $paiement->MATRICULE }}</small>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 pb-4">
                        <form method="POST" action="{{ route('paiements.update', $paiement->id) }}">
                            @csrf
                            @method('PATCH')
                            <div class="row g-3">
                                <div class="col-lg-6 col-sm-12">
                                    <label class="form-label fw-semibold" for="datePaiement_{{ $paiement->id }}">
                                        Date de paiement <span class="text-danger">*</span>
                                    </label>
                                    <input required type="date" name="datePaiement"
                                        id="datePaiement_{{ $paiement->id }}"
                                        class="form-control"
                                        value="{{ $paiement->datePaiement }}">
                                </div>
                                <div class="col-lg-6 col-sm-12">
                                    <label class="form-label fw-semibold" for="montantPaye_{{ $paiement->id }}">
                                        Montant (en FCFA) <span class="text-danger">*</span>
                                    </label>
                                    <input required type="number" name="montantPaye"
                                        id="montantPaye_{{ $paiement->id }}"
                                        class="form-control" min="0"
                                        value="{{ $paiement->montantPaye }}">
                                </div>
                                <div class="col-lg-6 col-sm-12">
                                    <label class="form-label fw-semibold" for="modePaiement_{{ $paiement->id }}">
                                        Mode de paiement
                                    </label>
                                    <input type="text" name="modePaiement"
                                        id="modePaiement_{{ $paiement->id }}"
                                        class="form-control"
                                        value="{{ $paiement->modePaiement }}">
                                </div>
                                <div class="col-lg-6 col-sm-12">
                                    <label class="form-label fw-semibold" for="numeroTranche_{{ $paiement->id }}">
                                        N° Tranche
                                    </label>
                                    <input type="number" name="numeroTranche"
                                        id="numeroTranche_{{ $paiement->id }}"
                                        class="form-control" min="1" max="9"
                                        value="{{ $paiement->numeroTranche }}">
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i>Fermer
                                </button>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-check-circle me-1"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </tbody>
</table>
@endif
