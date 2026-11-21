@extends('layout')
@section('title')
Attestations de Diplôme &amp; Certificats d'Authenticité
@endsection

@section('content')
<section class="section">
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('succes') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-octagon me-1"></i> {{ session('echec') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="card p-4 shadow-sm border-0 border-top border-primary border-3">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3">
                    <i class="bi bi-mortarboard-fill fs-2"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold text-dark">Attestations de Diplôme &amp; Certificats d'Authenticité</h4>
                    <p class="text-muted small mb-0">Édition des documents officiels de soutenance et de fin de cycle (3ème année / Licence Professionnelle).</p>
                </div>
            </div>

            <!-- FORMULAIRE DE SELECTION ET DE PARAMETRAGE -->
            <form action="{{ route('attestations-diplomes.index') }}" method="GET" id="filterForm" class="mb-4">
                <div class="card bg-light-subtle border p-3 rounded-3 mb-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-5 col-md-6 col-sm-12">
                            <label class="form-label fw-bold small text-dark" for="anneeAcademique">
                                Année académique <span class="text-danger">*</span>
                            </label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-control-sm select-native" onchange="this.form.submit()">
                                @foreach ($annees as $a)
                                <option value="{{ $a->id_Annee_Acad }}" {{ (string)$selectedAnnee === (string)$a->id_Annee_Acad ? 'selected' : '' }}>
                                    {{ $a->ANNEE_ACAD }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-5 col-md-6 col-sm-12">
                            <label class="form-label fw-bold small text-dark" for="anneeEtude">
                                Filière / Classe (3ème année) <span class="text-danger">*</span>
                            </label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-control-sm select-native" onchange="this.form.submit()">
                                @foreach ($filieresL3 as $f)
                                <option value="{{ $f->Code_Annee_Etude }}" {{ (string)$selectedFiliere === (string)$f->Code_Annee_Etude ? 'selected' : '' }}>
                                    {{ $f->Libelle_AnEtude }} ({{ $f->Code_Annee_Etude }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-12 col-sm-12">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                                <i class="bi bi-arrow-repeat me-1"></i> Charger la classe
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- FORMULAIRE GÉNÉRATEUR AVEC PARAMÈTRES PERSONNALISABLES -->
            <form id="printBatchForm" method="POST" target="_blank">
                @csrf
                <input type="hidden" name="anneeAcademique" value="{{ $selectedAnnee }}">
                <input type="hidden" name="anneeEtude" value="{{ $selectedFiliere }}">

                <!-- BLOC PARAMÈTRES RÉGLEMENTAIRES ET ANNUELS (ACCORDÉON / CARTE) -->
                <div class="card border mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <span class="fw-bold text-dark small text-uppercase">
                            <i class="bi bi-sliders me-2 text-primary"></i>Paramètres réglementaires et mentions officielles
                        </span>
                        <button class="btn btn-sm btn-link text-decoration-none p-0 text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#collapseParams" aria-expanded="true">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="collapseParams">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <!-- DÉCISION AUTORISATION SOUTENANCES -->
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-dark">
                                        Décision d'Autorisation des soutenances <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="decision_soutenance" id="decision_soutenance" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['decision_soutenance'] }}" required>
                                    <small class="text-muted">Texte inséré sur l'attestation sous "Conformément à la décision...".</small>
                                </div>

                                <!-- NUMÉRO DE RÉFÉRENCE -->
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-dark">
                                        Modèle de référence <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">N° [num]</span>
                                        <input type="text" name="ref_prefixe" id="ref_prefixe" class="form-control"
                                            value="{{ $filiereDetails['ref_prefixe'] }}" required>
                                    </div>
                                    <small class="text-muted">Exemple : /IUSAA/PDG/DA/DE/DEA/2026</small>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold small text-dark">Premier N° chrono</label>
                                    <input type="number" name="start_num" id="start_num" class="form-control form-control-sm"
                                        value="1" min="1" placeholder="001">
                                    <small class="text-muted">Vide = tirets ______</small>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-bold small text-dark">Session <span class="text-danger">*</span></label>
                                    <input type="text" name="session" id="session" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['session'] }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-bold small text-dark">Mention par défaut <span class="text-danger">*</span></label>
                                    <select name="mention_defaut" id="mention_defaut" class="form-control form-control-sm select-native">
                                        <option value="Honorable" selected>Honorable</option>
                                        <option value="Très Honorable">Très Honorable</option>
                                        <option value="Bien">Bien</option>
                                        <option value="Assez Bien">Assez Bien</option>
                                        <option value="Passable">Passable</option>
                                    </select>
                                </div>

                                <!-- ARRÊTÉS MINISTÉRIELS -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Arrêté Ministériel (Ligne 1)</label>
                                    <input type="text" name="arrete_ligne1" id="arrete_ligne1" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['arrete_ligne1'] }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Arrêté Ministériel (Ligne 2)</label>
                                    <input type="text" name="arrete_ligne2" id="arrete_ligne2" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['arrete_ligne2'] }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-dark">Intitulé Arrêté (En vert)</label>
                                    <input type="text" name="domaine_arrete" id="domaine_arrete" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['domaine_arrete'] }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-dark">Cycle officiel</label>
                                    <input type="text" name="cycle" id="cycle" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['cycle'] }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-dark">Spécialité / Option</label>
                                    <input type="text" name="specialite" id="specialite" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['specialite'] }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Fait à</label>
                                    <input type="text" name="lieu_delivrance" id="lieu_delivrance" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['lieu_delivrance'] }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">Date de délivrance (Fait le)</label>
                                    <input type="text" name="date_delivrance" id="date_delivrance" class="form-control form-control-sm"
                                        value="{{ $filiereDetails['date_delivrance'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BARRE D'OUTILS ET BOUTONS D'IMPRESSION GLOBALE -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 p-3 bg-light rounded-3 border">
                    <div>
                        <span class="badge bg-primary fs-7 me-2">
                            <i class="bi bi-people me-1"></i> {{ $inscriptions->count() }} étudiant(s) inscrit(s)
                        </span>
                        <span class="text-muted small">
                            Classe : <b>{{ $filieresL3->where('Code_Annee_Etude', $selectedFiliere)->first()?->Libelle_AnEtude ?? $selectedFiliere }}</b> ({{ $anneeLibelle }})
                        </span>
                    </div>

                    @if ($inscriptions->isNotEmpty())
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" id="btnImprimerToutesAttestations">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Imprimer TOUTES les Attestations
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btnImprimerTousCertificats">
                            <i class="bi bi-patch-check me-1"></i> Imprimer TOUS les Certificats
                        </button>
                        <button type="button" class="btn btn-sm btn-success fw-bold" id="btnImprimerPackClasse">
                            <i class="bi bi-collection-fill me-1"></i> Imprimer Pack Complet (Classe entière)
                        </button>
                    </div>
                    @endif
                </div>

                <!-- TABLEAU DE LA LISTE DES ÉTUDIANTS -->
                @if ($inscriptions->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle" id="tableEtudiantsDiplomes" style="font-size: 13px;">
                        <thead class="table-dark text-center">
                            <tr>
                                <th style="width: 50px;">N°</th>
                                <th style="width: 100px;">Matricule</th>
                                <th style="text-align: left;">Nom &amp; Prénoms</th>
                                <th style="width: 140px;">Né(e) le</th>
                                <th style="width: 130px;">À</th>
                                <th style="width: 150px;">Mention</th>
                                <th style="width: 180px;">N° Réf. personnalisé</th>
                                <th style="width: 160px;" class="text-center">Actions individuelles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inscriptions as $idx => $ins)
                            @php
                                $etu = $ins->etudiant;
                                $numChrono = str_pad((string)($idx + 1), 3, '0', STR_PAD_LEFT);
                                $refSuggeree = 'N° ' . $numChrono . ' ' . $filiereDetails['ref_prefixe'];
                            @endphp
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                                <td class="text-center fw-bold text-primary">{{ $ins->MATRICULE }}</td>
                                <td class="fw-semibold">
                                    {{ $etu ? $etu->fullname() : '—' }}
                                    @if ($etu && $etu->sexeEtu === 'F')
                                    <span class="badge bg-danger-subtle text-danger ms-1" style="font-size: 10px;">F</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ $etu && $etu->dateNaisEtu ? \Carbon\Carbon::parse($etu->dateNaisEtu)->format('d/m/Y') : '—' }}
                                </td>
                                <td>{{ $etu->lieuNaisEtu ?? 'Porto-Novo' }}</td>
                                <td>
                                    <select name="mentions[{{ $ins->MATRICULE }}]" class="form-control form-control-sm select-native select-mention-row">
                                        <option value="Honorable" selected>Honorable</option>
                                        <option value="Très Honorable">Très Honorable</option>
                                        <option value="Bien">Bien</option>
                                        <option value="Assez Bien">Assez Bien</option>
                                        <option value="Passable">Passable</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="numeros_ref[{{ $ins->MATRICULE }}]"
                                        class="form-control form-control-sm input-ref-row"
                                        value="{{ $refSuggeree }}"
                                        placeholder="N° _____ /IUSAA...">
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-danger btn-print-single"
                                            data-action="attestation"
                                            data-matricule="{{ $ins->MATRICULE }}"
                                            title="Imprimer Attestation de diplôme">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-print-single"
                                            data-action="certificat"
                                            data-matricule="{{ $ins->MATRICULE }}"
                                            title="Imprimer Certificat d'authenticité">
                                            <i class="bi bi-patch-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-print-single"
                                            data-action="pack"
                                            data-matricule="{{ $ins->MATRICULE }}"
                                            title="Imprimer Pack (Les 2 documents)">
                                            <i class="bi bi-collection"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info text-center py-5 my-3">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-primary"></i>
                    <h5>Aucun étudiant trouvé dans cette classe pour l'année sélectionnée</h5>
                    <p class="text-muted small mb-0">Veuillez sélectionner une autre filière ou vérifier les inscriptions de cette année académique.</p>
                </div>
                @endif
            </form>

        </div>
    </div>
</div>
</section>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Si la mention par défaut change, répercuter sur toutes les lignes
    $('#mention_defaut').on('change', function() {
        let val = $(this).val();
        $('.select-mention-row').val(val);
    });

    // Mettre à jour les références suggérées si le préfixe ou le start_num change
    function updateRefs() {
        let prefixe = $('#ref_prefixe').val();
        let startNumVal = $('#start_num').val();
        let startNum = parseInt(startNumVal) || 1;

        $('.input-ref-row').each(function(idx) {
            let numChrono = String(startNum + idx).padStart(3, '0');
            if (startNumVal !== '') {
                $(this).val('N° ' + numChrono + ' ' + prefixe);
            } else {
                $(this).val('N° _______ ' + prefixe);
            }
        });
    }

    $('#ref_prefixe, #start_num').on('input', updateRefs);

    // Soumission globale : Attestations de la classe
    $('#btnImprimerToutesAttestations').on('click', function(e) {
        e.preventDefault();
        $('#printBatchForm').find('input[name="matricule_seul"]').remove();
        $('#printBatchForm').attr('action', '{{ route("attestations-diplomes.imprimer-attestation") }}');
        $('#printBatchForm').submit();
    });

    // Soumission globale : Certificats de la classe
    $('#btnImprimerTousCertificats').on('click', function(e) {
        e.preventDefault();
        $('#printBatchForm').find('input[name="matricule_seul"]').remove();
        $('#printBatchForm').attr('action', '{{ route("attestations-diplomes.imprimer-certificat") }}');
        $('#printBatchForm').submit();
    });

    // Soumission globale : Pack complet
    $('#btnImprimerPackClasse').on('click', function(e) {
        e.preventDefault();
        $('#printBatchForm').find('input[name="matricule_seul"]').remove();
        $('#printBatchForm').attr('action', '{{ route("attestations-diplomes.imprimer-pack") }}');
        $('#printBatchForm').submit();
    });

    // Clic individuel pour un étudiant spécifique
    $(document).on('click', '.btn-print-single', function(e) {
        e.preventDefault();
        let action = $(this).data('action');
        let matricule = $(this).data('matricule');

        $('#printBatchForm').find('input[name="matricule_seul"]').remove();
        $('#printBatchForm').append('<input type="hidden" name="matricule_seul" value="' + matricule + '">');

        if (action === 'attestation') {
            $('#printBatchForm').attr('action', '{{ route("attestations-diplomes.imprimer-attestation") }}');
        } else if (action === 'certificat') {
            $('#printBatchForm').attr('action', '{{ route("attestations-diplomes.imprimer-certificat") }}');
        } else if (action === 'pack') {
            $('#printBatchForm').attr('action', '{{ route("attestations-diplomes.imprimer-pack") }}');
        }

        $('#printBatchForm').submit();
    });
});
</script>
@endsection
