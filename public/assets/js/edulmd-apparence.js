/*! ==========================================================================
    EduLMD — Réglages d'apparence
    --------------------------------------------------------------------------
    Panneau de personnalisation : mode clair/sombre/auto, couleur d'accent,
    densité. Le choix est enregistré dans `localStorage` et appliqué
    immédiatement, sans rechargement.

    Le script d'amorçage (dans le <head>, avant le rendu) pose les attributs
    `data-theme`, `data-accent` et `data-density` sur <html> dès le premier
    octet. Ce fichier-ci ne fait que piloter le panneau.

    Pourquoi deux fichiers : si l'application du thème attendait ce script,
    chaque page s'afficherait en clair pendant ~100 ms avant de basculer en
    sombre. C'est le « flash of incorrect theme » — très visible la nuit.
   ========================================================================== */
(function () {
  'use strict';

  var CLE = 'edulmd.apparence';
  var racine = document.documentElement;

  var DEFAUT = { mode: 'auto', accent: 'bordeaux', density: 'confortable' };

  var ETIQUETTES = {
    mode: { auto: 'Automatique', light: 'Clair', dark: 'Sombre' },
    accent: {
      bordeaux: 'Bordeaux', bleu: 'Bleu', emeraude: 'Émeraude',
      violet: 'Violet', ambre: 'Ambre', ardoise: 'Ardoise'
    },
    density: { confortable: 'Confortable', compact: 'Compact' }
  };

  function lire() {
    try {
      var brut = window.localStorage.getItem(CLE);
      if (!brut) return Object.assign({}, DEFAUT);
      var obj = JSON.parse(brut);
      return {
        mode: obj.mode || DEFAUT.mode,
        accent: obj.accent || DEFAUT.accent,
        density: obj.density || DEFAUT.density
      };
    } catch (e) {
      return Object.assign({}, DEFAUT);
    }
  }

  function ecrire(etat) {
    try {
      window.localStorage.setItem(CLE, JSON.stringify(etat));
    } catch (e) {
      /* Navigation privée, quota plein : on continue sans mémoriser.
         Le thème reste appliqué pour la session en cours. */
    }
  }

  /* Le mode « auto » suit le système, y compris quand l'utilisateur change
     de réglage en cours de route (bascule automatique le soir). */
  var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function themeEffectif(mode) {
    if (mode === 'auto') {
      return media && media.matches ? 'dark' : 'light';
    }
    return mode;
  }

  function appliquer(etat, animer) {
    if (animer) {
      racine.classList.add('ed-theme-anim');
      window.setTimeout(function () { racine.classList.remove('ed-theme-anim'); }, 320);
    }

    racine.setAttribute('data-theme', themeEffectif(etat.mode));
    racine.setAttribute('data-theme-mode', etat.mode);
    racine.setAttribute('data-accent', etat.accent);
    racine.setAttribute('data-density', etat.density);

    /* Les graphiques lisent leurs couleurs dans les variables CSS au
       moment du rendu. On prévient qui les écoute. */
    document.dispatchEvent(new CustomEvent('edulmd:theme', { detail: etat }));
  }

  var etat = lire();

  function enregistrer(modif) {
    etat = Object.assign({}, etat, modif);
    ecrire(etat);
    appliquer(etat, true);
    rafraichirPanneau();
  }

  /* ── Panneau ──────────────────────────────────────────────────────────── */

  var panneau = null;
  var bouton = null;

  function construire() {
    panneau = document.createElement('div');
    panneau.className = 'ed-apparence';
    panneau.setAttribute('hidden', '');
    panneau.setAttribute('role', 'dialog');
    panneau.setAttribute('aria-modal', 'false');
    panneau.setAttribute('aria-label', 'Réglages d\'apparence');
    panneau.innerHTML = gabarit();
    document.body.appendChild(panneau);

    panneau.addEventListener('click', function (e) {
      var cible = e.target.closest('[data-apparence]');
      if (!cible) return;

      var cle = cible.getAttribute('data-apparence');
      var valeur = cible.getAttribute('data-valeur');

      if (cle === 'fermer') { fermer(); return; }
      if (cle === 'reinitialiser') {
        enregistrer(Object.assign({}, DEFAUT));
        return;
      }
      enregistrer({ [cle]: valeur });
    });

    document.addEventListener('click', function (e) {
      if (!panneau || panneau.hasAttribute('hidden')) return;
      if (panneau.contains(e.target)) return;
      if (bouton && bouton.contains(e.target)) return;
      fermer();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panneau && !panneau.hasAttribute('hidden')) fermer();
    });
  }

  function gabarit() {
    return [
      '<div class="ed-apparence-entete">',
      '  <div>',
      '    <div class="ed-apparence-titre">Apparence</div>',
      '    <div class="ed-apparence-sous">Votre choix est mémorisé sur cet appareil</div>',
      '  </div>',
      '  <button type="button" class="ed-apparence-x" data-apparence="fermer" aria-label="Fermer">',
      '    <i class="ri-close-line"></i>',
      '  </button>',
      '</div>',

      '<div class="ed-apparence-corps">',

      '  <div class="ed-apparence-bloc">',
      '    <div class="ed-apparence-libelle">Mode</div>',
      '    <div class="ed-apparence-segments" role="group" aria-label="Mode d\'affichage">',
      '      <button type="button" data-apparence="mode" data-valeur="light" data-mode="light">',
      '        <i class="ri-sun-line"></i><span>Clair</span>',
      '      </button>',
      '      <button type="button" data-apparence="mode" data-valeur="dark" data-mode="dark">',
      '        <i class="ri-moon-line"></i><span>Sombre</span>',
      '      </button>',
      '      <button type="button" data-apparence="mode" data-valeur="auto" data-mode="auto">',
      '        <i class="ri-contrast-2-line"></i><span>Auto</span>',
      '      </button>',
      '    </div>',
      '  </div>',

      '  <div class="ed-apparence-bloc">',
      '    <div class="ed-apparence-libelle">Couleur d\'accent</div>',
      '    <div class="ed-apparence-pastilles" role="group" aria-label="Couleur d\'accent">',
      '      <button type="button" data-apparence="accent" data-valeur="bordeaux" data-accent="bordeaux" title="Bordeaux"><span></span><em>Bordeaux</em></button>',
      '      <button type="button" data-apparence="accent" data-valeur="bleu" data-accent="bleu" title="Bleu"><span></span><em>Bleu</em></button>',
      '      <button type="button" data-apparence="accent" data-valeur="emeraude" data-accent="emeraude" title="Émeraude"><span></span><em>Émeraude</em></button>',
      '      <button type="button" data-apparence="accent" data-valeur="violet" data-accent="violet" title="Violet"><span></span><em>Violet</em></button>',
      '      <button type="button" data-apparence="accent" data-valeur="ambre" data-accent="ambre" title="Ambre"><span></span><em>Ambre</em></button>',
      '      <button type="button" data-apparence="accent" data-valeur="ardoise" data-accent="ardoise" title="Ardoise"><span></span><em>Ardoise</em></button>',
      '    </div>',
      '  </div>',

      '  <div class="ed-apparence-bloc">',
      '    <div class="ed-apparence-libelle">Densité</div>',
      '    <div class="ed-apparence-segments" role="group" aria-label="Densité">',
      '      <button type="button" data-apparence="density" data-valeur="confortable" data-density-btn="confortable">',
      '        <i class="ri-layout-row-line"></i><span>Confortable</span>',
      '      </button>',
      '      <button type="button" data-apparence="density" data-valeur="compact" data-density-btn="compact">',
      '        <i class="ri-layout-masonry-line"></i><span>Compact</span>',
      '      </button>',
      '    </div>',
      '  </div>',

      '</div>',

      '<div class="ed-apparence-pied">',
      '  <button type="button" class="ed-apparence-reset" data-apparence="reinitialiser">',
      '    <i class="ri-restart-line"></i>Rétablir les réglages par défaut',
      '  </button>',
      '</div>'
    ].join('');
  }

  function rafraichirPanneau() {
    if (!panneau) return;

    panneau.querySelectorAll('[data-mode]').forEach(function (b) {
      b.classList.toggle('is-on', b.getAttribute('data-mode') === etat.mode);
      b.setAttribute('aria-pressed', b.getAttribute('data-mode') === etat.mode ? 'true' : 'false');
    });

    panneau.querySelectorAll('[data-accent]').forEach(function (b) {
      b.classList.toggle('is-on', b.getAttribute('data-accent') === etat.accent);
      b.setAttribute('aria-pressed', b.getAttribute('data-accent') === etat.accent ? 'true' : 'false');
    });

    panneau.querySelectorAll('[data-density-btn]').forEach(function (b) {
      var on = b.getAttribute('data-density-btn') === etat.density;
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  function ouvrir() {
    if (!panneau) construire();
    rafraichirPanneau();
    panneau.removeAttribute('hidden');
    if (bouton) bouton.setAttribute('aria-expanded', 'true');
    /* Le panneau est en position fixe sous le bouton ; on le replace à
       chaque ouverture au cas où la fenêtre a été redimensionnée. */
    positionner();
  }

  function fermer() {
    if (!panneau) return;
    panneau.setAttribute('hidden', '');
    if (bouton) bouton.setAttribute('aria-expanded', 'false');
  }

  function positionner() {
    if (!panneau || !bouton || panneau.hasAttribute('hidden')) return;

    var r = bouton.getBoundingClientRect();
    var largeur = Math.min(320, window.innerWidth - 24);
    var gauche = Math.min(Math.max(12, r.right - largeur), window.innerWidth - largeur - 12);

    panneau.style.width = largeur + 'px';
    panneau.style.left = gauche + 'px';
    panneau.style.top = (r.bottom + 8) + 'px';
  }

  function basculer() {
    if (!panneau) construire();
    if (panneau.hasAttribute('hidden')) ouvrir();
    else fermer();
  }

  /* ── Démarrage ────────────────────────────────────────────────────────── */

  function demarrer() {
    /* L'amorçage en ligne a déjà posé les attributs ; on réapplique pour
       être sûr que l'état du panneau et celui du document concordent. */
    appliquer(etat, false);

    bouton = document.querySelector('[data-ed-apparence]');
    if (bouton) {
      bouton.setAttribute('aria-expanded', 'false');
      bouton.addEventListener('click', function (e) {
        e.preventDefault();
        basculer();
      });
    }

    window.addEventListener('resize', positionner);
    window.addEventListener('scroll', function () {
      if (panneau && !panneau.hasAttribute('hidden')) positionner();
    }, { passive: true });

    /* Le mode « auto » doit réagir si le système bascule pendant la
       session (coucher du soleil, réglage automatique). */
    if (media) {
      var suivre = function () {
        if (etat.mode === 'auto') appliquer(etat, true);
      };
      if (media.addEventListener) media.addEventListener('change', suivre);
      else if (media.addListener) media.addListener(suivre);
    }

    /* Raccourci : Maj+D bascule clair/sombre sans ouvrir le panneau. */
    document.addEventListener('keydown', function (e) {
      if (!e.shiftKey || e.altKey || e.ctrlKey || e.metaKey) return;
      var tag = (e.target.tagName || '').toLowerCase();
      if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
      if (e.key !== 'D' && e.key !== 'd') return;

      e.preventDefault();
      var actuel = themeEffectif(etat.mode);
      enregistrer({ mode: actuel === 'dark' ? 'light' : 'dark' });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarrer);
  } else {
    demarrer();
  }

  /* Exposé pour les scripts qui veulent connaître la couleur courante
     (les graphiques, typiquement). */
  window.EduLMDApparence = {
    etat: function () { return Object.assign({}, etat); },
    ouvrir: ouvrir,
    fermer: fermer,
    /* Couleur d'accent effective, lue sur le document — utile aux canvas. */
    accent: function () {
      return getComputedStyle(racine).getPropertyValue('--ed-bordeaux').trim() || '#822a40';
    },
    sombre: function () { return themeEffectif(etat.mode) === 'dark'; }
  };
})();
