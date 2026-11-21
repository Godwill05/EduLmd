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
  <link href="favicon.ico" rel="icon">
  <link href={{asset("/assets/img/apple-touch-icon.png")}} rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href={{asset("/assets/vendor/bootstrap/css/bootstrap.min.css")}} rel="stylesheet">
  <link href={{asset("/assets/vendor/bootstrap-icons/bootstrap-icons.css")}} rel="stylesheet">
  <link href={{asset("/assets/vendor/boxicons/css/boxicons.min.css")}} rel="stylesheet">
  {{-- <link href={{asset("/assets/vendor/quill/quill.snow.css")}} rel="stylesheet">
  <link href={{asset("/assets/vendor/quill/quill.bubble.css")}} rel="stylesheet"> --}}
  <link href={{asset("/assets/vendor/remixicon/remixicon.css")}} rel="stylesheet">
  <link href={{asset("/assets/vendor/simple-datatables/style.css")}} rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href={{asset("/assets/css/style.css")}} rel="stylesheet">
  <link href={{asset("/assets/css/fastbootstrap.min.css")}} rel="stylesheet">
  <link href={{asset("/assets/tom-select/tom-select.bootstrap5.css")}} rel="stylesheet" />
  <script src={{asset("/assets/js/jquery-3.7.0.js")}}></script>
  <script src={{asset("/assets/tom-select/tom-select.complete.js")}}></script>

  {{-- DatePicker --}}
  <link rel="stylesheet" href={{asset("/assets/vanillajs-datepicker/datepicker-bs5.min.css")}}>
  <script src={{asset("/assets/vanillajs-datepicker/datepicker-full.min.js")}}></script>

  {{-- JSTree --}}
  <link rel="stylesheet" href={{asset("/assets/jstree/dist/themes/default/style.min.css")}} />
  <script src={{asset("/assets/jstree/dist/jstree.min.js")}}></script>
  @yield('css')

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="{{ route('home') }}" class="logo d-flex align-items-center">
        <img src="{{asset('/logo.png')}}" alt="">
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->

    {{-- <div class="search-bar">
      <form class="search-form d-flex align-items-center" method="POST" action="#">
        <input type="text" name="query" placeholder="Search" title="Enter search keyword">
        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
      </form>
    </div><!-- End Search Bar --> --}}

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

       {{--  <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle " href="#">
            <i class="bi bi-search"></i>
          </a>
        </li><!-- End Search Icon--> --}}

        {{-- <li class="nav-item dropdown">

          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-bell"></i>
            <span class="badge bg-primary badge-number">4</span>
          </a><!-- End Notification Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              You have 4 new notifications
              <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-exclamation-circle text-warning"></i>
              <div>
                <h4>Lorem Ipsum</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>30 min. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-x-circle text-danger"></i>
              <div>
                <h4>Atque rerum nesciunt</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>1 hr. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <h4>Sit rerum fuga</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>2 hrs. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="notification-item">
              <i class="bi bi-info-circle text-primary"></i>
              <div>
                <h4>Dicta reprehenderit</h4>
                <p>Quae dolorem earum veritatis oditseno</p>
                <p>4 hrs. ago</p>
              </div>
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>
            <li class="dropdown-footer">
              <a href="#">Show all notifications</a>
            </li>

          </ul><!-- End Notification Dropdown Items -->

        </li><!-- End Notification Nav --> --}}

        {{-- <li class="nav-item dropdown">

          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-chat-left-text"></i>
            <span class="badge bg-success badge-number">3</span>
          </a><!-- End Messages Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow messages">
            <li class="dropdown-header">
              You have 3 new messages
              <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                <img src={{asset("/assets/img/messages-1.jpg" alt="" class="rounded-circle")}}>
                <div>
                  <h4>Maria Hudson</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>4 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                <img src={{asset("/assets/img/messages-2.jpg" alt="" class="rounded-circle")}}>
                <div>
                  <h4>Anna Nelson</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>6 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                <img src={{asset("/assets/img/messages-3.jpg" alt="" class="rounded-circle")}}>
                <div>
                  <h4>David Muldon</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>8 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="dropdown-footer">
              <a href="#">Show all messages</a>
            </li>

          </ul><!-- End Messages Dropdown Items -->

        </li><!-- End Messages Nav --> --}}

        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            {{-- <img src={{asset("/assets/img/profile-img.jpg")}} alt="Profile" class="rounded-circle"> --}}
            <i class="fs-3 bi bi-person-circle d-md-block d-none"></i>
            <span class="avatar d-md-none">{{substr(Auth::user()->firstname,0,1)}}{{substr(Auth::user()->lastname,0,1)}}</span>
            <span class="d-none d-md-block dropdown-toggle ps-2">{{substr(Auth::user()->firstname,0,1)}}. {{Auth::user()->lastname}}</span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6>{{Auth::user()->fullname()}}</h6>
              {{-- <span>Web Designer</span> --}}
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('profile') }}">
                <i class="bi bi-person"></i>
                <span>Mon profil</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>


            <li>
              <a class="dropdown-item d-flex align-items-center" href="/logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Se déconnecter</span>
              </a>
            </li>

          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link " href="{{ route('home') }}">
          <i class="bi bi-grid"></i>
          <span>Accueil</span>
        </a>
      </li><!-- End Dashboard Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Paramètres globaux</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('domainesresao.index') }}">
              <i class="bi bi-circle"></i><span>Domaines RESAO</span>
            </a>
          </li>
          <li>
            <a href="{{ route('mentions.index') }}">
              <i class="bi bi-circle"></i><span>Mentions</span>
            </a>
          </li>
          <li>
            <a href="{{ route('specialites.index') }}">
              <i class="bi bi-circle"></i><span>Spécialités</span>
            </a>
          </li>
          <li>
            <a href="{{ route('grades.index') }}">
              <i class="bi bi-circle"></i><span>Grades</span>
            </a>
          </li>
          <li>
            <a href="{{ route('semestres.index') }}">
              <i class="bi bi-circle"></i><span>Semestres</span>
            </a>
          </li>
          <li>
            <a href="{{ route('constantes.index') }}">
              <i class="bi bi-circle"></i><span>Constantes</span>
            </a>
          </li>
          <li>
            <a href="{{ route('semestres-etudes.index') }}">
              <i class="bi bi-circle"></i><span>Semestres Etudes</span>
            </a>
          </li>
          <li>
            <a href="{{ route('annees-etudes.index') }}">
              <i class="bi bi-circle"></i><span>Années Etudes</span>
            </a>
          </li>
          <li>
            <a href="{{ route('filieres.index') }}">
              <i class="bi bi-circle"></i><span>Filières complètes</span>
            </a>
          </li>
          <li>
            <a href="{{ route('attribuer-semestres-anneeEtude.index') }}">
              <i class="bi bi-circle"></i><span>Semestre -> Années Etudes</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Paramètres spécifiques</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          @can('Gérer les universités')
          <li>
            <a href="{{ route('universites.index') }}">
              <i class="bi bi-circle"></i><span>Universités</span>
            </a>
          </li>
          @endcan
          @can('Gérer les établissements')
          <li>
            <a href="{{ route('etablissements.index') }}">
              <i class="bi bi-circle"></i><span>UFR/Ecole/Facultés</span>
            </a>
          </li>
          @endcan
          <li>
            <a href="{{ route('annees-academiques.index') }}">
              <i class="bi bi-circle"></i><span>Année Académique</span>
            </a>
          </li>
          <li>
            <a href="{{ route('passage-promotion.index') }}">
              <i class="bi bi-circle"></i><span>Passage de promotion</span>
            </a>
          </li>
          <li>
            <a href="{{ route('salles.index') }}">
              <i class="bi bi-circle"></i><span>Gestion des salles</span>
            </a>
          </li>
          @can('Gérer les utilisateurs')
          <li>
            <a href="{{ route('utilisateurs.index') }}">
              <i class="bi bi-circle"></i><span>Gestion des utilisateurs</span>
            </a>
          </li>
          <li>
              <a href="{{ route('audits') }}">
                <i class="bi bi-circle"></i><span>Audit des actions des utilisateurs</span>
            </a>
          </li>
          @endcan
        </ul>
      </li><!-- End Forms Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-layout-text-window-reverse"></i><span>Enregistrements</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('ue.index') }}">
              <i class="bi bi-circle"></i><span>Unité d'Enseignement (UE)</span>
            </a>
          </li>
          <li>
            <a href="{{ route('ecue.index') }}">
              <i class="bi bi-circle"></i><span>Eléments Constitutifs d'Unité d'Enseignement (ECUE)</span>
            </a>
          </li>
          <li>
            <a href="{{ route('etudiants.index') }}">
              <i class="bi bi-circle"></i><span>Etudiants</span>
            </a>
          </li>
          <li>
            <a href="{{ route('inscriptions.index') }}">
              <i class="bi bi-circle"></i><span>Inscriptions</span>
            </a>
          </li>
          <li>
            <a href="{{ route('liste-etudiants.index') }}">
              <i class="bi bi-circle"></i><span>Liste par filière</span>
            </a>
          </li>
          <li>
            <a href="{{ route('passage-promotion.index') }}">
              <i class="bi bi-circle"></i><span>Passage de promotion</span>
            </a>
          </li>
          <li>
            <a href="{{ route('paiements.index') }}">
              <i class="bi bi-circle"></i><span>Paiements</span>
            </a>
          </li>
          <li>
            <a href="{{ route('previsions.index') }}">
              <i class="bi bi-circle"></i><span>Prévisions</span>
            </a>
          </li>
          <li>
            <a href="{{ route('engagement.index') }}">
              <i class="bi bi-circle"></i><span>Fiches d'engagement</span>
            </a>
          </li>
          <li>
            <a href="{{ route('enseignants.index') }}">
              <i class="bi bi-circle"></i><span>Enseignants</span>
            </a>
          </li>
          <li>
            <a href="{{ route('listeEmargement') }}">
              <i class="bi bi-circle"></i><span>Liste d'émargement</span>
            </a>
          </li>
        </ul>
      </li><!-- End Tables Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bar-chart"></i><span>Opérations</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{route('attribuer-ue-semestre.index')}}">
              <i class="bi bi-circle"></i><span>Attribuer une UE à un semestre</span>
            </a>
          </li>
          <li>
            <a href="{{ route('afficherUeSemestre') }}">
              <i class="bi bi-circle"></i><span>Affichage des UEs par semestre</span>
            </a>
          </li>
          <li>
            <a href="{{ route('attribuer-ecue-ue.index') }}">
              <i class="bi bi-circle"></i><span>Attribuer une ECUE à une UE</span>
            </a>
          </li>
          <li>
            <a href="{{ route('afficherECUEsSemestre') }}">
              <i class="bi bi-circle"></i><span>Affichage des ECUEs par semestre</span>
            </a>
          </li>
          <li>
            <a href="{{ route('afficher-previsions') }}">
              <i class="bi bi-circle"></i><span>Affichage des prévisions</span>
            </a>
          </li>
          <li>
            <a href="{{ route('evaluations.index') }}">
              <i class="bi bi-circle"></i><span>Gestion des évaluations</span>
            </a>
          </li>
          <li>
            <a href="{{ route('cours.index') }}">
              <i class="bi bi-circle"></i><span>Gestion des cours</span>
            </a>
          </li>
          <li>
            <a href="{{ route('repartition-salle') }}">
              <i class="bi bi-circle"></i><span>Répartition en salle</span>
            </a>
          </li>
          <li>
            <a href="{{ route('afficherNotes') }}">
              <i class="bi bi-circle"></i><span>Insertion des notes</span>
            </a>
          </li>
          <li>
            <a href="{{ route('afficherUesNonValidees') }}">
              <i class="bi bi-circle"></i><span>Affichage des UEs non validées</span>
            </a>
          </li>
        </ul>
      </li><!-- End Charts Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Délibération</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="{{ route('parcours') }}">
              <i class="bi bi-circle"></i><span>Parcours d'un étudiant</span>
            </a>
          </li>
          <li>
            <a href="{{ route('recapitulatifUes') }}">
              <i class="bi bi-circle"></i><span>Récapitulatif des UEs</span>
            </a>
            <a href="{{ route('recapitulatifSemestre') }}">
              <i class="bi bi-circle"></i><span>Récapitulatif du semestre</span>
            </a>
          </li>
          <li>
            <a href="{{ route('recapitulatifAnnuel') }}">
              <i class="bi bi-circle"></i><span>Récapitulatif Annuel</span>
            </a>
          </li>
          <li>
            <a href="{{ route('recapitulatifGrade') }}">
              <i class="bi bi-circle"></i><span>Récapitulatif du Grade</span>
            </a>
          </li>
          <li>
            <a href="{{ route('releveNotes') }}">
              <i class="bi bi-circle"></i><span>Relevés par Semestre</span>
            </a>
          </li>
          <li>
            <a href="{{ route('attestations-diplomes.index') }}">
              <i class="bi bi-circle"></i><span>Attestations &amp; Diplômes (L3)</span>
            </a>
          </li>
        </ul>
      </li><!-- End Icons Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('about-us') }}">
          <i class="bi bi-info-circle"></i>
          <span>À propos</span>
        </a>
      </li>


  </aside><!-- End Sidebar-->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>@yield('title')</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.html">Accueil</a></li>
          @if (request()->path()!=="home")
          <li class="breadcrumb-item active">@yield('title')</li>
          @endif
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <div class="container-fluid">
        @if (session()->has('greetings'))
        <div class="row">
            <div class="alert alert-success">
                {{session('greetings')}}
            </div>
        </div>
        @endif
    </div>
    @yield('content')
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright {{now()->year}} | <strong>LMD Web Solutions</strong> | Tous droits réservés.
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  {{-- <script src={{asset("/assets/vendor/apexcharts/apexcharts.min.js")}}></script> --}}
  <script src={{asset("/assets/vendor/bootstrap/js/bootstrap.bundle.min.js")}}></script>
  {{-- <script src={{asset("/assets/vendor/chart.js/chart.umd.js")}}></script>
  <script src={{asset("/assets/vendor/echarts/echarts.min.js")}}></script>
  <script src={{asset("/assets/vendor/quill/quill.min.js")}}></script> --}}
  <script src={{asset("/assets/vendor/simple-datatables/simple-datatables.js")}}></script>
  {{-- <script src={{asset("/assets/vendor/tinymce/tinymce.min.js")}}></script>
  <script src={{asset("/assets/vendor/php-email-form/validate.js")}}></script> --}}

  <!-- Template Main JS File -->
  <script src={{asset("/assets/js/main.js")}}></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
          <div id="gcm-icon-band" class="text-center py-4" style="background:linear-gradient(135deg,#1a73e8,#4285f4);">
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
     *   gradient     {string}   CSS gradient bande (défaut: bleu)
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
            gradient:     'linear-gradient(135deg,#1a73e8,#4285f4)',
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
