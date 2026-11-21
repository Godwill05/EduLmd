/**
 * EduLMD — Boîte à outils de raccourcis clavier.
 * ----------------------------------------------------------------------
 * Aucune nouvelle fonctionnalité métier : uniquement des chemins plus
 * courts vers ce qui existe déjà.
 *
 *   1. Palette de commandes (Ctrl/⌘ + K)
 *      Étudiants (recherche serveur) **et** navigation dans tout le menu.
 *      Remplace l'ancien usage limité à la recherche d'étudiants.
 *
 *   2. ?    Aide-mémoire des raccourcis.
 *   3. g puis une lettre   Navigation instantanée (« g e » → Étudiants).
 *   4. /    Focus sur le premier champ de recherche de la page.
 *   5. Alt + N   Ouvre le formulaire d'ajout de la page courante.
 *   6. Ctrl/⌘ + S   Enregistre le formulaire visible (submit natif).
 *   7. Alt + ←/→    Historique précédent / suivant.
 *   8. Échap    Ferme l'élément ouvert (modale, palette, aide).
 *
 * Toutes les touches sont ignorées quand l'utilisateur écrit dans un champ,
 * sauf Échap et Ctrl+K.
 *
 * Contrat HTML :
 *   - le menu latéral (#sidebar-nav) alimente la navigation ;
 *   - les formulaires sont détectés automatiquement (method != GET) ;
 *   - un bouton d'ajout est cherché via [data-ed-add] ou le premier lien
 *     « …/create » de la page.
 */
(function () {
  'use strict';

  if (window.EduRaccourcis) return;

  /* ─────────────────────── Utilitaires ─────────────────────── */

  function qs(sel, racine) { return (racine || document).querySelector(sel); }
  function qsa(sel, racine) {
    return Array.prototype.slice.call((racine || document).querySelectorAll(sel));
  }

  function dansUnChamp(cible) {
    if (!cible) return false;
    var t = (cible.tagName || '').toLowerCase();
    return t === 'input' || t === 'textarea' || t === 'select' || cible.isContentEditable;
  }

  function echapper(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ─────────────────── Navigation lue dans le menu ─────────────────── */

  /**
   * Construit la liste des destinations depuis le menu latéral réel :
   * le raccourci reste synchronisé même si le menu évolue.
   */
  function destinations() {
    var vues = {};
    var sortie = [];

    qsa('#sidebar-nav a[href]').forEach(function (a) {
      var href = a.getAttribute('href') || '';
      if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
      if (vues[href]) return;
      vues[href] = true;

      var libelle = (a.textContent || '').replace(/\s+/g, ' ').trim();
      if (!libelle) return;

      sortie.push({
        titre: libelle,
        url: href,
        icone: (qs('i', a) || {}).className || 'ri-arrow-right-line',
        section: sectionDe(a),
      });
    });

    return sortie;
  }

  function sectionDe(lien) {
    var ul = lien.closest('ul.nav-content');
    if (!ul) return 'Général';
    var precedent = ul.previousElementSibling;
    if (precedent && precedent.classList.contains('nav-link')) {
      return (precedent.textContent || '').replace(/\s+/g, ' ').trim();
    }
    return 'Général';
  }

  /* ─────────────────────── Palette de commandes ─────────────────────── */

  var palette = null;
  var etat = { items: [], actif: 0, filtre: '', requeteSeq: 0 };

  function construirePalette() {
    var el = document.createElement('div');
    el.className = 'ed-cmdk-overlay';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-label', 'Palette de commandes');
    el.hidden = true;

    el.innerHTML =
      '<div class="ed-cmdk-panel">' +
        '<div class="ed-cmdk-head">' +
          '<i class="ri-search-line"></i>' +
          '<input type="text" id="edCmdkInput2" placeholder="Étudiant, écran, action…" ' +
                 'autocomplete="off" spellcheck="false" aria-label="Rechercher">' +
          '<kbd>Échap</kbd>' +
        '</div>' +
        '<div class="ed-cmdk-list" id="edCmdkList2" role="listbox"></div>' +
        '<div class="ed-cmdk-foot">' +
          '<span><kbd>↑</kbd><kbd>↓</kbd> naviguer</span>' +
          '<span><kbd>Entrée</kbd> ouvrir</span>' +
          '<span class="ms-auto"><span id="edCmdkCount2"></span></span>' +
        '</div>' +
      '</div>';

    document.body.appendChild(el);
    return el;
  }

  function ouvrirPalette(premierMot) {
    if (!palette) {
      palette = construirePalette();
      brancherPalette(palette);
    }
    palette.hidden = false;
    document.body.classList.add('ed-cmdk-open');

    var champ = palette.querySelector('#edCmdkInput2');
    champ.value = premierMot || '';
    champ.focus();
    champ.setSelectionRange(champ.value.length, champ.value.length);

    rechercher(champ.value);
  }

  function fermerPalette() {
    if (!palette || palette.hidden) return;
    palette.hidden = true;
    document.body.classList.remove('ed-cmdk-open');
  }

  function brancherPalette(el) {
    var champ = el.querySelector('#edCmdkInput2');

    champ.addEventListener('input', function () { rechercher(champ.value); });

    champ.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); deplacer(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); deplacer(-1); }
      else if (e.key === 'Enter') { e.preventDefault(); executer(); }
      else if (e.key === 'Escape') { e.preventDefault(); fermerPalette(); }
    });

    el.addEventListener('click', function (e) {
      if (e.target === el) { fermerPalette(); return; }
      var ligne = e.target.closest('[data-idx]');
      if (ligne) {
        etat.actif = parseInt(ligne.getAttribute('data-idx'), 10);
        executer();
      }
    });
  }

  function rechercher(valeur) {
    etat.filtre = (valeur || '').trim();
    var q = etat.filtre.toLowerCase();
    var items = [];

    /* 1. destinations du menu */
    destinations().forEach(function (d) {
      if (!q || d.titre.toLowerCase().indexOf(q) !== -1) {
        items.push({
          type: 'page', titre: d.titre, url: d.url,
          icone: d.icone, contexte: d.section,
        });
      }
    });

    /* 2. actions de la page courante */
    actionsPage().forEach(function (a) {
      if (!q || a.titre.toLowerCase().indexOf(q) !== -1) items.push(a);
    });

    etat.items = items;
    etat.actif = 0;
    rendrePalette();
    chercherEtudiants(etat.filtre);
  }

  /** Recherche serveur d'étudiants, lancée en parallèle des résultats locaux. */
  function chercherEtudiants(q) {
    var liste = palette.querySelector('#edCmdkList2');
    if (!q || q.length < 2 || !window.EDULMD_SEARCH_URL || window.EDULMD_SEARCH_URL === '#') {
      return;
    }

    var seq = ++etat.requeteSeq;
    var url = window.EDULMD_SEARCH_URL + '?q=' + encodeURIComponent(q);

    fetch(url, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then(function (r) { return r.ok ? r.json() : { results: [] }; })
      .then(function (data) {
        if (seq !== etat.requeteSeq) return;          /* réponse obsolète */
        var etudiants = (data.results || []).map(function (e) {
          return {
            type: 'etudiant',
            titre: e.nom,
            contexte: [e.matricule, e.niveau, e.annee].filter(Boolean).join(' · '),
            url: e.actions.fiche,
            icone: 'ri-user-line',
            initiales: e.initiales,
            reste: e.reste,
          };
        });
        if (!etudiants.length) return;

        etat.items = etudiants.concat(etat.items);
        etat.actif = 0;
        rendrePalette();
      })
      .catch(function () { /* la recherche locale reste utilisable */ });
  }

  function actionsPage() {
    var liste = [];

    /* Encaisser / prévisions / engagement présents sur la fiche étudiant */
    var encaisser = qs('[data-ed-pay]');
    if (encaisser) {
      liste.push({
        type: 'action', titre: 'Encaisser un paiement',
        contexte: 'action de la fiche', icone: 'ri-hand-coin-line',
        executer: function () { encaisser.click(); },
      });
    }

    var prevision = qs('[data-ed-echeancier]');
    if (prevision) {
      liste.push({
        type: 'action', titre: 'Gérer les prévisions de paiement',
        contexte: 'action de la fiche', icone: 'ri-calendar-check-line',
        executer: function () { prevision.click(); },
      });
    }

    var impression = qs('a[title*="engagement"]');
    if (impression) {
      liste.push({
        type: 'action', titre: "Imprimer la fiche d'engagement",
        contexte: 'action de la fiche', icone: 'ri-file-paper-2-line',
        executer: function () { window.open(impression.href, '_blank', 'noopener'); },
      });
    }

    /* Lien d'ajout de la page courante */
    var ajout = qs('[data-ed-add]') || qs('a[href$="/create"]');
    if (ajout) {
      liste.push({
        type: 'action', titre: ajout.getAttribute('data-ed-add') || 'Ajouter un élément',
        contexte: 'action de la page', icone: 'ri-add-line',
        executer: function () { window.location.href = ajout.href; },
      });
    }

    /* Navigation retour / accueil, toujours disponible */
    liste.push({
      type: 'action', titre: 'Retour à l\'accueil', contexte: 'navigation',
      icone: 'ri-home-4-line', executer: function () { window.location.href = '/home'; },
    });

    return liste;
  }

  function rendrePalette() {
    var liste = palette.querySelector('#edCmdkList2');
    var compteur = palette.querySelector('#edCmdkCount2');

    if (!etat.items.length) {
      liste.innerHTML = '<div class="ed-cmdk-empty">' +
        '<i class="ri-search-eye-line"></i>' +
        '<p>Aucun résultat pour « ' + echapper(etat.filtre) + ' ».</p>' +
        '<span class="ed-cmdk-tip">Essayez un nom d\'étudiant ou un écran du menu.</span>' +
        '</div>';
      if (compteur) compteur.textContent = '0 résultat';
      return;
    }

    liste.innerHTML = etat.items.map(function (item, i) {
      var actif = i === etat.actif ? ' is-active' : '';
      var badge = item.type === 'etudiant'
        ? '<span class="ed-cmdk-type">étudiant</span>'
        : (item.type === 'action' ? '<span class="ed-cmdk-type act">action</span>' : '');
      var icone = item.initiales
        ? '<span class="ed-cmdk-avatar">' + echapper(item.initiales) + '</span>'
        : '<i class="' + echapper(item.icone) + '"></i>';

      return '<div class="ed-cmdk-item' + actif + '" data-idx="' + i + '" role="option">' +
        icone +
        '<span class="ed-cmdk-txt">' +
          '<span class="ed-cmdk-title">' + echapper(item.titre) + '</span>' +
          (item.contexte ? '<span class="ed-cmdk-sub">' + echapper(item.contexte) + '</span>' : '') +
        '</span>' + badge +
      '</div>';
    }).join('');

    if (compteur) {
      var n = etat.items.filter(function (x) { return x.type === 'etudiant'; }).length;
      compteur.textContent = etat.items.length + ' résultat(s)' + (n ? ' · ' + n + ' étudiant(s)' : '');
    }
  }

  function deplacer(pas) {
    if (!etat.items.length) return;
    etat.actif = (etat.actif + pas + etat.items.length) % etat.items.length;
    rendrePalette();

    var ligne = palette.querySelector('.ed-cmdk-item.is-active');
    if (ligne) ligne.scrollIntoView({ block: 'nearest' });
  }

  function executer() {
    var item = etat.items[etat.actif];
    if (!item) return;
    fermerPalette();

    if (item.executer) item.executer();
    else if (item.url) window.location.href = item.url;
  }

  /* ─────────────────────── Aide-mémoire « ? » ─────────────────────── */

  var RACCOURCIS = [
    ['Ctrl / ⌘ + K', 'Palette de commandes (étudiants, écrans, actions)'],
    ['g puis une lettre', 'Aller directement à un écran (g e → Étudiants)'],
    ['/', 'Aller au champ de recherche de la page'],
    ['Alt + N', "Ouvrir le formulaire d'ajout"],
    ['Ctrl / ⌘ + S', 'Enregistrer le formulaire en cours'],
    ['Alt + ← / →', 'Page précédente / suivante'],
    ['?', 'Afficher cette aide'],
    ['Échap', 'Fermer la fenêtre ouverte'],
  ];

  var aide = null;

  function basculerAide() {
    if (!aide) {
      aide = document.createElement('div');
      aide.className = 'ed-keys-overlay';
      aide.hidden = true;
      aide.innerHTML =
        '<div class="ed-keys-panel" role="dialog" aria-label="Raccourcis clavier">' +
          '<header class="ed-keys-head">' +
            '<h2>Raccourcis clavier</h2>' +
            '<button type="button" class="ed-keys-close" aria-label="Fermer">' +
              '<i class="ri-close-line"></i></button>' +
          '</header>' +
          '<ul class="ed-keys-list">' +
            RACCOURCIS.map(function (r) {
              return '<li><span class="ed-keys-combo">' + r[0] + '</span>' +
                     '<span class="ed-keys-desc">' + r[1] + '</span></li>';
            }).join('') +
          '</ul>' +
        '</div>';
      document.body.appendChild(aide);
      aide.addEventListener('click', function (e) {
        if (e.target === aide || e.target.closest('.ed-keys-close')) aide.hidden = true;
      });
    }
    aide.hidden = !aide.hidden;
  }

  /* ─────────────────────── Navigation « g » ─────────────────────── */

  var RACCOURCIS_G = {
    h: { mot: 'accueil', defaut: '/home' },
    e: { mot: 'étudiant', defaut: null },
    i: { mot: 'inscription', defaut: null },
    p: { mot: 'paiement', defaut: null },
    f: { mot: 'filière', defaut: null },
    u: { mot: 'ue', defaut: null },
    q: { mot: 'qualité', defaut: null },
    j: { mot: 'journal', defaut: null },
  };

  var gArme = false;
  var gMinuteur = null;

  function allerA(lettre) {
    var cible = RACCOURCIS_G[lettre];
    if (!cible) return;

    var trouves = destinations().filter(function (d) {
      return d.titre.toLowerCase().indexOf(cible.mot) !== -1;
    });

    if (trouves.length) {
      window.location.href = trouves[0].url;
    } else if (cible.defaut) {
      window.location.href = cible.defaut;
    }
  }

  /* ─────────────────────── Raccourcis globaux ─────────────────────── */

  document.addEventListener('keydown', function (e) {
    var modif = e.ctrlKey || e.metaKey;

    /* Ctrl / ⌘ + K — palette (fonctionne même dans un champ) */
    if (modif && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      if (palette && !palette.hidden) fermerPalette();
      else ouvrirPalette();
      return;
    }

    /* Ctrl / ⌘ + S — enregistrer le formulaire visible */
    if (modif && (e.key === 's' || e.key === 'S')) {
      var form = formulaireActif();
      if (form) {
        e.preventDefault();
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      }
      return;
    }

    /* Alt + ← / → — historique */
    if (e.altKey && e.key === 'ArrowLeft') { e.preventDefault(); history.back(); return; }
    if (e.altKey && e.key === 'ArrowRight') { e.preventDefault(); history.forward(); return; }

    /* Alt + N — formulaire d'ajout */
    if (e.altKey && (e.key === 'n' || e.key === 'N')) {
      e.preventDefault();
      var ajout = qs('[data-ed-add]') || qs('a[href$="/create"]');
      if (ajout) window.location.href = ajout.href;
      return;
    }

    if (dansUnChamp(e.target)) {
      /* Échap dans la palette est déjà géré par le champ lui-même */
      return;
    }

    /* ? — aide */
    if (e.key === '?') { e.preventDefault(); basculerAide(); return; }

    /* / — champ de recherche */
    if (e.key === '/') {
      var champ = qs('input[type="search"], input[name="recherche"], #edSearchTrigger');
      if (champ) {
        e.preventDefault();
        champ.focus();
        if (typeof champ.select === 'function') champ.select();
      }
      return;
    }

    /* g puis une lettre */
    if (e.key === 'g' || e.key === 'G') {
      if (e.altKey || modif) return;
      gArme = true;
      window.clearTimeout(gMinuteur);
      gMinuteur = window.setTimeout(function () { gArme = false; }, 1200);
      return;
    }

    if (gArme) {
      gArme = false;
      window.clearTimeout(gMinuteur);
      allerA(e.key.toLowerCase());
      return;
    }

    /* Échap ferme l'élément le plus haut */
    if (e.key === 'Escape') {
      if (palette && !palette.hidden) { fermerPalette(); return; }
      if (aide && !aide.hidden) { aide.hidden = true; return; }
    }
  });

  /** Premier formulaire modifiant l'état (POST/PUT), visible. */
  function formulaireActif() {
    var formulaires = qsa('form').filter(function (f) {
      var methode = (f.getAttribute('method') || 'get').toLowerCase();
      if (methode === 'get') return false;
      var rect = f.getBoundingClientRect();
      return rect.width > 0 && rect.height > 0;
    });
    /* priorité au formulaire qui contient le focus */
    var focus = document.activeElement;
    for (var i = 0; i < formulaires.length; i++) {
      if (focus && formulaires[i].contains(focus)) return formulaires[i];
    }
    return formulaires[0] || null;
  }

  /* Déclencheurs : barre de recherche → palette, bouton « ? » → aide. */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-ed-keys]')) {
      e.preventDefault();
      basculerAide();
      return;
    }
    var declencheur = e.target.closest('#edSearchTrigger, [data-ed-cmdk]');
    if (!declencheur) return;
    e.preventDefault();
    ouvrirPalette();
  });

  window.EduRaccourcis = {
    ouvrir: ouvrirPalette,
    fermer: fermerPalette,
    aide: basculerAide,
    destinations: destinations,
  };
})();
