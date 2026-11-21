@extends('layout')
@section('title')
Récapitulatif Annuel & Relevé de notes
@endsection

@section('css')
<style>
    .student-card {
        background: #ffffff;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        border-color: #dee2e6 !important;
        user-select: none;
    }
    .student-card:hover {
        background: #f8f9fa;
        border-color: #0d6efd !important;
        transform: translateY(-1px);
    }
    .student-card.is-selected {
        background: #e7f1ff;
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 1px #0d6efd;
    }
    .student-cb {
        cursor: pointer;
        width: 1.15rem;
        height: 1.15rem;
    }
    #students-grid::-webkit-scrollbar {
        width: 6px;
    }
    #students-grid::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    #students-grid::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 4px;
    }
    #students-grid::-webkit-scrollbar-thumb:hover {
        background: #adb5bd;
    }
</style>
@endsection

@section('content')
<section class="section">
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class=" ri-checkbox-circle-line me-1"></i> {{ session('succes') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class=" ri-alert-line me-1"></i> {{ session('echec') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="p-4 card shadow-sm">
            <form action="{{ route('impression.annuel') }}" method="POST" id="recapAnnuel" target="_blank">
                @csrf
                <div class="row g-3">
                    {{-- Champ 1 : Année d'étude --}}
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="anneeEtude">
                                <i class=" ri-bookmark-line me-1 text-primary"></i>Année d'étude <span class="text-danger">*</span>
                            </label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-control-sm form-select" required onchange="chargerEtudiants()">
                                <option value="">Sélectionner une année d'étude...</option>
                                @foreach (\App\Models\AnneeEtude::all() as $anneeEtude)
                                <option value="{{ $anneeEtude->Code_Annee_Etude }}">{{ $anneeEtude->Libelle_AnEtude }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Champ 2 : Année académique --}}
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold" for="anneeAcademique">
                                <i class=" ri-calendar-line me-1 text-primary"></i>Année académique <span class="text-danger">*</span>
                            </label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-control-sm form-select" required onchange="chargerEtudiants()">
                                <option value="">Sélectionner l'année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                <option value="{{ $anneeAcademique->id_Annee_Acad }}">{{ $anneeAcademique->ANNEE_ACAD }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Champ 3 : Type d'étudiant --}}
                    <div class="col-lg-4 col-md-12 col-12">
                        <div class="form-group">
                            <label for="typeEtudiant" class="form-label fw-semibold">
                                <i class=" ri-price-tag-3-line me-1 text-primary"></i>Type d'étudiant
                            </label>
                            <select name="typeEtudiant" id="typeEtudiant" class="form-control form-control-sm form-select" onchange="chargerEtudiants()">
                                <option value="">Tous les statuts</option>
                                <option value="all">Tous les statuts</option>
                                @foreach (\App\Models\Statut::all() as $statut)
                                    <option value="{{ $statut->codeStatut }}">{{ $statut->LibStatut }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Champ 4 : Zone de sélection des étudiants (Responsive et interactive) --}}
                    <div class="col-12 mt-3">
                        <div class="card border rounded-3 bg-light-subtle shadow-none mb-0" id="zone-etudiants-card">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class=" ri-team-fill text-primary fs-5"></i>
                                    <span class="fw-bold text-dark">Étudiants de la filière</span>
                                    <span class="badge rounded-pill bg-light text-secondary border px-2 py-1" id="badge-selection" style="font-size: 0.78rem;">
                                        <i class=" ri-circle-fill text-secondary me-1" style="font-size: 0.5rem;"></i>Tous désélectionnés (Mode PV global)
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2" id="zone-actions" style="display: none !important;">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" id="btn-select-all" title="Cocher tous les étudiants affichés">
                                        <i class=" ri-check-double-line me-1"></i>Tout cocher
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" id="btn-deselect-all" title="Décocher tous les étudiants">
                                        <i class=" ri-close-circle-line me-1"></i>Tout décocher
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                {{-- Barre de recherche rapide --}}
                                <div id="search-container" class="mb-3" style="display: none;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class=" ri-search-line text-muted"></i>
                                        </span>
                                        <input type="text" id="student-search-input" class="form-control border-start-0" placeholder="Filtrer par nom, prénom ou matricule...">
                                        <button class="btn btn-outline-secondary" type="button" id="btn-clear-search" title="Effacer la recherche">
                                            <i class=" ri-close-line"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- État initial avant sélection --}}
                                <div id="students-placeholder" class="text-center py-4 text-muted bg-white border border-dashed rounded-3">
                                    <i class=" ri-graduation-cap-line fs-1 text-primary opacity-50 d-block mb-1"></i>
                                    <p class="mb-1 fw-semibold text-dark">Sélection des étudiants</p>
                                    <small>Choisissez d'abord une <b>Année d'étude</b> et une <b>Année académique</b> ci-dessus pour charger la liste des étudiants.</small>
                                </div>

                                {{-- État chargement (spinner) --}}
                                <div id="students-loading" class="text-center py-4 bg-white border rounded-3" style="display: none;">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span class="text-muted fw-semibold">Chargement des étudiants en cours...</span>
                                </div>

                                {{-- État aucun étudiant trouvé --}}
                                <div id="students-empty" class="text-center py-4 bg-white border rounded-3 text-warning" style="display: none;">
                                    <i class=" ri-error-warning-line fs-3 d-block mb-1"></i>
                                    <span class="fw-semibold">Aucun étudiant inscrit trouvé pour cette filière et cette année académique.</span>
                                </div>

                                {{-- Grille responsive des étudiants --}}
                                <div id="students-grid" class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-2" style="max-height: 250px; overflow-y: auto; display: none;">
                                    {{-- Cartes d'étudiants insérées dynamiquement via JS --}}
                                </div>

                                {{-- Pied de la zone avec explication contextuelle --}}
                                <div class="mt-3 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center text-muted" style="font-size: 0.82rem;">
                                    <div class="d-flex align-items-center gap-1">
                                        <i class=" ri-information-fill text-primary"></i>
                                        <span id="instruction-text">
                                            Laissez <b>tous désélectionnés</b> pour imprimer le <b>Procès-Verbal annuel global</b>. Cochez un ou plusieurs étudiants pour générer leur(s) <b>relevé(s) annuel(s) de notes</b>.
                                        </span>
                                    </div>
                                    <div class="fw-semibold text-secondary" id="students-count-badge" style="display: none;">
                                        <span id="total-students-count">0</span> étudiant(s) inscrit(s)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Boutons d'actions --}}
                <div class="text-end mt-4 d-flex flex-wrap justify-content-end align-items-center gap-2">
                    <button class="btn btn-sm btn-primary px-3" type="button" onclick="afficher()">
                        <i class=" ri-eye-line me-1"></i>Afficher
                    </button>
                    <button type="submit" class="btn btn-sm btn-secondary px-3" value="Session normale" name="btn" id="btn-session-normale">
                        <i class=" ri-printer-line me-1"></i><span class="btn-lbl">Session normale</span>
                    </button>
                    <button type="submit" class="btn btn-sm text-light px-3" style="background-color: #20c997;" value="Rattrapages" name="btn" id="btn-rattrapages">
                        <i class=" ri-printer-line me-1"></i><span class="btn-lbl">Rattrapages</span>
                    </button>
                    <button type="submit" class="btn btn-sm btn-dark px-3" name="btn" value="Reprises" id="btn-reprises">
                        <i class=" ri-printer-line me-1"></i><span class="btn-lbl">Reprises</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-success px-3" onclick="$('#recapAnnuel').attr('action','/export-resultats-annuel');$('#recapAnnuel').submit();$('#recapAnnuel').attr('action','/impression-annuel')">
                        <i class=" ri-file-excel-2-line me-1"></i>Exporter en format Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Zone d'affichage AJAX du tableau --}}
    <div class="row mt-3" id="afficher"></div>
</div>
</section>
@endsection

@section('js')
<script>
    let etudiantsData = [];

    function chargerEtudiants() {
        const anEtude = $('#anneeEtude').val();
        const anAcad = $('#anneeAcademique').val();
        const typeEtud = $('#typeEtudiant').val();

        // Réinitialiser la zone si l'un des deux critères obligatoires est vide
        if (!anEtude || !anAcad) {
            $('#students-placeholder').show();
            $('#students-loading').hide();
            $('#students-empty').hide();
            $('#students-grid').hide().empty();
            $('#search-container').hide();
            $('#zone-actions').attr('style', 'display: none !important');
            $('#students-count-badge').hide();
            mettreAJourBadge(0);
            return;
        }

        // Afficher l'indicateur de chargement
        $('#students-placeholder').hide();
        $('#students-loading').show();
        $('#students-empty').hide();
        $('#students-grid').hide().empty();
        $('#search-container').hide();
        $('#zone-actions').attr('style', 'display: none !important');
        $('#students-count-badge').hide();

        let url = '/api/ins/' + encodeURIComponent(anAcad) + '/' + encodeURIComponent(anEtude);
        if (typeEtud && typeEtud !== 'all') {
            url += '?statut=' + encodeURIComponent(typeEtud);
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#students-loading').hide();
                etudiantsData = Array.isArray(response) ? response : [];

                if (etudiantsData.length === 0) {
                    $('#students-empty').show();
                    mettreAJourBadge(0);
                    return;
                }

                // Construire la liste des étudiants (tous désélectionnés par défaut)
                const grid = $('#students-grid');
                grid.empty();

                etudiantsData.forEach(function(etu) {
                    const matricule = etu.MATRICULE || '';
                    const nom = (etu.nomEtu || '').trim();
                    const prenoms = (etu.prenomsEtu || '').trim();
                    const nomComplet = nom + ' ' + prenoms;
                    const searchText = (matricule + ' ' + nomComplet).toLowerCase();

                    const col = $('<div>', {
                        class: 'col student-col',
                        'data-search': searchText
                    });

                    const label = $('<label>', {
                        class: 'student-card w-100 p-2 border rounded-3 d-flex align-items-center gap-2 mb-0',
                        for: 'stu_' + matricule
                    });

                    const checkbox = $('<input>', {
                        type: 'checkbox',
                        class: 'form-check-input student-cb m-0',
                        name: 'selected_students[]',
                        value: matricule,
                        id: 'stu_' + matricule
                    });

                    const infoDiv = $('<div>', {
                        class: 'text-truncate flex-grow-1',
                        style: 'line-height: 1.25;'
                    }).append(
                        $('<div>', {
                            class: 'fw-bold text-dark text-truncate',
                            style: 'font-size: 0.88rem;',
                            text: nomComplet
                        }),
                        $('<small>', {
                            class: 'text-muted font-monospace',
                            style: 'font-size: 0.78rem;'
                        }).html('<i class=" ri-scan-line me-1"></i>' + matricule)
                    );

                    label.append(checkbox, infoDiv);
                    col.append(label);
                    grid.append(col);
                });

                grid.show();
                $('#search-container').show();
                $('#zone-actions').removeAttr('style');
                $('#total-students-count').text(etudiantsData.length);
                $('#students-count-badge').show();
                $('#student-search-input').val('');

                mettreAJourBadge(0);
            },
            error: function() {
                $('#students-loading').hide();
                $('#students-empty').html('<i class=" ri-close-circle-line text-danger me-1"></i> Erreur lors du chargement des étudiants.').show();
                mettreAJourBadge(0);
            }
        });
    }

    // Gestion du clic et de l'état sélectionné
    $(document).on('change', '.student-cb', function() {
        const card = $(this).closest('.student-card');
        if (this.checked) {
            card.addClass('is-selected');
        } else {
            card.removeClass('is-selected');
        }
        recalculerSelection();
    });

    function recalculerSelection() {
        const count = $('.student-cb:checked').length;
        mettreAJourBadge(count);
    }

    function mettreAJourBadge(count) {
        const badge = $('#badge-selection');
        const btnNormale = $('#btn-session-normale .btn-lbl');
        const btnRattr = $('#btn-rattrapages .btn-lbl');
        const btnRepr = $('#btn-reprises .btn-lbl');

        if (count === 0) {
            badge.removeClass('bg-primary text-white bg-success-subtle text-success')
                 .addClass('bg-light text-secondary')
                 .html('<i class=" ri-circle-fill text-secondary me-1" style="font-size: 0.5rem;"></i>Tous désélectionnés (Mode PV global)');
            btnNormale.text('Session normale');
            btnRattr.text('Rattrapages');
            btnRepr.text('Reprises');
        } else if (count === 1) {
            badge.removeClass('bg-light text-secondary')
                 .addClass('bg-primary text-white')
                 .html('<i class=" ri-checkbox-circle-fill me-1"></i>1 étudiant sélectionné (Mode Relevé)');
            btnNormale.text('Session normale (Relevé)');
            btnRattr.text('Rattrapages (Relevé)');
            btnRepr.text('Reprises (Relevé)');
        } else {
            badge.removeClass('bg-light text-secondary')
                 .addClass('bg-primary text-white')
                 .html('<i class=" ri-checkbox-circle-fill me-1"></i>' + count + ' étudiants sélectionnés (Mode Relevés)');
            btnNormale.text('Session normale (Relevés)');
            btnRattr.text('Rattrapages (Relevés)');
            btnRepr.text('Reprises (Relevés)');
        }
    }

    // Sélectionner tout (uniquement ceux visibles par la recherche)
    $(document).on('click', '#btn-select-all', function() {
        $('.student-col:visible .student-cb').prop('checked', true).each(function() {
            $(this).closest('.student-card').addClass('is-selected');
        });
        recalculerSelection();
    });

    // Désélectionner tout
    $(document).on('click', '#btn-deselect-all', function() {
        $('.student-cb').prop('checked', false).each(function() {
            $(this).closest('.student-card').removeClass('is-selected');
        });
        recalculerSelection();
    });

    // Recherche / filtrage en direct
    $(document).on('input', '#student-search-input', function() {
        const query = $(this).val().toLowerCase().trim();
        if (!query) {
            $('.student-col').show();
            return;
        }
        $('.student-col').each(function() {
            const searchData = $(this).attr('data-search') || '';
            if (searchData.indexOf(query) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Effacer la recherche
    $(document).on('click', '#btn-clear-search', function() {
        $('#student-search-input').val('');
        $('.student-col').show();
    });

    // Affichage AJAX du tableau récapitulatif
    function afficher() {
        $('#afficher').addClass('card p-4 shadow-sm');
        $('#afficher').html('<div class="text-center py-3"><button class="btn btn-link" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button></div>');
        $.ajax({
            url: '{{ route("affichage-recap-annuel") }}',
            type: 'POST',
            data: $('#recapAnnuel').serialize(),
            success: function(response) {
                $('#afficher').html(response);
            },
            error: function(xhr) {
                $('#afficher').html('<div class="alert alert-danger"><i class=" ri-alert-line me-1"></i> Erreur lors du chargement des données.</div>');
            }
        });
    }
</script>
@endsection
