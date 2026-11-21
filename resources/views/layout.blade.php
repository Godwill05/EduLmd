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

  <!-- Vendor CSS Files (100 % locaux — aucune ressource distante) -->
  <link href="{{ asset('/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

  <!-- Base historique (conservée pour compatibilité) + Design System EduLMD -->
  <link href="{{ asset('/assets/css/style.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/css/fastbootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/tom-select/tom-select.bootstrap5.css') }}" rel="stylesheet" />
  <link href="{{ asset('/assets/css/edulmd-design.css') }}" rel="stylesheet">

  <script src="{{ asset('/assets/js/jquery-3.7.0.js') }}"></script>
  <script src="{{ asset('/assets/tom-select/tom-select.complete.js') }}"></script>

  {{-- DatePicker --}}
  <link rel="stylesheet" href="{{ asset('/assets/vanillajs-datepicker/datepicker-bs5.min.css') }}">
  <script src="{{ asset('/assets/vanillajs-datepicker/datepicker-full.min.js') }}"></script>

  {{-- JSTree --}}
  <link rel="stylesheet" href="{{ asset('/assets/jstree/dist/themes/default/style.min.css') }}" />
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
  <header id="header" class="header fixed-top d-flex align-items-center">
    <i class="bi bi-list toggle-sidebar-btn" role="button" tabindex="0" aria-label="Ouvrir / fermer le menu" title="Menu"></i>

    <div class="header-context ms-2">
      <div>
        <div class="header-title">@yield('title')</div>
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

    <nav class="header-nav ms-auto" aria-label="Navigation principale">
      <ul class="d-flex align-items-center mb-0">
        <li class="nav-item dropdown pe-3">
          <a class="nav-link nav-profile d-flex align-items-center" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="avatar-initials" aria-hidden="true">{{ substr(Auth::user()->firstname, 0, 1) }}{{ substr(Auth::user()->lastname, 0, 1) }}</span>
            <span class="profile-name ps-2 d-none d-md-block">{{ substr(Auth::user()->firstname, 0, 1) }}. {{ Auth::user()->lastname }}</span>
            <i class="bi bi-chevron-down ms-1 d-none d-md-block" style="font-size:0.75rem;color:var(--ed-faint);"></i>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6>{{ Auth::user()->fullname() }}</h6>
              <span class="text-muted" style="font-size:0.78rem;">{{ Auth::user()->email ?? '' }}</span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('profile') }}">
                <i class="bi bi-person"></i><span>Mon profil</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="/logout">
                <i class="bi bi-box-arrow-right"></i><span>Se déconnecter</span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </header><!-- End Header -->

  <!-- ======= Sidebar ======= -->
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
          <i class="bi bi-speedometer2"></i><span>Accueil</span>
        </a>
      </li>

      <li class="nav-heading">Paramétrage</li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['domainesresao','mentions','specialites','grades','semestres','constantes','annees-etudes','filieres','attribuer-semestres-anneeEtude']) ? '' : 'collapsed' }}" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#" title="Paramètres globaux">
          <i class="bi bi-sliders"></i><span>Paramètres globaux</span><i class="bi bi-chevron-down ms-auto"></i>
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
        <a class="nav-link {{ $sectionOpen(['universites','etablissements','annees-academiques','passage-promotion','salles','utilisateurs','audits']) ? '' : 'collapsed' }}" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#" title="Paramètres spécifiques">
          <i class="bi bi-building-gear"></i><span>Paramètres spécifiques</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse {{ $sectionOpen(['universites','etablissements','annees-academiques','passage-promotion','salles','utilisateurs','audits']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
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
          <li><a class="{{ $isActive('audits') }}" href="{{ route('audits') }}">Audit des actions des utilisateurs</a></li>
          @endcan
        </ul>
      </li>

      <li class="nav-heading">Gestion</li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['ue','ecue','etudiants','inscriptions','liste-etudiants','paiements','previsions','engagement','enseignants','listeEmargement']) ? '' : 'collapsed' }}" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#" title="Enregistrements">
          <i class="bi bi-journal-check"></i><span>Enregistrements</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse {{ $sectionOpen(['ue','ecue','etudiants','inscriptions','liste-etudiants','paiements','previsions','engagement','enseignants','listeEmargement']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
          <li><a class="{{ $isActive('ue') }}" href="{{ route('ue.index') }}">Unité d'Enseignement (UE)</a></li>
          <li><a class="{{ $isActive('ecue') }}" href="{{ route('ecue.index') }}">Eléments Constitutifs d'Unité d'Enseignement (ECUE)</a></li>
          <li><a class="{{ $isActive('etudiants') }}" href="{{ route('etudiants.index') }}">Etudiants</a></li>
          <li><a class="{{ $isActive('inscriptions') }}" href="{{ route('inscriptions.index') }}">Inscriptions</a></li>
          <li><a class="{{ $isActive('liste-etudiants') }}" href="{{ route('liste-etudiants.index') }}">Liste par filière</a></li>
          <li><a class="{{ $isActive('passage-promotion') }}" href="{{ route('passage-promotion.index') }}">Passage de promotion</a></li>
          <li><a class="{{ $isActive('paiements') }}" href="{{ route('paiements.index') }}">Paiements</a></li>
          <li><a class="{{ $isActive('previsions') }}" href="{{ route('previsions.index') }}">Prévisions</a></li>
          <li><a class="{{ $isActive('engagement') }}" href="{{ route('engagement.index') }}">Fiches d'engagement</a></li>
          <li><a class="{{ $isActive('enseignants') }}" href="{{ route('enseignants.index') }}">Enseignants</a></li>
          <li><a class="{{ $isActive('listeEmargement') }}" href="{{ route('listeEmargement') }}">Liste d'émargement</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ $sectionOpen(['attribuer-ue-semestre','afficherUeSemestre','attribuer-ecue-ue','afficherECUEsSemestre','afficher-previsions','evaluations','cours','repartition-salle','afficherNotes','afficherUesNonValidees']) ? '' : 'collapsed' }}" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#" title="Opérations">
          <i class="bi bi-lightning-charge"></i><span>Opérations</span><i class="bi bi-chevron-down ms-auto"></i>
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
          <i class="bi bi-patch-check"></i><span>Délibération</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse {{ $sectionOpen(['parcours','recapitulatif','releveNotes','attestations-diplomes']) ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
          <li><a class="{{ $isActive('parcours') }}" href="{{ route('parcours') }}">Parcours d'un étudiant</a></li>
          <li><a class="{{ $isActive('recapitulatifUes') }}" href="{{ route('recapitulatifUes') }}">Récapitulatif des UEs</a></li>
          <li><a class="{{ $isActive('recapitulatifSemestre') }}" href="{{ route('recapitulatifSemestre') }}">Récapitulatif du semestre</a></li>
          <li><a class="{{ $isActive('recapitulatifAnnuel') }}" href="{{ route('recapitulatifAnnuel') }}">Récapitulatif Annuel</a></li>
          <li><a class="{{ $isActive('recapitulatifGrade') }}" href="{{ route('recapitulatifGrade') }}">Récapitulatif du Grade</a></li>
          <li><a class="{{ $isActive('releveNotes') }}" href="{{ route('releveNotes') }}">Relevés par Semestre</a></li>
          <li><a class="{{ $isActive('attestations-diplomes') }}" href="{{ route('attestations-diplomes.index') }}">Attestations &amp; Diplômes (L3)</a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ $isActive('about-us') }}" href="{{ route('about-us') }}" title="À propos">
          <i class="bi bi-info-circle"></i><span>À propos</span>
        </a>
      </li>
    </ul>

    <div class="sidebar-user">
      <span class="avatar-initials" aria-hidden="true">{{ substr(Auth::user()->firstname, 0, 1) }}{{ substr(Auth::user()->lastname, 0, 1) }}</span>
      <div class="su-text" style="min-width:0;">
        <div class="su-name">{{ Auth::user()->fullname() }}</div>
        <div class="su-role">Connecté(e)</div>
      </div>
    </div>
  </aside><!-- End Sidebar -->

  <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

  <main id="main" class="main">
    <div class="container-fluid">
      @if (session()->has('greetings'))
      <div class="row mb-3">
        <div class="col-12">
          <div class="alert alert-success d-flex align-items-center gap-2 mb-0" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('greetings') }}</span>
          </div>
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

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center" aria-label="Retour en haut"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('/assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('/assets/js/main.js') }}"></script>
  {{-- html2pdf.js — copie locale (plus de dépendance CDN) --}}
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
          let settings = {maxOptions:null};
          new TomSelect(el,settings);
        });
    });

    // Fermeture de la sidebar mobile (voile + Échap + redimensionnement)
    (function () {
        var backdrop = document.getElementById('sidebarBackdrop');
        function closeSidebar() { document.body.classList.remove('toggle-sidebar'); }
        if (backdrop) backdrop.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1200) closeSidebar();
        });
        // Navigation au clavier du bouton menu
        var toggle = document.querySelector('.toggle-sidebar-btn');
        if (toggle) toggle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle.click(); }
        });
    })();
  </script>

  {{-- ═══════════════════════════════════════════════════════════════
       MODALE DE CONFIRMATION GLOBALE — Réutilisable sur toutes pages
       Usage JS: confirmAction({ title, message, icon, iconColor, confirmText, confirmClass, onConfirm })
  ═══════════════════════════════════════════════════════════════════ --}}
  <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
      <div class="modal-content border-0 shadow-lg" style="border-radius:1.25rem;overflow:hidden;">
        <div class="modal-body p-0">
          {{-- Bande colorée icône --}}
          <div id="gcm-icon-band" class="text-center py-4" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
            <div id="gcm-icon-circle" style="width:68px;height:68px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto;">
              <i id="gcm-icon" class="bi bi-question-circle-fill" style="font-size:2rem;color:#fff;"></i>
            </div>
          </div>
          {{-- Contenu --}}
          <div class="px-4 pt-4 pb-3 text-center">
            <h5 id="gcm-title" class="fw-bold mb-2" style="font-size:1.1rem;">Confirmation</h5>
            <p id="gcm-message" class="text-muted mb-0" style="font-size:0.92rem;line-height:1.5;"></p>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4 justify-content-center gap-3">
          <button type="button" class="btn btn-light px-4 fw-semibold" data-bs-dismiss="modal" style="border-radius:.75rem;">
            <i class="bi bi-x-circle me-1"></i>Annuler
          </button>
          <button type="button" id="gcm-confirm-btn" class="btn btn-primary px-4 fw-semibold" style="border-radius:.75rem;">
            <i id="gcm-confirm-icon" class="bi bi-check-circle me-1"></i>
            <span id="gcm-confirm-text">Confirmer</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    /**
     * confirmAction(options) — Ouvre la modale de confirmation globale
     * @param {Object} options
     *   title        {string}   Titre (défaut: "Confirmation")
     *   message      {string}   Corps du message
     *   icon         {string}   Classe Bootstrap Icons (défaut: "bi-question-circle-fill")
     *   gradient     {string}   CSS gradient bande (défaut: indigo)
     *   confirmText  {string}   Texte du bouton OK (défaut: "Confirmer")
     *   confirmClass {string}   Classe btn du bouton OK (défaut: "btn-danger")
     *   confirmIcon  {string}   Icône bouton OK (défaut: "bi-check-circle")
     *   onConfirm    {Function} Callback au clic Confirmer
     */
    function confirmAction(options) {
        const o = Object.assign({
            title:        'Confirmation',
            message:      'Êtes-vous sûr ?',
            icon:         'bi-question-circle-fill',
            gradient:     'linear-gradient(135deg,#4f46e5,#7c3aed)',
            confirmText:  'Confirmer',
            confirmClass: 'btn-danger',
            confirmIcon:  'bi-check-circle',
            onConfirm:    function() {}
        }, options);

        // Remplir
        document.getElementById('gcm-title').textContent   = o.title;
        document.getElementById('gcm-message').innerHTML   = o.message;
        document.getElementById('gcm-icon').className      = 'bi ' + o.icon;
        document.getElementById('gcm-icon-band').style.background = o.gradient;
        document.getElementById('gcm-confirm-text').textContent   = o.confirmText;
        document.getElementById('gcm-confirm-icon').className     = 'bi ' + o.confirmIcon + ' me-1';

        // Bouton confirmer : reset classes
        const btn = document.getElementById('gcm-confirm-btn');
        btn.className = 'btn px-4 fw-semibold ' + o.confirmClass;
        btn.style.borderRadius = '.75rem';

        // Handler unique (évite les doubles clics)
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('globalConfirmModal'));
            if (modal) modal.hide();
            setTimeout(o.onConfirm, 150);
        });

        // Ouvrir
        new bootstrap.Modal(document.getElementById('globalConfirmModal')).show();
    }

    /**
     * confirmDelete(formEl, label) — Raccourci pour confirmer la suppression d'un formulaire
     * @param {HTMLElement} formEl  Le formulaire à soumettre
     * @param {string}      label   Ce qu'on supprime (ex: "ce paiement de 50 000 F")
     */
    function confirmDelete(formEl, label) {
        confirmAction({
            title:        'Supprimer ' + (label || 'cet élément'),
            message:      'Cette action est <strong>irréversible</strong>.<br>Voulez-vous vraiment supprimer ' + (label || 'cet élément') + ' ?',
            icon:         'bi-trash3-fill',
            gradient:     'linear-gradient(135deg,#c62828,#ef5350)',
            confirmText:  'Supprimer',
            confirmClass: 'btn-danger',
            confirmIcon:  'bi-trash3',
            onConfirm:    function() { formEl.submit(); }
        });
    }
  </script>

  @yield('js')

</body>

</html>
