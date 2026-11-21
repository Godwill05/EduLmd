@extends('layout')
@section('title')
Récapitulatif du semestre
@endsection
@section('content')
<style>
    .student-card {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
    }
    .student-card:hover {
        background-color: #f8fafc;
        border-color: #0d6efd;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
    }
    .student-card.is-selected {
        background-color: #eff6ff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 1px #3b82f6;
    }
    .student-card.is-excluded-pv {
        background-color: #f8fafc !important;
        border-color: #f1aeb5 !important;
        opacity: 0.60;
    }
    .student-card.is-excluded-pv:hover {
        opacity: 0.90;
    }
    .badge-excluded {
        font-size: 0.68rem;
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        padding: 1px 5px;
        border-radius: 4px;
        font-weight: 600;
        letter-spacing: 0.2px;
    }
    .badge-included {
        font-size: 0.68rem;
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        padding: 1px 5px;
        border-radius: 4px;
        font-weight: 600;
        letter-spacing: 0.2px;
    }
    .student-grid-container {
        max-height: 250px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .student-grid-container::-webkit-scrollbar {
        width: 6px;
    }
    .student-grid-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .student-grid-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .student-grid-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .badge-mode {
        font-size: 0.78rem;
        letter-spacing: 0.3px;
        transition: all 0.2s ease;
    }
    .select-semestre-custom {
        background-color: #ffffff !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 16px 12px !important;
        padding-right: 2.25rem !important;
        cursor: pointer !important;
    }
</style>

@php
    // Préchargement de tous les semestres côté serveur, regroupés par année d'étude
    $tousLesSemestres = [];
    foreach (\App\Models\SemestreAnneeEtude::with('classe')->get() as $sae) {
        $cle = (string) $sae->Code_Annee_Etude;
        $tousLesSemestres[$cle][] = [
            'id'  => $sae->id_S_Gr_Spe,
            'nom' => optional($sae->classe)->Nom_Classe ?: ('Semestre ' . $sae->id_S_Gr_Spe),
        ];
    }
@endphp

<section class="section">
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class=" ri-checkbox-circle-fill me-2"></i>{{ session('succes') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class=" ri-alert-fill me-2"></i>{{ session('echec') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="card p-4 shadow-sm border-0 mb-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class=" ri-graduation-cap-fill me-2"></i>Délibération & Relevés de notes semestriels
                    </h5>
                    <small class="text-muted">Sélectionnez les filtres pour générer le Procès-Verbal semestriel ou les relevés individuels des étudiants.</small>
                </div>
            </div>

            <form action="{{ route('impression.semestre') }}" method="POST" id="recapSemestre" target="_blank">
                @csrf
                <div class="row g-3 align-items-end">
                    <!-- 1. Année d'étude -->
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold text-secondary small mb-1" for="anneeEtude">
                                <i class=" ri-calendar-line me-1 text-primary"></i>Année d'étude <span class="text-danger">*</span>
                            </label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-control-sm form-select" onchange="chargerSemestres()" required>
                                <option value="">Sélectionner une année d'étude...</option>
                                @foreach (\App\Models\AnneeEtude::all() as $anneeEtude)
                                <option value="{{ $anneeEtude->Code_Annee_Etude }}">{{ $anneeEtude->Libelle_AnEtude }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 2. Année académique -->
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold text-secondary small mb-1" for="anneeAcademique">
                                <i class=" ri-history-line me-1 text-primary"></i>Année académique <span class="text-danger">*</span>
                            </label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-control-sm form-select" onchange="chargerEtudiants()" required>
                                <option value="">Sélectionner l'année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                <option value="{{ $anneeAcademique->id_Annee_Acad }}">{{ $anneeAcademique->ANNEE_ACAD }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 3. Semestre d'étude (select natif avec style personnalisé, non détourné par TomSelect) -->
                    <div class="col-lg-4 col-md-12 col-sm-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold text-secondary small mb-1" for="semestreEtude">
                                <i class=" ri-bookmark-line me-1 text-primary"></i>Semestre d'étude <span class="text-danger">*</span>
                            </label>
                            <select name="semestreEtude" id="semestreEtude" class="form-control form-control-sm select-semestre-custom" required>
                                <option value="">Sélectionner d'abord une année d'étude...</option>
                            </select>
                            <div class="form-text text-muted small" id="requiredAnEtd" style="display:none"></div>
                        </div>
                    </div>

                    <!-- 4. Zone Sélection des étudiants & Mode d'impression -->
                    <div class="col-12 mt-4">
                        <div class="card border rounded-3 bg-light-subtle shadow-none mb-0">
                            <div class="card-header bg-white border-bottom py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <span class="fw-bold text-dark d-flex align-items-center" style="font-size: 0.95rem;">
                                        <i class=" ri-team-fill text-primary me-2"></i>Étudiants de la filière
                                    </span>

                                    <!-- Sélecteur de document (PV vs Relevés) -->
                                    <div class="btn-group btn-group-sm shadow-sm" role="group" aria-label="Type de document">
                                        <input type="radio" class="btn-check" name="type_document" id="doc_pv" value="pv" checked autocomplete="off">
                                        <label class="btn btn-outline-primary fw-bold px-3 py-1" for="doc_pv" id="lbl_doc_pv" title="Générer le Procès-Verbal semestriel">
                                            <i class=" ri-file-excel-line me-1"></i> Procès-Verbal (PV)
                                        </label>

                                        <input type="radio" class="btn-check" name="type_document" id="doc_releve" value="releve" autocomplete="off">
                                        <label class="btn btn-outline-success fw-bold px-3 py-1" for="doc_releve" id="lbl_doc_releve" title="Générer les relevés individuels de notes">
                                            <i class=" ri-file-user-line me-1"></i> Relevés de notes
                                        </label>
                                    </div>

                                    <span class="badge bg-secondary-subtle text-secondary border ms-1" id="students-count-badge" style="display: none;">
                                        Total : <span id="total-students-count">0</span>
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <span id="badge-selection" class="badge bg-light text-secondary border px-3 py-2 badge-mode shadow-sm">
                                        <i class=" ri-circle-fill text-secondary me-1" style="font-size: 0.5rem;"></i>En attente de sélection...
                                    </span>
                                </div>
                            </div>

                            <!-- Bandeau d'information explicatif dynamique -->
                            <div class="px-3 pt-2 bg-white" id="zone-explication" style="display: none;">
                                <div class="alert alert-primary py-2 px-3 mb-0 small d-flex align-items-center justify-content-between gap-2 shadow-none border" id="mode-info-pv">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class=" ri-information-fill text-primary fs-5 flex-shrink-0"></i>
                                        <div>
                                            <strong>Mode Procès-Verbal (PV) :</strong> Tous les étudiants <strong>cochés</strong> figureront sur le PV. <strong>Décochez</strong> un étudiant pour l'exclure du PV.
                                        </div>
                                    </div>
                                    <span class="badge bg-primary text-white flex-shrink-0 d-none d-md-inline">Tous cochés par défaut</span>
                                </div>

                                <div class="alert alert-success py-2 px-3 mb-0 small d-flex align-items-center justify-content-between gap-2 shadow-none border" id="mode-info-releve" style="display: none;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class=" ri-checkbox-circle-fill text-success fs-5 flex-shrink-0"></i>
                                        <div>
                                            <strong>Mode Relevés de notes :</strong> Cochez <strong>uniquement</strong> les étudiants dont vous souhaitez imprimer le relevé individuel de notes.
                                        </div>
                                    </div>
                                    <span class="badge bg-success text-white flex-shrink-0 d-none d-md-inline">Relevés individuels</span>
                                </div>
                            </div>

                            <!-- Barre d'outils / Recherche / Boutons d'action rapide -->
                            <div class="p-2 border-bottom bg-white d-flex flex-wrap align-items-center justify-content-between gap-2" id="zone-actions" style="display: none !important;">
                                <div class="input-group input-group-sm" style="max-width: 320px;" id="search-container">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class=" ri-search-line text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-sm border-start-0" id="student-search-input" placeholder="Filtrer par nom, prénom, matricule...">
                                    <button class="btn btn-outline-secondary" type="button" id="btn-clear-search" title="Effacer la recherche">
                                        <i class=" ri-close-line"></i>
                                    </button>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-select-all">
                                        <i class=" ri-check-double-line me-1"></i><span id="btn-select-all-label">Tout inclure</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-deselect-all">
                                        <i class=" ri-close-circle-line me-1"></i><span id="btn-deselect-all-label">Tout exclure</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Zone de liste des étudiants -->
                            <div class="card-body p-3">
                                <div id="students-placeholder" class="text-center py-4 text-muted">
                                    <i class=" ri-information-line fs-3 d-block mb-2 text-primary opacity-50"></i>
                                    <span>Veuillez sélectionner une <strong>Année d'étude</strong> et une <strong>Année académique</strong> pour charger la liste des étudiants.</span>
                                </div>

                                <div id="students-loading" class="text-center py-4 text-primary" style="display: none;">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    <span>Chargement des étudiants de la promotion...</span>
                                </div>

                                <div id="students-empty" class="alert alert-warning mb-0 py-2" style="display: none;">
                                    <i class=" ri-alert-line me-1"></i> Aucun étudiant trouvé pour cette sélection.
                                </div>

                                <div id="students-grid" class="student-grid-container row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-2" style="display: none;">
                                    <!-- Les cartes des étudiants sont générées ici en JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action / Impression -->
                    <div class="col-12 mt-4 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-primary px-3 shadow-sm" type="button" onclick="afficher()">
                                <i class=" ri-eye-line me-1"></i> Afficher
                            </button>
                            <small class="text-muted d-none d-sm-inline">Affiche le tableau récapitulatif à l'écran</small>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="submit" class="btn btn-sm btn-secondary px-3" value="Session normale" name="btn" id="btn-session-normale">
                                <i class=" ri-printer-line me-1"></i> <span class="btn-lbl">Session normale</span>
                            </button>
                            <button type="submit" class="btn btn-sm text-light px-3" style="background-color: #20c997;" value="Rattrapages" name="btn" id="btn-rattrapages">
                                <i class=" ri-printer-line me-1"></i> <span class="btn-lbl">Rattrapages</span>
                            </button>
                            <button type="submit" class="btn btn-sm btn-dark px-3" name="btn" value="Reprises" id="btn-reprises">
                                <i class=" ri-printer-line me-1"></i> <span class="btn-lbl">Reprises</span>
                            </button>
                            <button type="submit" class="btn btn-sm text-light px-3" style="background-color: #6f42c1;" name="btn" value="Reprises après rattrapage" id="btn-reprises-apres-rattr">
                                <i class=" ri-printer-line me-1"></i> <span class="btn-lbl">Reprises après rattrapage</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Zone d'affichage AJAX du tableau récapitulatif -->
    <div class="row" id="afficher"></div>
</div>
</section>
@endsection

@section('js')
<script>
    // ===============================================================
    // PARTIE 1 : SEMESTRES (JavaScript natif, indépendant de jQuery)
    // ===============================================================
    var semestresParAnnee = @json($tousLesSemestres);

    function remplirSemestres(liste, placeholderTexte) {
        var select = document.getElementById('semestreEtude');
        if (!select) return;

        // Détruire tout résidu de TomSelect sur semestreEtude si présent
        if (select.tomselect) {
            select.tomselect.destroy();
        }

        select.innerHTML = '';

        var opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = placeholderTexte;
        select.appendChild(opt0);

        if (Array.isArray(liste)) {
            liste.forEach(function (sem) {
                var opt = document.createElement('option');
                opt.value = sem.id || sem.id_S_Gr_Spe;
                opt.textContent = sem.nom || sem.Nom_Classe;
                select.appendChild(opt);
            });
        }

        select.disabled = false;
        select.removeAttribute('disabled');
    }

    function afficherHint(texte) {
        var hint = document.getElementById('requiredAnEtd');
        if (!hint) return;
        if (texte) {
            hint.textContent = texte;
            hint.style.display = '';
        } else {
            hint.textContent = '';
            hint.style.display = 'none';
        }
    }

    function chargerSemestres() {
        var anneeEtudeEl = document.getElementById('anneeEtude');
        var anneeEtudeVal = anneeEtudeEl ? anneeEtudeEl.value : '';

        if (!anneeEtudeVal && window.jQuery) {
            anneeEtudeVal = $('#anneeEtude').val() || '';
        }

        if (!anneeEtudeVal) {
            remplirSemestres([], "Sélectionner d'abord une année d'étude...");
            afficherHint('');
            chargerEtudiants();
            return;
        }

        var liste = (semestresParAnnee && semestresParAnnee[String(anneeEtudeVal)]) ? semestresParAnnee[String(anneeEtudeVal)] : [];

        if (liste.length > 0) {
            remplirSemestres(liste, 'Sélectionner le semestre...');
            afficherHint('');
            chargerEtudiants();
            return;
        }

        // Secours : requête AJAX si rien dans le cache serveur
        remplirSemestres([], 'Chargement des semestres...');
        fetch('/api/semestre-etude/' + encodeURIComponent(anneeEtudeVal), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.ok ? r.json() : []; })
        .then(function (data) {
            if (Array.isArray(data) && data.length > 0) {
                remplirSemestres(
                    data.map(function (el) { return { id: el.id_S_Gr_Spe, nom: el.Nom_Classe }; }),
                    'Sélectionner le semestre...'
                );
                afficherHint('');
            } else {
                remplirSemestres([], 'Aucun semestre configuré');
                afficherHint("Aucun semestre pour cette année d'étude.");
            }
            chargerEtudiants();
        })
        .catch(function () {
            remplirSemestres([], 'Aucun semestre configuré');
            afficherHint('Erreur lors du chargement des semestres.');
            chargerEtudiants();
        });
    }

    // Écouteurs natifs
    var aEtEl = document.getElementById('anneeEtude');
    if (aEtEl) aEtEl.addEventListener('change', chargerSemestres);

    var aAcEl = document.getElementById('anneeAcademique');
    if (aAcEl) aAcEl.addEventListener('change', function () {
        chargerEtudiants();
    });

    // Hooks jQuery & TomSelect (après initialisation dans layout.blade.php)
    if (window.jQuery) {
        window.jQuery(document).ready(function($) {
            var semEl = document.getElementById('semestreEtude');
            if (semEl && semEl.tomselect) {
                semEl.tomselect.destroy();
            }

            var anneeEl = document.getElementById('anneeEtude');
            if (anneeEl && anneeEl.tomselect) {
                anneeEl.tomselect.on('change', function() {
                    chargerSemestres();
                });
            }

            var anneeAcadEl = document.getElementById('anneeAcademique');
            if (anneeAcadEl && anneeAcadEl.tomselect) {
                anneeAcadEl.tomselect.on('change', function() {
                    chargerEtudiants();
                });
            }

            if ($('#anneeEtude').val()) {
                chargerSemestres();
            }
        });
    }

    // Si une année d'étude est déjà sélectionnée
    if (aEtEl && aEtEl.value) {
        chargerSemestres();
    }

    // ===============================================================
    // PARTIE 2 : ÉTUDIANTS (jQuery, initialisé quand jQuery est prêt)
    // ===============================================================
    var etudiantsData = [];

    function getModeDocument() {
        if (!window.jQuery) return 'pv';
        return window.jQuery('input[name="type_document"]:checked').val() || 'pv';
    }

    function changerModeDocument(mode) {
        if (!window.jQuery) return;
        var $ = window.jQuery;
        if (mode === 'pv') {
            $('#mode-info-pv').show();
            $('#mode-info-releve').hide();
            $('#btn-select-all-label').text('Tout inclure');
            $('#btn-deselect-all-label').text('Tout exclure');
            // En passant en mode PV, si aucun étudiant n'était sélectionné, on inclut tout par défaut
            if ($('.student-cb:checked').length === 0 && etudiantsData.length > 0) {
                $('.student-cb').prop('checked', true);
            }
        } else {
            $('#mode-info-pv').hide();
            $('#mode-info-releve').show();
            $('#btn-select-all-label').text('Tout cocher');
            $('#btn-deselect-all-label').text('Tout décocher');
        }
        recalculerSelection();
    }

    function chargerEtudiants() {
        if (!window.jQuery) { return; }
        var $ = window.jQuery;

        var anneeEtude = $('#anneeEtude').val();
        var anneeAcademique = $('#anneeAcademique').val();

        if (!anneeEtude || !anneeAcademique) {
            $('#students-placeholder').show();
            $('#students-loading').hide();
            $('#students-empty').hide();
            $('#students-grid').hide().empty();
            $('#zone-actions').attr('style', 'display: none !important;');
            $('#zone-explication').hide();
            $('#students-count-badge').hide();
            mettreAJourBadge(0, 0, getModeDocument());
            return;
        }

        $('#students-placeholder').hide();
        $('#students-empty').hide();
        $('#students-grid').hide().empty();
        $('#zone-actions').attr('style', 'display: none !important;');
        $('#zone-explication').hide();
        $('#students-loading').show();

        var url = '/api/ins/' + encodeURIComponent(anneeAcademique) + '/' + encodeURIComponent(anneeEtude);

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('#students-loading').hide();
                etudiantsData = Array.isArray(response) ? response : [];

                if (etudiantsData.length === 0) {
                    $('#students-empty').html('<i class=" ri-alert-line me-1"></i> Aucun étudiant trouvé pour cette sélection.').show();
                    mettreAJourBadge(0, 0, getModeDocument());
                    return;
                }

                var mode = getModeDocument();
                var defaultChecked = (mode === 'pv');

                var grid = $('#students-grid');
                grid.empty();

                etudiantsData.forEach(function (etu) {
                    var matricule = etu.MATRICULE || '';
                    var nom = (etu.nomEtu || '').trim();
                    var prenoms = (etu.prenomsEtu || '').trim();
                    var nomComplet = nom + ' ' + prenoms;
                    var searchText = (matricule + ' ' + nomComplet).toLowerCase();

                    var col = $('<div>', { 'class': 'col student-col', 'data-search': searchText });
                    var label = $('<label>', {
                        'class': 'student-card w-100 p-2 border rounded-3 d-flex align-items-center gap-2 mb-0' + (defaultChecked ? ' is-selected' : ''),
                        'for': 'stu_' + matricule
                    });
                    var checkbox = $('<input>', {
                        type: 'checkbox',
                        'class': 'form-check-input student-cb m-0',
                        name: 'selected_students[]',
                        value: matricule,
                        id: 'stu_' + matricule,
                        checked: defaultChecked
                    });
                    var infoDiv = $('<div>', {
                        'class': 'text-truncate flex-grow-1',
                        style: 'line-height: 1.25;'
                    }).append(
                        $('<div>', { 'class': 'fw-bold text-dark text-truncate', style: 'font-size: 0.88rem;', text: nomComplet }),
                        $('<div>', { 'class': 'd-flex align-items-center justify-content-between gap-1 flex-wrap' }).append(
                            $('<small>', { 'class': 'text-muted font-monospace', style: 'font-size: 0.78rem;' })
                                .html('<i class=" ri-scan-line me-1"></i>' + $('<span>').text(matricule).html()),
                            $('<span>', { 'class': 'status-indicator' })
                        )
                    );
                    label.append(checkbox, infoDiv);
                    col.append(label);
                    grid.append(col);
                });

                grid.show();
                $('#search-container').show();
                $('#zone-actions').removeAttr('style');
                $('#zone-explication').show();
                $('#total-students-count').text(etudiantsData.length);
                $('#students-count-badge').show();
                $('#student-search-input').val('');
                changerModeDocument(mode);
            },
            error: function () {
                $('#students-loading').hide();
                $('#students-empty').html('<i class=" ri-close-circle-line text-danger me-1"></i> Erreur lors du chargement des étudiants.').show();
                mettreAJourBadge(0, 0, getModeDocument());
            }
        });
    }

    function recalculerSelection() {
        if (!window.jQuery) { return; }
        var $ = window.jQuery;
        var total = etudiantsData.length;
        var checkedCount = $('.student-cb:checked').length;
        var mode = getModeDocument();

        // Mettre à jour l'apparence des cartes
        $('.student-cb').each(function () {
            var isChecked = this.checked;
            var card = $(this).closest('.student-card');
            var indicator = card.find('.status-indicator');

            if (mode === 'pv') {
                card.toggleClass('is-selected', isChecked);
                card.toggleClass('is-excluded-pv', !isChecked);
                if (isChecked) {
                    indicator.html('<span class="badge-included"><i class=" ri-check-line me-1"></i>Inclus au PV</span>');
                } else {
                    indicator.html('<span class="badge-excluded"><i class=" ri-close-circle-line me-1"></i>Exclu du PV</span>');
                }
            } else {
                card.toggleClass('is-selected', isChecked);
                card.removeClass('is-excluded-pv');
                if (isChecked) {
                    indicator.html('<span class="badge-included"><i class=" ri-file-check-line me-1"></i>Relevé coché</span>');
                } else {
                    indicator.empty();
                }
            }
        });

        mettreAJourBadge(checkedCount, total, mode);
    }

    function mettreAJourBadge(checkedCount, total, mode) {
        if (!window.jQuery) { return; }
        var $ = window.jQuery;

        var badge = $('#badge-selection');
        var btnNormale = $('#btn-session-normale .btn-lbl');
        var btnRattr = $('#btn-rattrapages .btn-lbl');
        var btnRepr = $('#btn-reprises .btn-lbl');
        var btnReprApresRattr = $('#btn-reprises-apres-rattr .btn-lbl');

        if (total === 0) {
            badge.removeClass().addClass('badge bg-light text-secondary border px-3 py-2 badge-mode')
                 .html('<i class=" ri-information-line me-1"></i>En attente de sélection...');
            btnNormale.text('Session normale');
            btnRattr.text('Rattrapages');
            btnRepr.text('Reprises');
            btnReprApresRattr.text('Reprises après rattrapage');
            return;
        }

        if (mode === 'pv') {
            var exclus = total - checkedCount;
            if (checkedCount === total) {
                badge.removeClass().addClass('badge bg-success text-white border px-3 py-2 badge-mode shadow-sm')
                     .html('<i class=" ri-check-double-line me-1 fs-6"></i>PV Complet : tous les ' + total + ' étudiants inclus');
            } else if (checkedCount > 0) {
                badge.removeClass().addClass('badge bg-warning text-dark border px-3 py-2 badge-mode shadow-sm')
                     .html('<i class=" ri-filter-fill me-1"></i>PV Filtré : ' + checkedCount + '/' + total + ' étudiants (' + exclus + ' exclu' + (exclus > 1 ? 's' : '') + ')');
            } else {
                badge.removeClass().addClass('badge bg-danger text-white border px-3 py-2 badge-mode shadow-sm')
                     .html('<i class=" ri-alert-fill me-1"></i>Attention : 0 étudiant dans le PV');
            }

            btnNormale.html('<i class=" ri-file-excel-line me-1"></i>PV - Session normale');
            btnRattr.html('<i class=" ri-file-excel-line me-1"></i>PV - Rattrapages');
            btnRepr.html('<i class=" ri-file-excel-line me-1"></i>PV - Reprises');
            btnReprApresRattr.html('<i class=" ri-file-excel-line me-1"></i>PV - Reprises après rattrapage');
        } else {
            // Mode Relevé
            if (checkedCount === 0) {
                badge.removeClass().addClass('badge bg-light text-secondary border px-3 py-2 badge-mode')
                     .html('<i class=" ri-checkbox-blank-circle-line me-1"></i>0 relevé sélectionné (cochez les étudiants)');
            } else {
                badge.removeClass().addClass('badge bg-primary text-white border px-3 py-2 badge-mode shadow-sm')
                     .html('<i class=" ri-checkbox-circle-fill me-1"></i>' + checkedCount + ' relevé' + (checkedCount > 1 ? 's' : '') + ' sélectionné' + (checkedCount > 1 ? 's' : ''));
            }

            var suffix = checkedCount <= 1 ? ' (Relevé)' : ' (' + checkedCount + ' Relevés)';
            btnNormale.html('<i class=" ri-printer-line me-1"></i>Session normale' + suffix);
            btnRattr.html('<i class=" ri-printer-line me-1"></i>Rattrapages' + suffix);
            btnRepr.html('<i class=" ri-printer-line me-1"></i>Reprises' + suffix);
            btnReprApresRattr.html('<i class=" ri-printer-line me-1"></i>Reprises' + suffix);
        }
    }

    // Affichage AJAX du tableau récapitulatif (appelé par le bouton Afficher)
    function afficher() {
        var $ = window.jQuery;
        var semVal = document.getElementById('semestreEtude').value;
        var anEtVal = document.getElementById('anneeEtude').value;
        var anAcVal = document.getElementById('anneeAcademique').value;
        if (!anEtVal || !anAcVal || !semVal) {
            alert("Veuillez renseigner tous les champs obligatoires !");
            return;
        }
        $('#afficher').addClass('card p-4 shadow-sm')
            .html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span> Chargement en cours...</div>');
        $.ajax({
            url: '{{ route("affichage-recap-semestre") }}',
            type: 'POST',
            data: $('#recapSemestre').serialize(),
            success: function (response) { $('#afficher').html(response); },
            error: function () { $('#afficher').html('<div class="alert alert-danger">Erreur lors du chargement.</div>'); }
        });
    }

    // Écouteurs jQuery : attendent que jQuery soit réellement chargé
    function initJqueryHandlers() {
        var $ = window.jQuery;

        // Écouteur sur le changement de mode document (PV vs Relevés)
        $(document).on('change', 'input[name="type_document"]', function () {
            changerModeDocument(this.value);
        });

        // Clic sur une case à cocher étudiant
        $(document).on('change', '.student-cb', function () {
            recalculerSelection();
        });

        // Tout cocher / Tout inclure
        $(document).on('click', '#btn-select-all', function () {
            $('.student-col:visible .student-cb').prop('checked', true);
            recalculerSelection();
        });

        // Tout décocher / Tout exclure
        $(document).on('click', '#btn-deselect-all', function () {
            $('.student-col:visible .student-cb').prop('checked', false);
            recalculerSelection();
        });

        // Recherche d'étudiant
        $(document).on('input', '#student-search-input', function () {
            var query = $(this).val().toLowerCase().trim();
            $('.student-col').each(function () {
                $(this).toggle(!query || ($(this).attr('data-search') || '').indexOf(query) !== -1);
            });
        });

        $(document).on('click', '#btn-clear-search', function () {
            $('#student-search-input').val('');
            $('.student-col').show();
        });

        // Validation avant soumission du formulaire
        $('#recapSemestre').on('submit', function (e) {
            var mode = getModeDocument();
            var checkedCount = $('.student-cb:checked').length;
            if (etudiantsData.length > 0 && checkedCount === 0) {
                if (mode === 'pv') {
                    if (!confirm("Attention : aucun étudiant n'est coché pour le Procès-Verbal.\nSouhaitez-vous générer le PV pour TOUS les étudiants inscrits ?")) {
                        e.preventDefault();
                        return false;
                    }
                } else {
                    alert("Veuillez sélectionner au moins un étudiant pour imprimer ses relevés de notes.");
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Si les deux années étaient déjà choisies avant que jQuery soit prêt
        chargerEtudiants();
    }

    (function attendreJquery(essais) {
        if (window.jQuery) {
            initJqueryHandlers();
        } else if (essais < 100) {
            setTimeout(function () { attendreJquery(essais + 1); }, 50);
        } else {
            console.error('jQuery introuvable : la liste des étudiants ne peut pas fonctionner.');
        }
    })(0);
</script>
@endsection