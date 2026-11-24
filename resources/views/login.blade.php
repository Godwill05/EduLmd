<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Connexion — EduLMD</title>
  <meta name="description" content="Connectez-vous à votre espace EduLMD">
  <meta name="keywords" content="connexion, EduLMD, LMD, académique">

  <!-- Favicons -->
  <link href="{{ asset('favicon.ico') }}" rel="icon">
  <link href="{{ asset('/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Icônes RemixIcon -->
  <link href="{{ asset('/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">

  <!-- Feuille de style compilée -->
  <link href="{{ asset('/assets/css/edulmd.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/css/edulmd-theme.css') }}" rel="stylesheet">

  <!-- Meta CSRF -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Styles spécifiques page de login (animations fluides, design pro) -->
  <style>
    /* ========================================================================
       LOGIN PAGE — Design pro, animations séquentielles, thème respecté
       ======================================================================== */

    :root {
      --login-radius: 1.25rem;
      --login-shadow: 0 20px 50px -12px rgba(0, 0, 0, .35);
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--ed-fond, #f7f7f9);
      color: var(--ed-texte-principal, #212529);
      font-family: inherit;
    }

    /* Conteneur principal */
    .login-wrapper {
      width: 100%;
      max-width: 1120px;
      height: 88vh;
      display: grid;
      grid-template-columns: 1fr 520px;
      gap: 0;
      background: var(--ed-surface-1, #ffffff);
      border-radius: var(--login-radius);
      overflow: hidden;
      box-shadow: var(--login-shadow);
      position: relative;
      animation: loginWrapperIn 1s cubic-bezier(.23, 1, .32, 1) forwards;
    }

    /* Mode sombre */
    html[data-theme="dark"] .login-wrapper {
      background: var(--ed-surface-1);
      color: var(--ed-noir);
    }

    /* Panneau branding (gauche) */
    .login-brand {
      padding: 3rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: linear-gradient(135deg, var(--ed-bordeaux, #822a40) 0%, var(--ed-bordeaux-fonce, #6b2435) 100%);
      color: #fff;
      position: relative;
      overflow: hidden;
    }

    .login-brand::before {
      content: "";
      position: absolute;
      top: -50%; left: -50%;
      width: 200%; height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 60%);
      transform: rotate(45deg);
      pointer-events: none;
      animation: brandGlow 8s ease-in-out infinite alternate;
    }

    @keyframes brandGlow {
      0% { transform: rotate(45deg) translate(-10%, -10%); opacity: .6; }
      100% { transform: rotate(45deg) translate(10%, 10%); opacity: 1; }
    }

    .brand-badge {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .45rem .9rem;
      background: rgba(255,255,255,.12);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 2rem;
      font-size: .8rem;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      margin-bottom: 1.5rem;
      animation: badgeIn 1s .1s both;
    }

    .brand-badge i {
      font-size: .9rem;
    }

    .brand-content h1 {
      font-size: clamp(1.75rem, 4vw, 2.5rem);
      font-weight: 800;
      margin: 0 0 1rem;
      line-height: 1.15;
      letter-spacing: -.02em;
      animation: titleIn 1s .2s both;
    }

    .brand-content p {
      font-size: 1rem;
      line-height: 1.6;
      opacity: .92;
      margin: 0 0 2rem;
      max-width: 320px;
      animation: descIn 1s .3s both;
    }

    .brand-features {
      list-style: none;
      padding: 0;
      margin: 0;
      animation: featuresIn 1s .4s both;
    }

    .brand-features li {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin-bottom: .85rem;
      font-size: .95rem;
      opacity: 0;
      transform: translateY(10px);
      animation: featItemIn .5s ease forwards;
    }

    .brand-features li:nth-child(1) { animation-delay: .5s; }
    .brand-features li:nth-child(2) { animation-delay: .6s; }
    .brand-features li:nth-child(3) { animation-delay: .7s; }
    .brand-features li:nth-child(4) { animation-delay: .8s; }

    .brand-features .feat-icon {
      width: 2.1rem; height: 2.1rem;
      display: grid; place-items: center;
      background: rgba(255,255,255,.15);
      border-radius: .75rem;
      flex-shrink: 0;
      color: #fff;
      font-size: 1rem;
    }

    .brand-foot {
      position: absolute;
      bottom: 1.5rem;
      left: 2.5rem;
      font-size: .75rem;
      opacity: .7;
      animation: footIn 1s .9s both;
    }

    /* Panneau formulaire (droite) */
    .login-panel {
      padding: 3rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--ed-surface-1, #ffffff);
    }

    html[data-theme="dark"] .login-panel {
      background: var(--ed-surface-1);
    }

    .login-card {
      width: 100%;
      max-width: 380px;
      animation: cardIn 1s .15s both;
    }

    .login-header {
      text-align: center;
      margin-bottom: 2rem;
      animation: headerIn 1s .25s both;
    }

    .login-logo {
      width: 3.5rem; height: 3.5rem;
      margin: 0 auto 1rem;
      border-radius: .85rem;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0,0,0,.12);
      border: 2px solid var(--ed-bordeaux-clair, #fbedf1);
    }

    .login-logo img {
      width: 100%; height: 100%; object-fit: cover;
    }

    .login-header h2 {
      font-size: 1.75rem;
      font-weight: 800;
      margin: 0 0 .5rem;
      color: var(--ed-noir, #16171a);
      letter-spacing: -.02em;
    }

    .login-header .login-subtitle {
      color: var(--ed-texte-faible, #7b7e88);
      font-size: .95rem;
      margin: 0;
    }

    /* Alertes — uniquement si erreur réelle */
    .login-alert {
      margin-bottom: 1.5rem;
      padding: 1rem 1.25rem;
      border-radius: .85rem;
      border: none;
      display: flex;
      align-items: flex-start;
      gap: .85rem;
      font-size: .9rem;
      animation: alertIn .5s ease both;
    }

    .login-alert.alert-warning {
      background: #fffbeb;
      color: #92400e;
      border-left: 4px solid #d97706;
    }
    .login-alert.alert-danger {
      background: #fef2f2;
      color: #b91c1c;
      border-left: 4px solid #dc2626;
    }

    html[data-theme="dark"] .login-alert.alert-warning {
      background: #2b2110;
      color: #fcd34d;
      border-left-color: #fbbf24;
    }
    html[data-theme="dark"] .login-alert.alert-danger {
      background: #2c1416;
      color: #fca5a5;
      border-left-color: #f87171;
    }

    .login-alert i {
      font-size: 1.1rem;
      margin-top: .1rem;
      flex-shrink: 0;
    }

    .login-alert .btn-close {
      margin-left: auto;
      margin-top: -.15rem;
      opacity: .6;
    }
    .login-alert .btn-close:hover { opacity: 1; }

    /* Formulaire — validation classique, pas de JS bloquant */
    .login-form {
      animation: formIn .9s .25s both;
    }

    /* === Animations séquentielles "waouh" ===
       Chaque champ glisse depuis le bas, les uns après les autres,
       puis le bouton apparaît en dernier avec un effet de rebond. */
    .login-field {
      margin-bottom: 1.25rem;
      position: relative;
      opacity: 0;
      animation: fieldReveal .7s cubic-bezier(.175, .885, .32, 1.275) both;
    }

    .login-field:nth-child(1) { animation-delay: 0.2s; }
    .login-field:nth-child(2) { animation-delay: 0.45s; }
    .login-field:nth-child(3) { animation-delay: 0.7s; }

    .btn-login {
      opacity: 0;
      animation: btnReveal .8s cubic-bezier(.175, .885, .32, 1.275) both 1s;
    }

    .login-field label {
      display: flex;
      align-items: center;
      gap: .5rem;
      font-size: .85rem;
      font-weight: 700;
      color: var(--ed-noir, #16171a);
      margin-bottom: .5rem;
      letter-spacing: .01em;
    }

    .login-field label i {
      font-size: .9rem;
      color: var(--ed-bordeaux, #822a40);
    }

    .field-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .field-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--ed-texte-faible, #7b7e88);
      font-size: 1rem;
      z-index: 2;
      pointer-events: none;
      transition: color .2s;
    }

    .login-field input {
      width: 100%;
      padding: .85rem 1rem .85rem 3rem;
      font-size: 1rem;
      border: 2px solid var(--ed-ligne, #dee2e6);
      border-radius: .9rem;
      background: var(--ed-surface-2, #f7f7f9);
      color: var(--ed-noir, #16171a);
      transition: all .3s ease;
      box-sizing: border-box;
    }

    html[data-theme="dark"] .login-field input {
      background: var(--ed-surface-2);
      border-color: var(--ed-ligne);
      color: var(--ed-noir);
    }

    .login-field input::placeholder {
      color: var(--ed-texte-faible, #7b7e88);
      opacity: .7;
    }

    .login-field input:focus {
      outline: none;
      border-color: var(--ed-bordeaux, #822a40);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(130, 42, 64, .12);
    }

    html[data-theme="dark"] .login-field input:focus {
      background: var(--ed-surface-1);
      box-shadow: 0 0 0 4px rgba(130, 42, 64, .2);
    }

    .login-field input.is-invalid {
      border-color: var(--ed-erreur, #dc2626);
    }

    .login-field input.is-invalid:focus {
      box-shadow: 0 0 0 4px rgba(220, 38, 38, .15);
    }

    /* Messages d'erreur uniquement affichés quand il y a une erreur */
    .invalid-feedback {
      display: none;
      font-size: .78rem;
      color: var(--ed-erreur, #dc2626);
      margin-top: .4rem;
    }

    .is-invalid ~ .invalid-feedback,
    .was-validated .form-control:invalid ~ .invalid-feedback {
      display: block;
    }

    /* Toggle mot de passe */
    .toggle-password {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--ed-texte-faible, #7b7e88);
      cursor: pointer;
      padding: .25rem;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 3;
      transition: color .2s;
    }

    .toggle-password:hover { color: var(--ed-bordeaux, #822a40); }
    .toggle-password:focus { outline: 2px solid var(--ed-bordeaux, #822a40); border-radius: .4rem; }

    /* Se souvenir de moi */
    .login-remember {
      display: flex;
      align-items: center;
      gap: .6rem;
      margin: .5rem 0 1.75rem;
      font-size: .9rem;
      color: var(--ed-texte-faible, #7b7e88);
      cursor: pointer;
    }

    .login-remember input {
      width: 1.1rem; height: 1.1rem;
      accent-color: var(--ed-bordeaux, #822a40);
      margin: 0;
    }

    .login-remember a {
      margin-left: auto;
      color: var(--ed-bordeaux, #822a40);
      text-decoration: none;
      font-size: .85rem;
      font-weight: 600;
    }

    .login-remember a:hover { text-decoration: underline; }

    /* Bouton connexion — submit classique, pas de spinner bloquant */
    .btn-login {
      width: 100%;
      padding: 1rem;
      font-size: 1rem;
      font-weight: 700;
      border-radius: .9rem;
      border: none;
      background: var(--ed-bordeaux, #822a40);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .6rem;
      cursor: pointer;
      transition: all .3s ease;
      animation: btnIn 1s .75s both;
    }

    .btn-login:hover {
      background: var(--ed-bordeaux-fonce, #6b2435);
      transform: translateY(-2px);
      box-shadow: 0 12px 24px -8px rgba(130, 42, 64, .4);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .btn-login i { font-size: 1.05rem; }

    /* Pied de page */
    .login-foot {
      margin-top: 2rem;
      text-align: center;
      font-size: .75rem;
      color: var(--ed-texte-faible, #7b7e88);
      animation: footIn 1s 1s both;
    }

    /* ========================================================================
       Animations keyframes
       ======================================================================== */

    @keyframes loginWrapperIn {
      from { opacity: 0; transform: scale(.94); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes badgeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes titleIn {
      from { opacity: 0; transform: translateY(-12px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes descIn {
      from { opacity: 0; transform: translateY(-8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes featuresIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes featItemIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes footIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes cardIn {
      from { opacity: 0; transform: translateX(30px); }
      to { opacity: 1; transform: translateX(0); }
    }

    @keyframes headerIn {
      from { opacity: 0; transform: translateY(-15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes alertIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes formIn {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Champ qui glisse depuis le bas avec un léger rebond à l'arrivée */
    @keyframes fieldReveal {
      0%   { opacity: 0; transform: translateY(35px) scale(.96); }
      60%  { opacity: .8; transform: translateY(-4px) scale(1.02); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Bouton qui apparaît en dernier avec un effet de rebond prononcé */
    @keyframes btnReveal {
      0%   { opacity: 0; transform: translateY(35px) scale(.9); }
      50%  { opacity: .8; transform: translateY(-6px) scale(1.05); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ========================================================================
       Responsive
       ======================================================================== */

    @media (max-width: 900px) {
      .login-wrapper {
        grid-template-columns: 1fr;
        height: auto;
        min-height: 100vh;
        border-radius: 0;
        box-shadow: none;
        overflow: visible;
      }

      .login-brand {
        display: none;
      }

      .login-panel {
        padding: 2rem 1rem;
      }

      .login-card {
        max-width: 100%;
      }

      .login-header h2 { font-size: 1.5rem; }
    }

    @media (max-width: 480px) {
      .login-panel { padding: 1.5rem 1rem; }
      .login-header h2 { font-size: 1.35rem; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
      }
    }
  </style>

  <script>
    (function () {
      // Amorce le thème avant affichage (évite le flash)
      try {
        var e = JSON.parse(localStorage.getItem('edulmd.apparence') || '{}');
        var mode = e.mode || 'auto';
        var sombre = mode === 'dark' ||
          (mode === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.setAttribute('data-theme', sombre ? 'dark' : 'light');
        document.documentElement.setAttribute('data-accent', e.accent || 'bordeaux');
        document.documentElement.setAttribute('data-density', e.density || 'confortable');
      } catch (err) {
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.setAttribute('data-accent', 'bordeaux');
        document.documentElement.setAttribute('data-density', 'confortable');
      }
    })();
  </script>
</head>

<body>
  <main class="login-wrapper" role="main" aria-label="Connexion">

    <!-- Panneau marque (gauche, masqué sur mobile) -->
    <section class="login-brand" aria-hidden="true">
      <div class="brand-badge">
        <i class="ri-shield-check-line"></i>
        Accès sécurisé
      </div>
      <div class="brand-content">
        <h1>La gestion académique LMD,<br>simple et fiable.</h1>
        <p>Licence — Master — Doctorat : étudiants, inscriptions, notes, délibérations et documents officiels réunis dans une seule plateforme.</p>
        <ul class="brand-features">
          <li><span class="feat-icon"><i class="ri-team-line"></i></span> Suivi complet du parcours étudiant</li>
          <li><span class="feat-icon"><i class="ri-file-list-3-line"></i></span> Notes, relevés et délibérations</li>
          <li><span class="feat-icon"><i class="ri-file-text-line"></i></span> Documents officiels et impressions</li>
          <li><span class="feat-icon"><i class="ri-shield-check-line"></i></span> Accès sécurisé par rôles et permissions</li>
        </ul>
      </div>
      <div class="brand-foot">&copy; {{ now()->year }} LMD Web Solutions — Tous droits réservés.</div>
    </section>

    <!-- Panneau formulaire (droite) -->
    <section class="login-panel">
      <div class="login-card">
        <div class="login-header">
          <div class="login-logo">
            <img src="{{ asset('logo6.png') }}" alt="Logo EduLMD">
          </div>
          <h2>Bon retour</h2>
          <p class="login-subtitle">Connectez-vous à votre espace de travail</p>
        </div>

        @if(request('mustAuthenticate') !== null)
        <div class="login-alert alert-warning" role="alert" id="mA">
          <i class="ri-error-warning-line"></i>
          <span class="flex-grow-1">Vous devez d'abord vous connecter !</span>
          <button type="button" class="btn-close" aria-label="Fermer" onclick="document.getElementById('mA').remove()"></button>
        </div>
        @endif

        @if (session()->has('errorCredentials'))
        <div class="login-alert alert-danger" role="alert">
          <i class="ri-close-circle-line"></i>
          <span class="flex-grow-1">{{ session('errorCredentials') }}</span>
          <button type="button" class="btn-close" aria-label="Fermer"></button>
        </div>
        @endif

        <form class="login-form" method="POST" action="{{ route('login.post') }}">
          @csrf

          <div class="login-field">
            <label for="loginUsername"><i class="ri-user-line"></i> Nom d'utilisateur</label>
            <div class="field-wrap">
              <i class="ri-user-line field-icon"></i>
              <input type="text" name="email" class="form-control @error('email') is-invalid @enderror"
                id="loginUsername" required value="{{ old('email') }}"
                autocomplete="username" placeholder="ex: nom.prenom">
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="login-field">
            <label for="loginPassword"><i class="ri-lock-line"></i> Mot de passe</label>
            <div class="field-wrap">
              <i class="ri-lock-line field-icon"></i>
              <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                id="loginPassword" required autocomplete="current-password" placeholder="••••••••">
              <button type="button" class="toggle-password" id="togglePassword"
                aria-label="Afficher / masquer le mot de passe" title="Afficher / masquer">
                <i class="ri-eye-line"></i>
              </button>
              @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <label class="login-remember">
            <input type="checkbox" name="remember" value="true" id="rememberMe">
            <span>Se souvenir de moi</span>
            <a href="#" onclick="alert('Contactez l\'administrateur pour réinitialiser votre mot de passe.'); return false;">Mot de passe oublié ?</a>
          </label>

          <button class="btn btn-primary btn-login w-100" type="submit" id="btnLogin">
            <span class="btn-text">Se connecter</span>
          </button>
        </form>

        <div class="login-foot">
          Copyright &copy; {{ now()->year }} | <strong>LMD Web Solutions</strong> | Tous droits réservés.
        </div>
      </div>
    </section>

  </main>

  <script>
    (function () {
      // Afficher / masquer le mot de passe
      var toggle = document.getElementById('togglePassword');
      var input = document.getElementById('loginPassword');
      if (toggle && input) {
        toggle.addEventListener('click', function () {
          var show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          toggle.innerHTML = '<i class="ri-eye' + (show ? '-off' : '') + '-line"></i>';
          input.focus();
        });
      }

      // Focus automatique sur le premier champ après l'animation
      setTimeout(function () {
        var first = document.getElementById('loginUsername');
        if (first) first.focus();
      }, 700);
    })();
  </script>
</body>

</html>