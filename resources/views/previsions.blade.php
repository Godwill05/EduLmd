@extends('layout')
@section('title')
Gestion des Échéances & Prévisions
@endsection
@section('content')
<style>
.select-native {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right .75rem center;
    background-size: 16px 12px;
    padding-right: 2.25rem;
    cursor: pointer;
}
</style>
<section class="section">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0 fw-bold text-navy"><i class=" ri-calendar-event-line me-2 text-primary"></i>Gestion des Échéances &amp; Prévisions de Paiement</h3>
            <p class="text-muted small mb-0">Configuration des tranches par défaut pour toute une classe ou sur-mesure pour chaque étudiant.</p>
        </div>
        <div>
            <a href="{{ route('engagement.index') }}" class="btn btn-outline-dark btn-sm">
                <i class=" ri-file-pdf-line me-1"></i> Fiches d'engagement
            </a>
            <a href="{{ route('paiements.index') }}" class="btn btn-outline-primary btn-sm ms-1">
                <i class=" ri-wallet-3-line me-1"></i> Espace Paiements
            </a>
        </div>
    </div>

    @if (session()->has('succes'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class=" ri-checkbox-circle-fill me-2"></i>{{ session('succes') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class=" ri-alert-fill me-2"></i>{{ session('echec') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- ONGLETS DE NAVIGATION -->
    <ul class="nav nav-pills nav-fill mb-3 bg-white p-2 rounded shadow-sm" id="previsionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="tab-perso-btn" data-bs-toggle="pill" data-bs-target="#tab-perso" type="button" role="tab">
                <i class=" ri-user-settings-line me-1"></i> 1. Échéancier personnalisé par étudiant
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-classe-btn" data-bs-toggle="pill" data-bs-target="#tab-classe" type="button" role="tab">
                <i class=" ri-play-list-line me-1"></i> 2. Génération automatique pour toute une classe
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-liste-btn" data-bs-toggle="pill" data-bs-target="#tab-liste" type="button" role="tab">
                <i class=" ri-table-line me-1"></i> 3. Consultation des prévisions existantes
            </button>
        </li>
    </ul>

    <div class="tab-content" id="previsionTabsContent">

        <!-- ═══ ONGLET 1 : ÉCHÉANCIER PERSONNALISÉ PAR ÉTUDIANT ═══ -->
        <div class="tab-pane fade show active" id="tab-perso" role="tabpanel">
            <div class="card p-4 shadow-sm border-0 mb-3">
                <h5 class="fw-bold text-dark mb-3"><i class=" ri-search-line me-2 text-primary"></i>Sélectionner un étudiant</h5>
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <label class="form-label fw-semibold" for="anneeAcademiquePerso">Année académique <span class="text-danger">*</span></label>
                        <select id="anneeAcademiquePerso" class="form-control form-control-sm select-native">
                            <option value="">Sélectionner l'année...</option>
                            @foreach ($annees as $a)
                            <option value="{{ $a->id_Annee_Acad }}" {{ $loop->first ? 'selected' : '' }}>{{ $a->ANNEE_ACAD }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <label class="form-label fw-semibold" for="anneeEtudePerso">Filière / Classe <span class="text-danger">*</span></label>
                        <select id="anneeEtudePerso" class="form-control form-control-sm select-native">
                            <option value="">Sélectionner une classe...</option>
                            @foreach ($filieres as $f)
                            <option value="{{ $f->Code_Annee_Etude }}">{{ $f->Libelle_AnEtude }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-12 col-sm-12">
                        <label class="form-label fw-semibold" for="etudiantPerso">Étudiant de la classe <span class="text-danger">*</span></label>
                        <input type="text" id="filtreEtudiantRapide" class="form-control form-control-sm mb-1" placeholder="Filtrer par nom ou matricule..." style="display:none;">
                        <select id="etudiantPerso" class="form-control form-control-sm select-native" disabled>
                            <option value="">Choisissez d'abord la classe...</option>
                        </select>
                        <small class="text-muted" id="etudiantHelp">Seuls les inscrits de la classe choisie sont affichés.</small>
                    </div>
                </div>
            </div>

            <!-- ZONE ÉDITEUR DE TRANCHES (DYNAMIQUE) -->
            <div id="editeurContainer" style="display:none;">
                <!-- FICHE RÉSUMÉ ÉTUDIANT -->
                <div class="card p-3 shadow-sm border-0 bg-light mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h5 class="mb-1 fw-bold text-navy" id="infoNomEtu">—</h5>
                            <div class="text-muted small">
                                Matricule : <b id="infoMatricule" class="text-dark">—</b> &bull;
                                Classe : <b id="infoFiliere" class="text-dark">—</b> &bull;
                                Année : <b id="infoAnnee" class="text-dark">—</b>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end mt-2 mt-md-0">
                            <div class="d-inline-block text-start bg-white p-2 px-3 rounded border">
                                <div class="small text-muted">Scolarité : <b id="infoScolarite" class="text-dark">—</b></div>
                                <div class="small">
                                    <span class="text-success fw-bold">Versé : <span id="infoVerse">—</span></span> |
                                    <span class="text-danger fw-bold">Reste : <span id="infoReste">—</span></span>
                                </div>
                            </div>
                            <div class="mt-1" id="badgeStatutPrevision"></div>
                        </div>
                    </div>
                </div>

                <!-- TABLEAU ÉDITABLE DES TRANCHES -->
                <div class="card p-4 shadow-sm border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold mb-0"><i class=" ri-list-check me-2 text-primary"></i>Échéancier des tranches</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <!-- Menu Découpage Mensuel (Plan Social) -->
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success btn-sm dropdown-toggle fw-semibold" data-bs-toggle="dropdown" aria-expanded="false" id="btnPlanMensuelDropdown">
                                    <i class=" ri-calendar-line me-1"></i> Plan mensuel
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border p-1" style="min-width: 280px; z-index: 1050;">
                                    <li><h6 class="dropdown-header text-uppercase fw-bold" style="font-size: 0.75rem;">Découpage en mensualités</h6></li>
                                    <li>
                                        <a class="dropdown-item py-2 btn-plan-rapide" href="javascript:void(0)" data-mois="4">
                                            <i class=" ri-calendar-line me-2 text-secondary"></i>4 mensualités <span class="text-muted small ms-1">(Octobre à Janvier)</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 btn-plan-rapide" href="javascript:void(0)" data-mois="6">
                                            <i class=" ri-calendar-line me-2 text-secondary"></i>6 mensualités <span class="text-muted small ms-1">(Octobre à Mars)</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 btn-plan-rapide" href="javascript:void(0)" data-mois="8">
                                            <i class=" ri-calendar-check-line me-2 text-success"></i>8 mensualités <span class="text-muted small ms-1">(Octobre à Mai)</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 btn-plan-rapide" href="javascript:void(0)" data-mois="10">
                                            <i class=" ri-calendar-line me-2 text-secondary"></i>10 mensualités <span class="text-muted small ms-1">(Octobre à Juillet)</span>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)" id="btnOuvrirModalPlan">
                                            <i class=" ri-settings-3-line me-2 text-primary"></i>Personnaliser le plan...
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetDefaut" title="Rétablir les 3 ou 4 tranches officielles">
                                <i class=" ri-arrow-go-back-line me-1"></i> Barème officiel
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAjouterTranche">
                                <i class=" ri-add-circle-line me-1"></i> Ajouter une tranche
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="tableTranches">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width: 15%;">Tranche</th>
                                    <th style="width: 30%;">Date d'échéance (Limite)</th>
                                    <th style="width: 35%;">Montant prévu (F CFA)</th>
                                    <th style="width: 20%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tranchesTbody">
                                <!-- Rempli en JS -->
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th class="text-end" colspan="2">TOTAL DES TRANCHES :</th>
                                    <th id="totalTranchesAffiche" class="fw-bold fs-6">0 F CFA</th>
                                    <th id="indicateurEquilibre"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="alert alert-info py-2 small mb-3">
                        <i class=" ri-information-line me-1"></i> Vous pouvez modifier librement les dates et montants selon l'accord conclu avec l'étudiant ou son tuteur. Les versements déjà enregistrés viendront automatiquement solder les premières tranches.
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-success btn-lg px-4" id="btnEnregistrerEcheancier">
                            <i class=" ri-check-line me-1"></i> Enregistrer cet échéancier
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ ONGLET 2 : GÉNÉRATION AUTOMATIQUE POUR TOUTE UNE CLASSE ═══ -->
        <div class="tab-pane fade" id="tab-classe" role="tabpanel">
            <div class="card p-4 shadow-sm border-0 border-top border-primary border-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary-subtle text-primary p-3 rounded me-3">
                        <i class=" ri-magic-line fs-2"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Générer les tranches en 1 clic pour toute une classe</h4>
                        <p class="text-muted small mb-0">Applique automatiquement le barème officiel de l'IUSAA à chaque étudiant inscrit.</p>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-navy mb-2"><i class=" ri-calendar-line me-2"></i>1ère et 3ème année (3 tranches en parts égales)</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><b>1ère Tranche (33.3%)</b> : Échéance au <b>30 Septembre</b></li>
                                <li><b>2ème Tranche (33.3%)</b> : Échéance au <b>30 Novembre</b></li>
                                <li><b>3ème Tranche (33.4%)</b> : Échéance au <b>21 Février</b></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-navy mb-2"><i class=" ri-calendar-line me-2"></i>2ème année (4 tranches en parts égales)</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><b>1ère Tranche (25%)</b> : Échéance au <b>30 Septembre</b></li>
                                <li><b>2ème Tranche (25%)</b> : Échéance au <b>30 Novembre</b></li>
                                <li><b>3ème Tranche (25%)</b> : Échéance au <b>31 Janvier</b></li>
                                <li><b>4ème Tranche (25%)</b> : Échéance au <b>31 Mars</b></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form action="{{ route('previsions.generer-classe') }}" method="POST" id="formGenererClasse">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <label class="form-label fw-bold" for="anneeAcademiqueClasse">Année académique <span class="text-danger">*</span></label>
                            <select name="anneeAcademique" id="anneeAcademiqueClasse" class="form-control select-native" required>
                                <option value="">Sélectionner l'année...</option>
                                @foreach ($annees as $a)
                                <option value="{{ $a->id_Annee_Acad }}" {{ $loop->first ? 'selected' : '' }}>{{ $a->ANNEE_ACAD }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <label class="form-label fw-bold" for="anneeEtudeClasse">Filière / Classe cible <span class="text-danger">*</span></label>
                            <select name="anneeEtude" id="anneeEtudeClasse" class="form-control select-native" required>
                                <option value="">Sélectionner une classe...</option>
                                <option value="toutes" class="fw-bold text-primary">-- TOUTES LES FILIÈRES DE L'ANNÉE --</option>
                                @foreach ($filieres as $f)
                                <option value="{{ $f->Code_Annee_Etude }}">{{ $f->Libelle_AnEtude }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="ecraser" value="1" id="checkEcraser">
                        <label class="form-check-label text-danger fw-semibold" for="checkEcraser">
                            Écraser les échéanciers déjà configurés pour ces étudiants
                        </label>
                        <div class="form-text">Si décoché, les étudiants qui ont déjà un échéancier sur-mesure ne seront pas modifiés.</div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-primary btn-lg" id="btnLancerGeneration">
                            <i class=" ri-play-circle-fill me-1"></i> Lancer la génération automatique
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═══ ONGLET 3 : CONSULTATION DES PRÉVISIONS EXISTANTES ═══ -->
        <div class="tab-pane fade" id="tab-liste" role="tabpanel">
            <div class="card p-3 shadow-sm border-0 mb-3">
                <form action="{{ route('previsions.index') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Filtrer par année :</label>
                        <select name="annee_filtre" class="form-control form-control-sm select-native">
                            <option value="">Toutes les années</option>
                            @foreach ($annees as $a)
                            <option value="{{ $a->id_Annee_Acad }}" {{ (string)$anneeFiltre === (string)$a->id_Annee_Acad ? 'selected' : '' }}>{{ $a->ANNEE_ACAD }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Filtrer par filière :</label>
                        <select name="filiere_filtre" class="form-control form-control-sm select-native">
                            <option value="">Toutes les filières</option>
                            @foreach ($filieres as $f)
                            <option value="{{ $f->Code_Annee_Etude }}" {{ (string)$filiereFiltre === (string)$f->Code_Annee_Etude ? 'selected' : '' }}>{{ $f->Libelle_AnEtude }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class=" ri-filter-line me-1"></i> Filtrer</button>
                    </div>
                </form>
            </div>

            <div class="card p-4 shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class=" ri-table-line me-2 text-secondary"></i>Liste des tranches enregistrées en base</h5>
                    <span class="badge bg-secondary">{{ $previsions->total() }} tranche(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-dark text-center small">
                            <tr>
                                <th>Matricule</th>
                                <th>Étudiant</th>
                                <th>Classe / Année</th>
                                <th>Échéance</th>
                                <th>Montant prévu</th>
                                <th>Reste</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($previsions as $p)
                            <tr class="text-center">
                                <td><b class="text-navy">{{ $p->MATRICULE }}</b></td>
                                <td class="text-start">{{ $p->etudiant ? $p->etudiant->fullname() : 'N/A' }}</td>
                                <td>{{ $p->ID_FILIERE }} <span class="badge bg-light text-dark border">{{ $p->anneeacademique->ANNEE_ACAD ?? '' }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($p->echeance)->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ number_format($p->montant_a_payer, 0, ',', ' ') }} F</td>
                                <td>{{ number_format($p->reste_a_payer, 0, ',', ' ') }} F</td>
                                <td>
                                    @if ($p->paye)
                                    <span class="badge bg-success"><i class=" ri-check-line"></i> Réglé</span>
                                    @else
                                    <span class="badge bg-warning text-dark"><i class=" ri-time-line"></i> À solder</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('previsions.destroy', $p->id) }}" method="POST" id="form-del-prev-{{ $p->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-2" title="Supprimer cette tranche"
                                            onclick="confirmDelete(document.getElementById('form-del-prev-{{ $p->id }}'), 'la tranche du <strong>{{ \Carbon\Carbon::parse($p->echeance)->format('d/m/Y') }}</strong>')">
                                            <i class=" ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class=" ri-inbox-line fs-3 d-block mb-1"></i> Aucune prévision trouvée pour ces filtres.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $previsions->withQueryString()->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ═══ MODALE CONFIGURATION DU PLAN MENSUEL ═══ -->
<div class="modal fade" id="modalPlanMensuelConfig" tabindex="-1" aria-labelledby="modalPlanMensuelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 0.75rem;">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-semibold" id="modalPlanMensuelLabel">
                    <i class=" ri-calendar-line me-2"></i>Configuration du plan mensuel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border d-flex align-items-center py-2 px-3 mb-3">
                    <i class=" ri-information-line text-primary fs-5 me-2"></i>
                    <div class="small text-muted">
                        Répartition de la scolarité de <strong id="modalScolariteAffichee" class="text-dark">0 F CFA</strong> en mensualités régulières.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">Nombre de mensualités <span class="text-danger">*</span></label>
                        <select id="planNbMois" class="form-control form-control-sm select-native">
                            <option value="4">4 mensualités (1 semestre)</option>
                            <option value="5">5 mensualités</option>
                            <option value="6">6 mensualités (Octobre à Mars)</option>
                            <option value="7">7 mensualités</option>
                            <option value="8" selected>8 mensualités (Recommandé : Octobre à Mai)</option>
                            <option value="9">9 mensualités</option>
                            <option value="10">10 mensualités (Année complète)</option>
                            <option value="12">12 mensualités (Année civile)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">Mois de départ <span class="text-danger">*</span></label>
                        <select id="planMoisDepart" class="form-control form-control-sm select-native">
                            <option value="9">Septembre</option>
                            <option value="10" selected>Octobre (Rentrée)</option>
                            <option value="11">Novembre</option>
                            <option value="12">Décembre</option>
                            <option value="1">Janvier</option>
                            <option value="2">Février</option>
                            <option value="3">Mars</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-dark">Jour de versement souhaité</label>
                        <select id="planJourPaiement" class="form-control form-control-sm select-native">
                            <option value="fin" selected>Dernier jour du mois (28, 30 ou 31) — Standard</option>
                            <option value="25">Le 25 du mois (Salaires fin de mois)</option>
                            <option value="28">Le 28 du mois</option>
                            <option value="5">Le 05 du mois suivant</option>
                            <option value="10">Le 10 du mois suivant</option>
                            <option value="15">Le 15 du mois</option>
                        </select>
                    </div>
                </div>

                <!-- Simulation en temps réel -->
                <div class="card border border-2 border-success-subtle bg-light-subtle mt-3 p-3 rounded-3 text-center">
                    <span class="text-muted small fw-semibold">Estimation de la mensualité :</span>
                    <h4 class="fw-bold text-success mb-0 mt-1" id="simulationMensualite">0 F CFA / mois</h4>
                    <small class="text-muted" id="simulationPeriode">sur 8 mensualités</small>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-end gap-2">
                <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success rounded-3 px-4 fw-bold" id="btnAppliquerPlanMensuel">
                    <i class=" ri-checkbox-circle-line me-1"></i> Appliquer à l'échéancier
                </button>
            </div>
        </div>
    </div>
</div>

</section>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Détruire tout résidu éventuel de TomSelect créé par layout.blade.php
    ['anneeAcademiquePerso', 'anneeEtudePerso', 'etudiantPerso', 'anneeAcademiqueClasse', 'anneeEtudeClasse', 'annee_filtre', 'filiere_filtre'].forEach(function(id) {
        let el = document.getElementById(id);
        if (el && el.tomselect) {
            try { el.tomselect.destroy(); } catch(e) {}
        }
    });

    let currentEtudiantData = null;
    let listeEtudiantsActuelle = [];

    // Charger les étudiants dès que l'année ou la filière change dans l'onglet personnalisé
    function chargerEtudiantsPerso() {
        let annee = $('#anneeAcademiquePerso').val();
        let filiere = $('#anneeEtudePerso').val();

        $('#editeurContainer').hide();
        $('#filtreEtudiantRapide').hide().val('');
        currentEtudiantData = null;
        listeEtudiantsActuelle = [];

        if (!annee || !filiere) {
            $('#etudiantPerso').attr('disabled', true).html('<option value="">Choisissez d\'abord la classe...</option>');
            return;
        }

        $('#etudiantPerso').attr('disabled', true).html('<option value="">Chargement des étudiants...</option>');

        $.ajax({
            url: '/api/ins/' + annee + '/' + encodeURIComponent(filiere),
            method: 'GET',
            success: function(response) {
                if (!response || response.length === 0) {
                    $('#etudiantPerso').attr('disabled', true).html('<option value="">Aucun étudiant inscrit dans cette classe</option>');
                    return;
                }

                listeEtudiantsActuelle = response;
                afficherOptionsEtudiants(response);
                $('#filtreEtudiantRapide').show();
            },
            error: function() {
                $('#etudiantPerso').attr('disabled', true).html('<option value="">Erreur de chargement des étudiants</option>');
            }
        });
    }

    function afficherOptionsEtudiants(etudiants) {
        let options = '<option value="">-- Sélectionner l\'étudiant (' + etudiants.length + ' inscrits) --</option>';
        etudiants.forEach(function(item) {
            options += '<option value="' + item.MATRICULE + '">' + item.nomEtu + ' ' + item.prenomsEtu + ' (' + item.MATRICULE + ')</option>';
        });
        $('#etudiantPerso').html(options).removeAttr('disabled');
    }

    // Filtre textuel en direct dans la liste des étudiants
    $('#filtreEtudiantRapide').on('input', function() {
        let q = $(this).val().toLowerCase().trim();
        if (!q) {
            afficherOptionsEtudiants(listeEtudiantsActuelle);
            return;
        }
        let filtres = listeEtudiantsActuelle.filter(function(e) {
            let texte = (e.nomEtu + ' ' + e.prenomsEtu + ' ' + e.MATRICULE).toLowerCase();
            return texte.includes(q);
        });
        afficherOptionsEtudiants(filtres);
        if (filtres.length === 1) {
            $('#etudiantPerso').val(filtres[0].MATRICULE).trigger('change');
        }
    });

    $('#anneeAcademiquePerso, #anneeEtudePerso').on('change', chargerEtudiantsPerso);

    // Quand l'étudiant est sélectionné, charger son échéancier
    $('#etudiantPerso').on('change', function() {
        let mat = $(this).val();
        let annee = $('#anneeAcademiquePerso').val();
        let filiere = $('#anneeEtudePerso').val();

        if (!mat) {
            $('#editeurContainer').hide();
            return;
        }

        $('#editeurContainer').show();
        $('#tranchesTbody').html('<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Chargement de l\'échéancier...</td></tr>');

        $.ajax({
            url: '{{ route("previsions.etudiant-echeancier") }}',
            method: 'GET',
            data: { matricule: mat, anneeAcademique: annee, anneeEtude: filiere },
            success: function(data) {
                currentEtudiantData = data;
                afficherFicheEtudiant(data);
                rendreTableauTranches(data.tranches);
            },
            error: function(err) {
                alert('Erreur lors du chargement des données : ' + (err.responseJSON ? err.responseJSON.error : 'Erreur inconnue'));
                $('#editeurContainer').hide();
            }
        });
    });

    function afficherFicheEtudiant(data) {
        $('#infoNomEtu').text(data.nom_complet);
        $('#infoMatricule').text(data.matricule);
        $('#infoFiliere').text(data.filiere_libelle);
        $('#infoAnnee').text(data.annee_acad);
        $('#infoScolarite').text(formatNombre(data.frais_total) + ' F CFA');
        $('#infoVerse').text(formatNombre(data.cumul_paye) + ' F');
        $('#infoReste').text(formatNombre(data.reste_total) + ' F');

        if (data.existe_en_base) {
            $('#badgeStatutPrevision').html('<span class="badge bg-success-subtle text-success border border-success"><i class=" ri-checkbox-circle-line me-1"></i>Échéancier déjà configuré en base</span>');
        } else {
            $('#badgeStatutPrevision').html('<span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class=" ri-time-line me-1"></i>Tranches suggérées par défaut (non encore enregistrées)</span>');
        }
    }

    function rendreTableauTranches(tranches) {
        let html = '';
        tranches.forEach(function(tr, idx) {
            let label = tr.label || ('Tranche ' + (idx + 1));
            let dateVal = tr.date || '';
            let montantVal = tr.montant || 0;

            html += '<tr data-idx="' + idx + '">';
            html += '<td class="text-center fw-bold text-navy"><span class="badge bg-dark">T' + (idx + 1) + '</span><br><small class="text-muted">' + label + '</small></td>';
            html += '<td><input type="date" class="form-control form-control-sm tranche-date" value="' + dateVal + '" required></td>';
            html += '<td><div class="input-group input-group-sm"><input type="number" class="form-control tranche-montant" value="' + montantVal + '" min="0" step="1000" required><span class="input-group-text">F CFA</span></div></td>';
            html += '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-suppr-tranche" title="Supprimer cette tranche"><i class=" ri-delete-bin-line"></i></button></td>';
            html += '</tr>';
        });

        $('#tranchesTbody').html(html);
        recalculerTotaux();
    }

    // Recalcul en temps réel de la somme des tranches
    function recalculerTotaux() {
        let total = 0;
        $('.tranche-montant').each(function() {
            total += parseInt($(this).val()) || 0;
        });

        $('#totalTranchesAffiche').text(formatNombre(total) + ' F CFA');

        if (currentEtudiantData) {
            let cible = currentEtudiantData.frais_total;
            let diff = cible - total;

            if (diff === 0) {
                $('#indicateurEquilibre').html('<span class="badge bg-success"><i class=" ri-checkbox-circle-line me-1"></i>Équilibré (100% de la scolarité)</span>');
            } else if (diff > 0) {
                $('#indicateurEquilibre').html('<span class="badge bg-warning text-dark"><i class=" ri-alert-line me-1"></i>Manque ' + formatNombre(diff) + ' F CFA</span>');
            } else {
                $('#indicateurEquilibre').html('<span class="badge bg-danger"><i class=" ri-error-warning-line me-1"></i>Dépassement de ' + formatNombre(Math.abs(diff)) + ' F CFA</span>');
            }
        }
    }

    $(document).on('input', '.tranche-montant', recalculerTotaux);

    // Supprimer une tranche
    $(document).on('click', '.btn-suppr-tranche', function() {
        if ($('#tranchesTbody tr').length <= 1) {
            alert('Il doit rester au moins une tranche.');
            return;
        }
        $(this).closest('tr').remove();
        // Renuméroter les tranches
        $('#tranchesTbody tr').each(function(i) {
            $(this).find('td:first').html('<span class="badge bg-dark">T' + (i + 1) + '</span><br><small class="text-muted">Tranche ' + (i + 1) + '</small>');
        });
        recalculerTotaux();
    });

    // Ajouter une tranche
    $('#btnAjouterTranche').on('click', function() {
        let count = $('#tranchesTbody tr').length;
        let today = new Date().toISOString().split('T')[0];
        let html = '<tr data-idx="' + count + '">';
        html += '<td class="text-center fw-bold text-navy"><span class="badge bg-dark">T' + (count + 1) + '</span><br><small class="text-muted">Tranche ' + (count + 1) + '</small></td>';
        html += '<td><input type="date" class="form-control form-control-sm tranche-date" value="' + today + '" required></td>';
        html += '<td><div class="input-group input-group-sm"><input type="number" class="form-control tranche-montant" value="0" min="0" step="1000" required><span class="input-group-text">F CFA</span></div></td>';
        html += '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-suppr-tranche" title="Supprimer cette tranche"><i class=" ri-delete-bin-line"></i></button></td>';
        html += '</tr>';
        $('#tranchesTbody').append(html);
        recalculerTotaux();
    });

    // Réinitialiser selon barème officiel
    function appliquerTranchesDefaut() {
        let niveau = currentEtudiantData.niveau;
        let total = currentEtudiantData.frais_total;
        let parts = (currentEtudiantData.annee_acad || '').split('-');
        let y = parseInt(parts[0]) || 2026;
        let y1 = parseInt(parts[1]) || (y + 1);

        let tranches = [];
        if (niveau === 2) {
            let p = Math.round(total / 4);
            tranches = [
                { label: 'Tranche 1', date: y + '-09-30', montant: p },
                { label: 'Tranche 2', date: y + '-11-30', montant: p },
                { label: 'Tranche 3', date: y1 + '-01-31', montant: p },
                { label: 'Tranche 4', date: y1 + '-03-31', montant: Math.max(0, total - (p * 3)) },
            ];
        } else {
            let p = Math.round(total / 3);
            tranches = [
                { label: 'Tranche 1', date: y + '-09-30', montant: p },
                { label: 'Tranche 2', date: y + '-11-30', montant: p },
                { label: 'Tranche 3', date: y1 + '-02-21', montant: Math.max(0, total - (p * 2)) },
            ];
        }
        rendreTableauTranches(tranches);
    }

    $('#btnResetDefaut').on('click', function() {
        if (!currentEtudiantData) return;
        confirmAction({
            title: 'Réinitialiser l\'échéancier',
            message: 'Voulez-vous réinitialiser les tranches de cet étudiant selon le <strong>barème officiel</strong> ?',
            icon: 'ri-arrow-go-back-line',
            gradient: 'linear-gradient(135deg,#f57c00,#ffb74d)',
            confirmText: 'Réinitialiser',
            confirmClass: 'btn-warning text-dark',
            confirmIcon: 'ri-arrow-go-back-line',
            onConfirm: function() {
                appliquerTranchesDefaut();
            }
        });
    });

    // ══════════════════════════════════════════════════════════════
    // PLAN MENSUEL (DÉCOUPAGE SOCIAL)
    // ══════════════════════════════════════════════════════════════
    const nomsMoisFr = [
        '', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
    ];

    function construirePlanMensuel(nbMois, startMonth = 10, jourPaiement = 'fin') {
        if (!currentEtudiantData) return;

        let total = currentEtudiantData.frais_total;
        let parts = (currentEtudiantData.annee_acad || '').split('-');
        let y = parseInt(parts[0]) || new Date().getFullYear();

        // Montant de base arrondi à 500 F CFA
        let p = Math.round((total / nbMois) / 500) * 500;
        let cumul = 0;
        let tranches = [];

        let curMonth = parseInt(startMonth);
        let curYear = y;

        // Si le mois de départ est avant septembre (ex: Janvier, Février), c'est l'année N+1
        if (curMonth < 9) {
            curYear = y + 1;
        }

        for (let i = 0; i < nbMois; i++) {
            let dateStr = '';
            if (jourPaiement === 'fin') {
                let lastDayObj = new Date(curYear, curMonth, 0);
                let mm = String(curMonth).padStart(2, '0');
                let dd = String(lastDayObj.getDate()).padStart(2, '0');
                dateStr = `${curYear}-${mm}-${dd}`;
            } else {
                let j = parseInt(jourPaiement) || 28;
                let mm = String(curMonth).padStart(2, '0');
                let dd = String(j).padStart(2, '0');
                dateStr = `${curYear}-${mm}-${dd}`;
            }

            let mnt = (i === nbMois - 1) ? Math.max(0, total - cumul) : p;
            cumul += mnt;

            let nomMois = nomsMoisFr[curMonth] || ('Mois ' + curMonth);
            tranches.push({
                label: `Mois ${i + 1} (${nomMois} ${curYear})`,
                date: dateStr,
                montant: mnt
            });

            curMonth++;
            if (curMonth > 12) {
                curMonth = 1;
                curYear++;
            }
        }

        rendreTableauTranches(tranches);
    }

    // Clic sur option rapide du menu dropdown (4, 6, 8 ou 10 mensualités)
    $(document).on('click', '.btn-plan-rapide', function(e) {
        e.preventDefault();
        if (!currentEtudiantData) {
            alert('Veuillez d\'abord sélectionner un étudiant.');
            return;
        }
        let nbMois = parseInt($(this).data('mois')) || 8;
        let total = currentEtudiantData.frais_total;
        let est = Math.round(total / nbMois);

        confirmAction({
            title: `Plan sur ${nbMois} mensualités`,
            message: `Répartir la scolarité de <strong>${formatNombre(total)} F CFA</strong> en <strong>${nbMois} mensualités</strong> d'environ <strong>${formatNombre(est)} F CFA / mois</strong> ?`,
            icon: 'ri-calendar-line',
            gradient: 'linear-gradient(135deg,#1e293b,#334155)',
            confirmText: 'Appliquer ce plan',
            confirmClass: 'btn-dark',
            confirmIcon: 'ri-check-line',
            onConfirm: function() {
                construirePlanMensuel(nbMois, 10, 'fin');
            }
        });
    });

    // Ouvrir la modale de personnalisation poussée
    $('#btnOuvrirModalPlan').on('click', function(e) {
        e.preventDefault();
        if (!currentEtudiantData) {
            alert('Veuillez d\'abord sélectionner un étudiant.');
            return;
        }
        $('#modalScolariteAffichee').text(formatNombre(currentEtudiantData.frais_total) + ' F CFA');
        actualiserSimulationModal();
        EduUI.Modal.getOrCreateInstance(document.getElementById('modalPlanMensuelConfig')).show();
    });

    function actualiserSimulationModal() {
        if (!currentEtudiantData) return;
        let nbMois = parseInt($('#planNbMois').val()) || 8;
        let total = currentEtudiantData.frais_total;
        let mens = Math.round((total / nbMois) / 500) * 500;
        let startM = parseInt($('#planMoisDepart').val()) || 10;
        let nomDep = nomsMoisFr[startM] || 'Début';

        let endM = (startM + nbMois - 1) % 12;
        if (endM === 0) endM = 12;
        let nomFin = nomsMoisFr[endM] || 'Fin';

        $('#simulationMensualite').text(formatNombre(mens) + ' F CFA / mois');
        $('#simulationPeriode').text(`de ${nomDep} à ${nomFin} (${nbMois} mensualités au total)`);
    }

    $('#planNbMois, #planMoisDepart, #planJourPaiement').on('change', actualiserSimulationModal);

    $('#btnAppliquerPlanMensuel').on('click', function() {
        let nbMois = parseInt($('#planNbMois').val()) || 8;
        let startMonth = parseInt($('#planMoisDepart').val()) || 10;
        let jour = $('#planJourPaiement').val();

        construirePlanMensuel(nbMois, startMonth, jour);

        let modalEl = document.getElementById('modalPlanMensuelConfig');
        let modalInst = EduUI.Modal.getInstance(modalEl);
        if (modalInst) modalInst.hide();
    });

    // Envoi AJAX de l'échéancier personnalisé
    function envoyerEcheancier(tranches) {
        let btn = $('#btnEnregistrerEcheancier');
        btn.attr('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...');

        $.ajax({
            url: '{{ route("previsions.enregistrer-echeancier") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                matricule: currentEtudiantData.matricule,
                anneeAcademique: $('#anneeAcademiquePerso').val(),
                anneeEtude: $('#anneeEtudePerso').val(),
                tranches: tranches
            },
            success: function(resp) {
                btn.removeAttr('disabled').html('<i class=" ri-check-line me-1"></i> Enregistrer cet échéancier');
                confirmAction({
                    title: 'Succès !',
                    message: resp.message || 'L\'échéancier a été enregistré avec succès.',
                    icon: 'ri-checkbox-circle-fill',
                    gradient: 'linear-gradient(135deg,#2e7d32,#66bb6a)',
                    confirmText: 'Parfait',
                    confirmClass: 'btn-success',
                    confirmIcon: 'ri-check-line',
                    onConfirm: function() {}
                });
                $('#badgeStatutPrevision').html('<span class="badge bg-success"><i class=" ri-checkbox-circle-line me-1"></i>Échéancier enregistré en base</span>');
            },
            error: function(err) {
                btn.removeAttr('disabled').html('<i class=" ri-check-line me-1"></i> Enregistrer cet échéancier');
                confirmAction({
                    title: 'Erreur',
                    message: (err.responseJSON ? err.responseJSON.message : 'Erreur lors de l\'enregistrement.'),
                    icon: 'ri-alert-fill',
                    gradient: 'linear-gradient(135deg,#c62828,#ef5350)',
                    confirmText: 'Fermer',
                    confirmClass: 'btn-danger',
                    confirmIcon: 'ri-close-line',
                    onConfirm: function() {}
                });
            }
        });
    }

    // Enregistrer l'échéancier personnalisé
    $('#btnEnregistrerEcheancier').on('click', function() {
        if (!currentEtudiantData) return;

        let tranches = [];
        let erreur = false;

        $('#tranchesTbody tr').each(function() {
            let dateVal = $(this).find('.tranche-date').val();
            let montantVal = parseInt($(this).find('.tranche-montant').val());

            if (!dateVal) {
                alert('Veuillez renseigner une date pour chaque tranche.');
                erreur = true;
                return false;
            }
            if (isNaN(montantVal) || montantVal < 0) {
                alert('Veuillez renseigner un montant valide pour chaque tranche.');
                erreur = true;
                return false;
            }

            tranches.push({ date: dateVal, montant: montantVal });
        });

        if (erreur || tranches.length === 0) return;

        let totalSaisi = tranches.reduce((sum, t) => sum + t.montant, 0);
        if (totalSaisi !== currentEtudiantData.frais_total) {
            confirmAction({
                title: 'Écart sur la scolarité',
                message: 'Le total des tranches (<strong>' + formatNombre(totalSaisi) + ' F</strong>) ne correspond pas à la scolarité totale de l\'étudiant (<strong>' + formatNombre(currentEtudiantData.frais_total) + ' F</strong>).<br><br>Souhaitez-vous tout de même enregistrer cet échéancier spécifique ?',
                icon: 'ri-alert-fill',
                gradient: 'linear-gradient(135deg,#f57c00,#ff9800)',
                confirmText: 'Enregistrer quand même',
                confirmClass: 'btn-warning text-dark',
                confirmIcon: 'ri-checkbox-circle-line',
                onConfirm: function() {
                    envoyerEcheancier(tranches);
                }
            });
            return;
        }

        envoyerEcheancier(tranches);
    });

    // Lancer la génération automatique pour une classe (Onglet 2)
    $('#btnLancerGeneration').on('click', function(e) {
        e.preventDefault();
        const annee = $('#anneeAcademiqueClasse').val();
        const filiere = $('#anneeEtudeClasse').val();
        if (!annee) {
            alert('Veuillez sélectionner une année académique.');
            return;
        }
        if (!filiere) {
            alert('Veuillez sélectionner une filière ou classe cible.');
            return;
        }

        const filiereTexte = $('#anneeEtudeClasse option:selected').text();
        const ecraser = $('#checkEcraser').is(':checked');
        const ecraserTexte = ecraser ? '<br><span class="text-danger fw-bold"><i class=" ri-alert-line me-1"></i>Attention : les échéanciers personnalisés existants seront écrasés !</span>' : '';

        confirmAction({
            title: 'Génération automatique des tranches',
            message: 'Voulez-vous générer les tranches d\'échéance officielles pour <strong>' + filiereTexte + '</strong> ?' + ecraserTexte,
            icon: 'ri-calendar-check-line',
            gradient: 'linear-gradient(135deg,#1a73e8,#4285f4)',
            confirmText: 'Lancer la génération',
            confirmClass: 'btn-primary',
            confirmIcon: 'ri-play-circle-fill',
            onConfirm: function() {
                document.getElementById('formGenererClasse').submit();
            }
        });
    });

    function formatNombre(n) {
        return (n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
});
</script>
@endsection
