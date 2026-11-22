<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>..::LMD Web Solutions | Licence - Master - Doctorat::..</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Favicons -->
  <link href="{{ asset('favicon.ico') }}" rel="icon">
  <link href="{{ asset('/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Feuille de style unique — Tailwind compilé en local (aucune ressource distante) -->
  <link href="{{ asset('/assets/css/edulmd.css') }}" rel="stylesheet">

  <!-- Icônes RemixIcon (auto-hébergées) -->
  <link href="{{ asset('/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">

  <!-- Libs tierces locales -->
  <link href="{{ asset('/assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('/assets/tom-select/edulmd-tom-select.css') }}">
  <link rel="stylesheet" href="{{ asset('/assets/vanillajs-datepicker/datepicker-edulmd.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/assets/jstree/dist/themes/default/style.min.css') }}">

  <script src="{{ asset('/assets/js/jquery-3.7.0.js') }}"></script>
  <script src="{{ asset('/assets/tom-select/tom-select.complete.js') }}"></script>
  <script src="{{ asset('/assets/vanillajs-datepicker/datepicker-full.min.js') }}"></script>
  <script src="{{ asset('/assets/jstree/dist/jstree.min.js') }}"></script>

  @yield('css')
</head>

<body>

  @php
    $routeName = request()->route() ? (request()->route()->getName() ?? '') : '';
    $isActive = fn($prefix) => str_starts_with($routeName, $prefix) ? 'active' : '';
    $sectionOpen = function($prefixes) use ($routeName) {
        foreach ((array) $prefixes as $p) {
            if (str_starts_with($routeName, $p)) return true;
        }
        return false;
    };
  @endphp

  <!-- ======= Header ======= -->
  <header id="header" class="header">
    <i class="ri-menu-line toggle-sidebar-btn" role="button" tabindex="0" aria-label="Ouvrir / fermer le menu" title="Menu"></i>

    <div class="header-context min-w-0">
      <div>
        <div class="header-title truncate">@yield('title')</div>
        <nav aria-label="Fil d'Ariane">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
            @if (request()->path() !== "home")
            <li class="breadcrumb-item active" aria-current="page">@yield('title')</li>
            @endif
          </ol>
        </nav>
      </div>
    </div>

    <nav class="header-nav" aria-label="Navigation principale">
      <ul>
        <li class="nav-item">
          <button type="button" class="ed-search-trigger" id="edSearchTrigger"
                  aria-label="Palette de commandes (Ctrl+K)" title="Palette de commandes — Ctrl+K">
            <i class="ri-search-line"></i>
            <span class="ed-search-label">Rechercher ou aller à…</span>
            <kbd>Ctrl K</kbd>
          </button>
        </li>
        <li class="nav-item">
          <button type="button" class="ed-keys-trigger" data-ed-keys
                  aria-label="Raccourcis clavier" title="Raccourcis clavier — appuyez sur ?">
            <i class="ri-question-line"></i>
          </button>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link nav-profile flex items-center gap-2" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="avatar-initials" aria-hidden="true">{{ substr(Auth::user()->firstname, 0, 1) }}{{ substr(Auth::user()->lastname, 0, 1) }}</span>
            <span class="profile-name hidden md:block">{{ substr(Auth::user()->firstname, 0, 1) }}. {{ Auth::user()->lastname }}</span>
            <i class="ri-arrow-down-s-line hidden md:block text-[0.8rem] text-encre-faint"></i>
          </a>

          <ul class="dropdown-menu dropdown-menu-end profile">
            <li class="dropdown-header">
              <h6>{{ Auth::user()->fullname() }}</h6>
              <span class="text-muted text-[0.75rem]">{{ Auth::user()->email ?? '' }}</span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="{{ route('profile') }}">
                <i class="ri-user-line"></i><span>Mon profil</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="/logout">
                <i class="ri-logout-box-line"></i><span>Se déconnecter</span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </header><!-- End Header -->

  <!-- ======= Sidebar (claire) ======= -->
  <aside id="sidebar" class="sidebar" aria-label="Menu de navigation">
    <div class="sidebar-brand">
      <a href="{{ route('home') }}" class="brand-logo" aria-label="EduLMD — Accueil">
        <img src="{{ asset('/logo.png') }}" alt="Logo EduLMD">
      </a>
      <div class="brand-text">
        <div class="brand-name">EduLMD</div>
        <div class="brand-sub">LMD Web Solutions</div>
      </div>
    </div>

    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link {{ $routeName === 'home' ? 'active' : '' }}" href="{{ route('home') }}" title="Accueil">
          <i class="ri-dashboard-line"></i><span>Accueil</span>
        </a>
      </li>

      {{-- Pilotage : réservé à ceux qui consultent le journal (permission Audit),
           car il expose les chiffres financiers et les taux de réussite. --}}
      @can('Audit')
      <li class="nav-item">
        <a class="nav-link {{ $routeName === 'pilotage' ? 'active' : '' }}" href="{{ route('pilotage') }}" title="Pilotage">
          <i class="ri-line-chart-line"></i><span>Pilotage</span>
        </a>
      </li>
      @endcan

      <li class="nav-heading">Paramétrage</li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['domainesresao','mentions','specialites','grades','semestres','constantes','annees-etudes','filieres','attribuer-semestres-anneeEtude']) ? '' : 'collapsed' }}" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#" title="Paramètres globaux">
          <i class="ri-settings-3-line"></i><span>Paramètres globaux</span><i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse {{ $sectionOpen(['domainesresao','mentions','specialites','grades','semestres','constantes','annees-etudes','filieres','attribuer-semestres-anneeEtude']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
          <li><a class="{{ $isActive('domainesresao') }}" href="{{ route('domainesresao.index') }}">Domaines RESAO</a></li>
          <li><a class="{{ $isActive('mentions') }}" href="{{ route('mentions.index') }}">Mentions</a></li>
          <li><a class="{{ $isActive('specialites') }}" href="{{ route('specialites.index') }}">Spécialités</a></li>
          <li><a class="{{ $isActive('grades') }}" href="{{ route('grades.index') }}">Grades</a></li>
          <li><a class="{{ $isActive('semestres') }}" href="{{ route('semestres.index') }}">Semestres</a></li>
          <li><a class="{{ $isActive('constantes') }}" href="{{ route('constantes.index') }}">Constantes</a></li>
          <li><a class="{{ $isActive('semestres-etudes') }}" href="{{ route('semestres-etudes.index') }}">Semestres Etudes</a></li>
          <li><a class="{{ $isActive('annees-etudes') }}" href="{{ route('annees-etudes.index') }}">Années Etudes</a></li>
          <li><a class="{{ $isActive('filieres') }}" href="{{ route('filieres.index') }}">Filières complètes</a></li>
          <li><a class="{{ $isActive('attribuer-semestres-anneeEtude') }}" href="{{ route('attribuer-semestres-anneeEtude.index') }}">Semestre -&gt; Années Etudes</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['universites','etablissements','annees-academiques','passage-promotion','salles','utilisateurs','audits','journal-activite']) ? '' : 'collapsed' }}" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#" title="Paramètres spécifiques">
          <i class="ri-building-line"></i><span>Paramètres spécifiques</span><i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse {{ $sectionOpen(['universites','etablissements','annees-academiques','passage-promotion','salles','utilisateurs','audits','journal-activite']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
          @can('Gérer les universités')
          <li><a class="{{ $isActive('universites') }}" href="{{ route('universites.index') }}">Universités</a></li>
          @endcan
          @can('Gérer les établissements')
          <li><a class="{{ $isActive('etablissements') }}" href="{{ route('etablissements.index') }}">UFR/Ecole/Facultés</a></li>
          @endcan
          <li><a class="{{ $isActive('annees-academiques') }}" href="{{ route('annees-academiques.index') }}">Année Académique</a></li>
          <li><a class="{{ $isActive('passage-promotion') }}" href="{{ route('passage-promotion.index') }}">Passage de promotion</a></li>
          <li><a class="{{ $isActive('salles') }}" href="{{ route('salles.index') }}">Gestion des salles</a></li>
          @can('Gérer les utilisateurs')
          <li><a class="{{ $isActive('utilisateurs') }}" href="{{ route('utilisateurs.index') }}">Gestion des utilisateurs</a></li>
          <li><a class="{{ $isActive('roles') }}" href="{{ route('roles.index') }}">Rôles et niveaux</a></li>
          <li><a class="{{ $isActive('journal-activite') }}" href="{{ route('journal-activite.index') }}">Journal d'activité</a></li>
          @endcan
        </ul>
      </li>

      <li class="nav-heading">Gestion</li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['ue','ecue','etudiants','inscriptions','liste-etudiants','paiements','previsions','engagement','enseignants','listeEmargement','qualite-donnees','imports']) ? '' : 'collapsed' }}" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#" title="Enregistrements">
          <i class="ri-book-read-line"></i><span>Enregistrements</span><i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse {{ $sectionOpen(['ue','ecue','etudiants','inscriptions','liste-etudiants','paiements','previsions','engagement','enseignants','listeEmargement','qualite-donnees','imports']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
          <li><a class="{{ $isActive('ue') }}" href="{{ route('ue.index') }}">Unité d'Enseignement (UE)</a></li>
          <li><a class="{{ $isActive('ecue') }}" href="{{ route('ecue.index') }}">Eléments Constitutifs (ECUE)</a></li>
          <li><a class="{{ $isActive('etudiants') }}" href="{{ route('etudiants.index') }}">Etudiants</a></li>
          <li><a class="{{ $isActive('inscriptions') }}" href="{{ route('inscriptions.index') }}">Inscriptions</a></li>
          <li><a class="{{ $isActive('liste-etudiants') }}" href="{{ route('liste-etudiants.index') }}">Liste par filière</a></li>
          <li><a class="{{ $isActive('passage-promotion') }}" href="{{ route('passage-promotion.index') }}">Passage de promotion</a></li>
          <li><a class="{{ $isActive('paiements') }}" href="{{ route('paiements.index') }}">Paiements</a></li>
          <li><a class="{{ $isActive('previsions') }}" href="{{ route('previsions.index') }}">Prévisions</a></li>
          <li><a class="{{ $isActive('engagement') }}" href="{{ route('engagement.index') }}">Fiches d'engagement</a></li>
          <li><a class="{{ $isActive('enseignants') }}" href="{{ route('enseignants.index') }}">Enseignants</a></li>
          <li><a class="{{ $isActive('listeEmargement') }}" href="{{ route('listeEmargement') }}">Liste d'émargement</a></li>
          <li><a class="{{ $isActive('imports') }}" href="{{ route('imports.index') }}">Importer des données</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ $isActive('qualite-donnees') }}" href="{{ route('qualite-donnees.index') }}" title="Qualité des données">
          <i class="ri-shield-check-line"></i><span>Qualité des données</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['attribuer-ue-semestre','afficherUeSemestre','attribuer-ecue-ue','afficherECUEsSemestre','afficher-previsions','evaluations','cours','repartition-salle','afficherNotes','afficherUesNonValidees']) ? '' : 'collapsed' }}" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#" title="Opérations">
          <i class="ri-flashlight-line"></i><span>Opérations</span><i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse {{ $sectionOpen(['attribuer-ue-semestre','afficherUeSemestre','attribuer-ecue-ue','afficherECUEsSemestre','afficher-previsions','evaluations','cours','repartition-salle','afficherNotes','afficherUesNonValidees']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
          <li><a class="{{ $isActive('attribuer-ue-semestre') }}" href="{{ route('attribuer-ue-semestre.index') }}">Attribuer une UE à un semestre</a></li>
          <li><a class="{{ $isActive('afficherUeSemestre') }}" href="{{ route('afficherUeSemestre') }}">Affichage des UEs par semestre</a></li>
          <li><a class="{{ $isActive('attribuer-ecue-ue') }}" href="{{ route('attribuer-ecue-ue.index') }}">Attribuer une ECUE à une UE</a></li>
          <li><a class="{{ $isActive('afficherECUEsSemestre') }}" href="{{ route('afficherECUEsSemestre') }}">Affichage des ECUEs par semestre</a></li>
          <li><a class="{{ $isActive('afficher-previsions') }}" href="{{ route('afficher-previsions') }}">Affichage des prévisions</a></li>
          <li><a class="{{ $isActive('evaluations') }}" href="{{ route('evaluations.index') }}">Gestion des évaluations</a></li>
          <li><a class="{{ $isActive('cours') }}" href="{{ route('cours.index') }}">Gestion des cours</a></li>
          <li><a class="{{ $isActive('repartition-salle') }}" href="{{ route('repartition-salle') }}">Répartition en salle</a></li>
          <li><a class="{{ $isActive('afficherNotes') }}" href="{{ route('afficherNotes') }}">Insertion des notes</a></li>
          <li><a class="{{ $isActive('afficherUesNonValidees') }}" href="{{ route('afficherUesNonValidees') }}">Affichage des UEs non validées</a></li>
        </ul>
      </li>

      <li class="nav-heading">Délibération</li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['parcours','recapitulatif','releveNotes','attestations-diplomes']) ? '' : 'collapsed' }}" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#" title="Délibération">
          <i class="ri-verified-badge-line"></i><span>Délibération</span><i class="ri-arrow-down-s-line ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse {{ $sectionOpen(['parcours','recapitulatif','releveNotes','attestations-diplomes']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
          <li><a class="{{ $isActive('parcours') }}" href="{{ route('parcours') }}">Parcours d'un étudiant</a></li>
          <li><a class="{{ $isActive('recapitulatifUes') }}" href="{{ route('recapitulatifUes') }}">Récapitulatif des UEs</a></li>
          <li><a class="{{ $isActive('recapitulatifSemestre') }}" href="{{ route('recapitulatifSemestre') }}">Récapitulatif du semestre</a></li>
          <li><a class="{{ $isActive('recapitulatifAnnuel') }}" href="{{ route('recapitulatifAnnuel') }}">Récapitulatif Annuel</a></li>
          <li><a class="{{ $isActive('recapitulatifGrade') }}" href="{{ route('recapitulatifGrade') }}">Récapitulatif du Grade</a></li>
          <li><a class="{{ $isActive('releveNotes') }}" href="{{ route('releveNotes') }}">Relevés par Semestre</a></li>
          <li><a class="{{ $isActive('emploi-du-temps') }}" href="{{ route('emploi-du-temps.index') }}">Emploi du temps</a></li>
          <li><a class="{{ $isActive('attestations-diplomes') }}" href="{{ route('attestations-diplomes.index') }}">Attestations &amp; Diplômes (L3)</a></li>
          {{-- Révoquer un diplôme engage l'établissement : niveau 1 seulement.
               La route le vérifie aussi ; le lien suit pour ne pas proposer
               un écran qui répondrait 403. --}}
          @if (\App\Support\Hierarchie::niveau(auth()->user()) === 1)
          <li><a class="{{ $isActive('revocation-documents') }}" href="{{ route('revocation.index') }}">Révoquer un document</a></li>
          @endif
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ $isActive('about-us') }}" href="{{ route('about-us') }}" title="À propos">
          <i class="ri-information-line"></i><span>À propos</span>
        </a>
      </li>
    </ul>

    <div class="sidebar-user">
      <span class="avatar-initials" aria-hidden="true">{{ substr(Auth::user()->firstname, 0, 1) }}{{ substr(Auth::user()->lastname, 0, 1) }}</span>
      <div class="su-text min-w-0">
        <div class="su-name">{{ Auth::user()->fullname() }}</div>
        <div class="su-role">Connecté(e)</div>
      </div>
    </div>
  </aside><!-- End Sidebar -->

  <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

  <main id="main" class="main">
    <div class="container-fluid">
      @if (session()->has('greetings'))
      <div class="mb-4">
        <div class="alert alert-success flex items-center gap-2 mb-0" role="status">
          <i class="ri-checkbox-circle-fill"></i>
          <span>{{ session('greetings') }}</span>
        </div>
      </div>
      @endif
    </div>
    @yield('content')
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright {{ now()->year }} | <strong>LMD Web Solutions</strong> | Tous droits réservés.
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top" aria-label="Retour en haut"><i class="ri-arrow-up-line"></i></a>

  <!-- Libs tierces locales -->
  <script src="{{ asset('/assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

  <!-- Moteur d'interactions EduLMD (remplace Bootstrap JS) -->
  <script src="{{ asset('/assets/js/edulmd-ui.js') }}"></script>

  <!-- Comportements de l'application -->
  <script src="{{ asset('/assets/js/edulmd-shell.js') }}"></script>
  <script>
    window.EDULMD_SEARCH_URL = "{{ route('recherche.etudiants') }}";
  </script>
  {{-- Palette de commandes unifiée, aide clavier, navigation « g » : n'ajoute
       aucune fonctionnalité métier, seulement des chemins plus courts.
       Chargée avant search.js, qui renonce alors à sa palette d'étudiants. --}}
  <script src="{{ asset('/assets/js/edulmd-raccourcis.js') }}"></script>
  <script src="{{ asset('/assets/js/edulmd-search.js') }}"></script>
  <script>
    window.EDULMD_PAY_SITUATION = "{{ url('/api/paiement-rapide') }}";
    window.EDULMD_PAY_STORE = "{{ url('/api/paiement-rapide') }}";
  </script>
  <script src="{{ asset('/assets/js/edulmd-pay.js') }}"></script>
  <script>
    window.EDULMD_BULK_STATUT = "{{ route('actions-groupees.statut') }}";
    window.EDULMD_BULK_EXPORT = "{{ route('actions-groupees.export') }}";
    window.EDULMD_BULK_PRINT = "{{ route('actions-groupees.impression') }}";
    @php
      /* Statuts mis en cache : évite une requête sur chaque page. */
      $edStatuts = \Illuminate\Support\Facades\Cache::remember('edulmd.statuts', 3600, function () {
          return \App\Models\Statut::orderBy('LibStatut')
              ->get(['codeStatut', 'LibStatut'])
              ->map(fn($s) => ['code' => $s->codeStatut, 'libelle' => $s->LibStatut])
              ->values()
              ->all();
      });
    @endphp
    window.EDULMD_STATUTS = @json($edStatuts);
  </script>
  <script src="{{ asset('/assets/js/edulmd-bulk.js') }}"></script>
  <script>
    window.EDULMD_ECHEANCIER_URL = "{{ route('previsions.etudiant-echeancier') }}";
    window.EDULMD_ECHEANCIER_SAVE = "{{ route('previsions.enregistrer-echeancier') }}";
  </script>
  <script src="{{ asset('/assets/js/edulmd-previsions.js') }}"></script>

  {{-- html2pdf.js — copie locale --}}
  <script src="{{ asset('/assets/vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>

  <script>
    // Setup global CSRF token pour toutes les requêtes AJAX jQuery
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).ready(function() {
        document.querySelectorAll('.form-select:not(.no-tomselect)').forEach((el)=>{
          /*
           * Les listes à cascade (semestre, UE, ECUE de la page « Notes »)
           * sont remplies par JavaScript après coup : TomSelect ne voit pas
           * ces modifications et affiche une liste vide. Elles portent donc
           * la classe .no-tomselect et gardent le <select> natif.
           */
          if (el.disabled || el.dataset.cascade === '1') return;
          let settings = {maxOptions:null};
          new TomSelect(el,settings);
        });

        /*
         * Remplace l'instance TomSelect d'un select par les options à jour.
         * À appeler après tout remplissage dynamique d'un select enrichi.
         */
        window.edulmdSyncSelect = function (select) {
          if (!select) return;
          if (select.tomselect) {
            select.tomselect.clearOptions();
            Array.prototype.forEach.call(select.options, function (o) {
              if (o.value !== '') select.tomselect.addOption({ value: o.value, text: o.textContent });
            });
            select.tomselect.refreshOptions(false);
            select.tomselect.setValue(select.value, true);
          }
        };
    });
  </script>

  {{-- ═══════════════════════════════════════════════════════════════
       MODALE DE CONFIRMATION GLOBALE — Réutilisable sur toutes pages
       Usage JS: confirmAction({ title, message, icon, iconColor, confirmText, confirmClass, onConfirm })
  ═══════════════════════════════════════════════════════════════════ --}}
  <div class="modal" id="globalConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered max-w-[420px]">
      <div class="modal-content">
        <div class="modal-body p-0">
          {{-- Bande colorée icône --}}
          <div id="gcm-icon-band" class="text-center py-4" style="background:linear-gradient(135deg,#822A40,#B55168);">
            <div id="gcm-icon-circle" class="mx-auto flex items-center justify-center rounded-full" style="width:68px;height:68px;background:rgba(255,255,255,0.2);">
              <i id="gcm-icon" class="ri-question-fill text-white" style="font-size:2rem;"></i>
            </div>
          </div>
          {{-- Contenu --}}
          <div class="px-6 pt-6 pb-4 text-center">
            <h5 id="gcm-title" class="text-[1.05rem] font-bold text-encre mb-2">Confirmation</h5>
            <p id="gcm-message" class="text-encre-muted text-[0.9rem] leading-relaxed mb-0"></p>
          </div>
        </div>
        <div class="modal-footer justify-center gap-3 border-0 pt-0 pb-6 px-6">
          <button type="button" class="btn btn-light px-4 fw-semibold" data-bs-dismiss="modal">
            <i class="ri-close-circle-line me-1"></i>Annuler
          </button>
          <button type="button" id="gcm-confirm-btn" class="btn btn-primary px-4 fw-semibold">
            <i id="gcm-confirm-icon" class="ri-checkbox-circle-line me-1"></i>
            <span id="gcm-confirm-text">Confirmer</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    /**
     * confirmAction(options) — Ouvre la modale de confirmation globale
     */
    function confirmAction(options) {
        const o = Object.assign({
            title:        'Confirmation',
            message:      'Êtes-vous sûr ?',
            icon:         'ri-question-fill',
            gradient:     'linear-gradient(135deg,#822A40,#B55168)',
            confirmText:  'Confirmer',
            confirmClass: 'btn-danger',
            confirmIcon:  'ri-checkbox-circle-line',
            onConfirm:    function() {}
        }, options);

        document.getElementById('gcm-title').textContent   = o.title;
        document.getElementById('gcm-message').innerHTML   = o.message;
        document.getElementById('gcm-icon').className      = o.icon + ' text-white';
        document.getElementById('gcm-icon-band').style.background = o.gradient;
        document.getElementById('gcm-confirm-text').textContent   = o.confirmText;
        document.getElementById('gcm-confirm-icon').className     = o.confirmIcon + ' me-1';

        const btn = document.getElementById('gcm-confirm-btn');
        btn.className = 'btn px-4 fw-semibold ' + o.confirmClass;

        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            const modal = EduUI.Modal.getInstance(document.getElementById('globalConfirmModal'));
            if (modal) modal.hide();
            setTimeout(o.onConfirm, 150);
        });

        EduUI.Modal.getOrCreateInstance(document.getElementById('globalConfirmModal')).show();
    }

    /**
     * confirmDelete(formEl, label) — Raccourci pour confirmer la suppression
     */
    function confirmDelete(formEl, label) {
        confirmAction({
            title:        'Supprimer ' + (label || 'cet élément'),
            message:      'Cette action est <strong>irréversible</strong>.<br>Voulez-vous vraiment supprimer ' + (label || 'cet élément') + ' ?',
            icon:         'ri-delete-bin-6-fill',
            gradient:     'linear-gradient(135deg,#B91C1C,#EF4444)',
            confirmText:  'Supprimer',
            confirmClass: 'btn-danger',
            confirmIcon:  'ri-delete-bin-line',
            onConfirm:    function() { formEl.submit(); }
        });
    }
  </script>

  @yield('js')

</body>

</html>
