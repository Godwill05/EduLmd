@extends('layout')
@section('title')
Paiements
@endsection
@section('content')
<section class="section">
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success alert-dismissible fade show">
            {{session('succes')}}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger alert-dismissible fade show">
            {{session('echec')}}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <!-- Onglets de navigation -->
    <ul class="nav nav-tabs nav-tabs-bordered mb-4" id="paiementsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="tab-liste-btn" data-bs-toggle="tab" data-bs-target="#tab-liste" type="button" role="tab" aria-controls="tab-liste" aria-selected="true">
                <i class=" ri-list-check me-1"></i> Liste des paiements
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-primary fw-bold" id="tab-situation-btn" data-bs-toggle="tab" data-bs-target="#tab-situation" type="button" role="tab" aria-controls="tab-situation" aria-selected="false">
                <i class=" ri-calendar-check-line me-1"></i> Situation des paiements à une échéance (En règle / En retard)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-success fw-bold" id="tab-journal-btn" data-bs-toggle="tab" data-bs-target="#tab-journal" type="button" role="tab" aria-controls="tab-journal" aria-selected="false">
                <i class=" ri-book-read-line me-1"></i> Journal d'encaissement
            </button>
        </li>
    </ul>

    <div class="tab-content" id="paiementsTabsContent">
        <!-- ONGLET 1 : HISTORIQUE ET LISTE DES PAIEMENTS -->
        <div class="tab-pane fade show active" id="tab-liste" role="tabpanel" aria-labelledby="tab-liste-btn">
            <div class="row">
                <div class="card p-4 shadow-sm border-0 border-top border-secondary border-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-secondary-subtle text-secondary p-2 rounded me-3">
                            <i class=" ri-receipt-line fs-3"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold">Afficher la liste des paiements</h4>
                            <p class="text-muted small mb-0">Consultez l'historique des paiements ou imprimez le récapitulatif par filière et par étudiant.</p>
                        </div>
                    </div>
                    <hr class="my-2">
                    <form action="{{ route('impression.paiement') }}" method="POST" id="pmtForm" target="_blank">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <label class="form-label fw-bold small" for="anneeAcademique">
                                    <i class=" ri-calendar-event-line me-1 text-primary"></i>Année académique <span class="text-danger">*</span>
                                </label>
                                <select name="anneeAcademique" id="anneeAcademique" class="form-select form-select-sm no-tomselect" required>
                                    <option value="">Sélectionner l'année...</option>
                                    @foreach (\App\Models\AnneeAcademique::orderBy('id_Annee_Acad', 'desc')->get() as $anneeAcademique)
                                    <option value="{{$anneeAcademique->id_Annee_Acad}}" {{ $loop->first ? 'selected' : '' }}>{{$anneeAcademique->ANNEE_ACAD}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <label class="form-label fw-bold small" for="anneeEtudePmt">
                                    <i class=" ri-graduation-cap-line me-1 text-primary"></i>Filière / Classe
                                </label>
                                <select name="anneeEtude" id="anneeEtudePmt" class="form-select form-select-sm no-tomselect">
                                    <option value="">Toutes les filières</option>
                                    @foreach (\App\Models\AnneeEtude::orderBy('Libelle_AnEtude')->get() as $ae)
                                    <option value="{{$ae->Code_Annee_Etude}}">{{$ae->Libelle_AnEtude}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6 col-sm-6">
                                <label for="dateDebut" class="form-label fw-bold small">
                                    <i class=" ri-calendar-line me-1 text-secondary"></i>Du (facultatif)
                                </label>
                                <input type="date" name="dateDebut" id="dateDebut" class="form-control form-control-sm">
                            </div>

                            <div class="col-lg-2 col-md-6 col-sm-6">
                                <label for="dateFin" class="form-label fw-bold small">
                                    <i class=" ri-calendar-line me-1 text-secondary"></i>Au (facultatif)
                                </label>
                                <input type="date" name="dateFin" id="dateFin" class="form-control form-control-sm">
                            </div>

                            <div class="col-lg-2 col-md-12 col-sm-12">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm flex-fill" id="afficher" type="button">
                                        <i class=" ri-eye-line me-1"></i>Afficher
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                        <i class=" ri-printer-line me-1"></i>Imprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 text-muted small">
                            <i class=" ri-information-line me-1"></i>Laissez les dates vides pour consulter ou imprimer l'ensemble des paiements de l'année.
                        </div>
                        <input type="hidden" name="btn" value="Imprimer">
                    </form>
                </div>
            </div>
            <div class="displayPaiement mt-3">

            </div>
        </div>

        <!-- ONGLET 2 : NOUVEL ESPACE DÉDIÉ AUX ÉCHÉANCES ET RETARDS -->
        <div class="tab-pane fade" id="tab-situation" role="tabpanel" aria-labelledby="tab-situation-btn">
            <div class="row">
                <div class="card p-4 shadow-sm border-0 border-top border-primary border-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary-subtle text-primary p-2 rounded me-3">
                            <i class=" ri-calendar-check-line fs-3"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold">Situation des paiements à une échéance</h4>
                            <p class="text-muted small mb-0">Sélectionnez une date d'échéance pour déterminer qui est en règle ou en retard de paiement.</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <form action="{{ route('impression.situation-echeance') }}" method="POST" id="situationForm" target="_blank">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label fw-bold" for="anneeAcademiqueSituation">Année académique <span class="text-danger">*</span></label>
                                    <select name="anneeAcademique" id="anneeAcademiqueSituation" class="form-control form-control-sm form-select" required>
                                        <option value="">Sélectionner l'année...</option>
                                        @foreach (\App\Models\AnneeAcademique::orderBy('id_Annee_Acad', 'desc')->get() as $annee)
                                        <option value="{{$annee->id_Annee_Acad}}">{{$annee->ANNEE_ACAD}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label fw-bold" for="dateEcheance">Date d'évaluation (Échéance) <span class="text-danger">*</span></label>
                                    <input type="date" name="dateEcheance" id="dateEcheance" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                                    <small class="text-muted">Évalue les tranches et versements jusqu'à cette date.</small>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label fw-bold" for="anneeEtudeSituation">Filière / Classe</label>
                                    <select name="anneeEtude" id="anneeEtudeSituation" class="form-control form-control-sm form-select">
                                        <option value="">Toutes les filières</option>
                                        @foreach (\App\Models\AnneeEtude::orderBy('Libelle_AnEtude')->get() as $ae)
                                        <option value="{{$ae->Code_Annee_Etude}}">{{$ae->Libelle_AnEtude}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label fw-bold" for="statutFiltre">Filtrer par statut</label>
                                    <select name="statutFiltre" id="statutFiltre" class="form-control form-control-sm form-select">
                                        <option value="tous">Tous les étudiants</option>
                                        <option value="en_retard">En retard uniquement (Impayés)</option>
                                        <option value="en_regle">En règle uniquement (À jour / Soldés)</option>
                                        <option value="sans_echeancier">Sans échéancier défini</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="btn" value="Imprimer">
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-outline-primary" id="btnAfficherSituation">
                                <i class=" ri-eye-line me-1"></i> Afficher la situation
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnImprimerSituation">
                                <i class=" ri-printer-line me-1"></i> Imprimer le document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="displaySituationEcheance mt-3">

            </div>
        </div>

        <!-- ONGLET 3 : JOURNAL D'ENCAISSEMENT -->
        <div class="tab-pane fade" id="tab-journal" role="tabpanel" aria-labelledby="tab-journal-btn">
            <div class="row">
                <div class="card p-4 shadow-sm border-0 border-top border-success border-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-subtle text-success p-2 rounded me-3">
                            <i class=" ri-book-read-line fs-3"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold">Journal d'encaissement</h4>
                            <p class="text-muted small mb-0">Visualisez et imprimez les paiements encaissés sur une période donnée, groupés par date.</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <form id="journalForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-bold" for="journalAnnee">Année académique <span class="text-danger">*</span></label>
                                <select name="anneeAcademique" id="journalAnnee" class="form-control form-control-sm form-select" required>
                                    <option value="">Sélectionner l'année...</option>
                                    @foreach (\App\Models\AnneeAcademique::orderBy('id_Annee_Acad','desc')->get() as $annee)
                                    <option value="{{ $annee->id_Annee_Acad }}" {{ $annee->actif ?? false ? 'selected' : '' }}>{{ $annee->ANNEE_ACAD }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-bold" for="journalDateDebut">Du <span class="text-danger">*</span></label>
                                <input type="date" name="dateDebut" id="journalDateDebut" class="form-control form-control-sm"
                                    value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-bold" for="journalDateFin">Au <span class="text-danger">*</span></label>
                                <input type="date" name="dateFin" id="journalDateFin" class="form-control form-control-sm"
                                    value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-bold" for="journalFiliere">Filière (facultatif)</label>
                                <select name="anneeEtude" id="journalFiliere" class="form-control form-control-sm form-select">
                                    <option value="">Toutes les filières</option>
                                    @foreach (\App\Models\AnneeEtude::orderBy('Libelle_AnEtude')->get() as $ae)
                                    <option value="{{ $ae->Code_Annee_Etude }}">{{ $ae->Libelle_AnEtude }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-bold" for="journalEtudiant">Étudiant (facultatif)</label>
                                <input type="text" name="journalEtudiant" id="journalEtudiant" class="form-control form-control-sm"
                                    placeholder="Nom ou matricule">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-bold" for="journalModePaiement">Mode de paiement</label>
                                <select name="journalModePaiement" id="journalModePaiement" class="form-control form-control-sm form-select">
                                    <option value="">Tous les modes</option>
                                    <option value="Espèces">Espèces</option>
                                    <option value="Chèque">Chèque</option>
                                    <option value="Virement">Virement</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-success btn-sm flex-fill" id="btnAfficherJournal">
                                        <i class=" ri-eye-line me-1"></i>Afficher
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm flex-fill" id="btnImprimerJournal">
                                        <i class=" ri-printer-line me-1"></i>Imprimer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Raccourcis de période -->
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold small text-muted mb-2">Raccourcis de période :</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="today"><i class="ri-calendar-today-line me-1"></i>Aujourd'hui</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="this_week"><i class="ri-calendar-week-line me-1"></i>Cette semaine</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="last_week"><i class="ri-calendar-line me-1"></i>La semaine dernière</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="this_month"><i class="ri-calendar-month-line me-1"></i>Ce mois</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="last_month"><i class="ri-calendar-line me-1"></i>Le mois dernier</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="this_year"><i class="ri-calendar-full-line me-1"></i>Cette année</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="last_year"><i class="ri-calendar-line me-1"></i>L'an dernier</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="minus_3_days"><i class="ri-history-line me-1"></i>3 derniers jours</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm raccourci-periode" data-code="minus_7_days"><i class="ri-history-line me-1"></i>7 derniers jours</button>
                            </div>
                            <small class="text-muted mt-2 d-block">Cliquez sur un raccourci pour remplir automatiquement les dates « Du » et « Au ».</small>
                        </div>

                    </form>
                </div>
            </div>
            <div class="displayJournalEncaissement mt-3"></div>
        </div>

    </div>
</div>
</section>
@endsection
@section('js')
<script>
    // Onglet 1 : Afficher liste des paiements existante
    $('#afficher').on('click',function(e){
        e.preventDefault();
        $('.displayPaiement').addClass('card p-2');
        $('.displayPaiement').html('<button class="btn btn-link text-purple-500" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:'/dP',
            method:'POST',
            data:$('#pmtForm').serialize(),
            success:function(response){
                $('.displayPaiement').html(response)
                const dataTable = new simpleDatatables.DataTable("#tableD");
                e.stopPropagation()
            }
        })
    });

    // Onglet 2 : Afficher la situation des paiements à une échéance
    $('#btnAfficherSituation').on('click', function(e) {
        e.preventDefault();
        const annee = $('#anneeAcademiqueSituation').val();
        if (!annee) {
            alert('Veuillez sélectionner une année académique.');
            $('#anneeAcademiqueSituation').focus();
            return;
        }

        $('.displaySituationEcheance').addClass('card p-3 shadow-sm border-0');
        $('.displaySituationEcheance').html('<div class="text-center py-4"><button class="btn btn-link text-primary" type="button" disabled><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Calcul de la situation des paiements en cours...</button></div>');

        $.ajax({
            url: '{{ route("situation-echeance.display") }}',
            method: 'POST',
            data: $('#situationForm').serialize(),
            success: function(response) {
                $('.displaySituationEcheance').html(response);
            },
            error: function(xhr) {
                let err = 'Erreur lors du calcul de la situation des paiements.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    err = xhr.responseJSON.message;
                }
                $('.displaySituationEcheance').html('<div class="alert alert-danger"><i class=" ri-alert-line me-2"></i>' + err + '</div>');
            }
        });
    });

    /* ========================================================================
       Raccourcis de période — Journal d'encaissement
       ------------------------------------------------------------------------
       Remplit automatiquement les champs « Du » et « Au » selon la période
       choisie (semaine, mois, année, derniers jours…).
       ======================================================================== */
    function appliquerRaccourci(code) {
        const today = new Date();
        const yy = today.getFullYear();
        const mm = today.getMonth();
        const dd = today.getDate();
        const fmt = (d) => d.toISOString().split('T')[0]; // YYYY-MM-DD

        let debut, fin;

        switch (code) {
            case 'today':
                debut = fin = today;
                break;
            case 'this_week':   // lundi → aujourd'hui
                debut = new Date(today);
                debut.setDate(dd - ((today.getDay() + 6) % 7));
                fin = today;
                break;
            case 'last_week':   // lundi → dimanche de la semaine passée
                debut = new Date(today);
                debut.setDate(dd - ((today.getDay() + 6) % 7) - 7);
                fin = new Date(debut);
                fin.setDate(debut.getDate() + 6);
                break;
            case 'this_month':  // 1er → aujourd'hui
                debut = new Date(yy, mm, 1);
                fin = today;
                break;
            case 'last_month':  // 1er → dernier jour du mois passé
                debut = new Date(yy, mm - 1, 1);
                fin = new Date(yy, mm, 0);
                break;
            case 'this_year':   // 1er janv → aujourd'hui
                debut = new Date(yy, 0, 1);
                fin = today;
                break;
            case 'last_year':   // 1er janv → 31 déc de l'année passée
                debut = new Date(yy - 1, 0, 1);
                fin = new Date(yy - 1, 11, 31);
                break;
            case 'minus_3_days': // il y a 3 jours → aujourd'hui
                debut = new Date(today);
                debut.setDate(dd - 2);
                fin = today;
                break;
            case 'minus_7_days': // 7 derniers jours → aujourd'hui
                debut = new Date(today);
                debut.setDate(dd - 6);
                fin = today;
                break;
        }

        $('#journalDateDebut').val(fmt(debut));
        $('#journalDateFin').val(fmt(fin));
    }

    $('.raccourci-periode').on('click', function () {
        appliquerRaccourci($(this).data('code'));
    });

    // Onglet 3 : Journal d'encaissement
    function chargerJournal() {
        const annee = $('#journalAnnee').val();
        if (!annee) {
            alert('Veuillez sélectionner une année académique.');
            $('#journalAnnee').focus();
            return;
        }
        $('.displayJournalEncaissement').addClass('card p-3 shadow-sm border-0');
        $('.displayJournalEncaissement').html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm text-success me-2"></span>Chargement du journal...</div>');
        $.ajax({
            url: '{{ route("journal-encaissement.display") }}',
            method: 'POST',
            data: $('#journalForm').serialize(),
            success: function(response) {
                $('.displayJournalEncaissement').html(response);
            },
            error: function(xhr) {
                let err = 'Erreur lors du chargement du journal.';
                if (xhr.responseJSON && xhr.responseJSON.message) err = xhr.responseJSON.message;
                $('.displayJournalEncaissement').html('<div class="alert alert-danger"><i class=" ri-alert-line me-2"></i>' + err + '</div>');
            }
        });
    }

    $('#btnAfficherJournal').on('click', function(e) {
        e.preventDefault();
        chargerJournal();
    });

    $('#btnImprimerJournal').on('click', function(e) {
        e.preventDefault();
        const annee = $('#journalAnnee').val();
        if (!annee) { alert('Veuillez sélectionner une année académique.'); return; }
        // Soumettre comme un vrai formulaire POST pour PDF dans un nouvel onglet
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("impression.journal-encaissement") }}';
        form.target = '_blank';
        const fields = $('#journalForm').serializeArray();
        fields.forEach(function(f) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = f.name;
            inp.value = f.value;
            form.appendChild(inp);
        });
        // Ajouter le btn caché
        const btnInput = document.createElement('input');
        btnInput.type = 'hidden';
        btnInput.name = 'btn';
        btnInput.value = 'Imprimer';
        form.appendChild(btnInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });
</script>
@endsection
