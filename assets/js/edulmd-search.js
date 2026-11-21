/**
 * EduLMD — Palette de recherche globale (Ctrl+K / Cmd+K)
 * ------------------------------------------------------
 * Recherche un étudiant par nom, prénoms ou matricule, affiche une liste
 * de résultats navigable au clavier et propose les actions associées :
 * fiche étudiant, paiements, parcours.
 *
 * Accessibilité : rôle combobox, focus piégé, Échap pour fermer,
 * flèches haut/bas, Entrée pour valider.
 */
(function () {
  'use strict';

  var ENDPOINT = window.EDULMD_SEARCH_URL || '/api/recherche';
  var overlay, input, list, statusEl, hintEl;
  var results = [];
  var activeIndex = -1;
  var lastFocused = null;
  var controller = null;
  var debounceTimer = null;
  var isOpen = false;

  /* ───────────────────────── Construction du DOM ───────────────────────── */

  function build() {
    overlay = document.createElement('div');
    overlay.className = 'ed-cmdk';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Recherche globale');
    overlay.hidden = true;

    overlay.innerHTML =
      '<div class="ed-cmdk-panel" role="document">' +
        '<div class="ed-cmdk-inputrow">' +
          '<i class="ri-search-line ed-cmdk-icon" aria-hidden="true"></i>' +
          '<input type="text" class="ed-cmdk-input" id="edCmdkInput"' +
            ' placeholder="Rechercher un étudiant : nom, prénoms ou matricule…"' +
            ' autocomplete="off" spellcheck="false"' +
            ' role="combobox" aria-expanded="true" aria-controls="edCmdkList"' +
            ' aria-autocomplete="list" aria-label="Recherche d\'étudiant">' +
          '<kbd class="ed-cmdk-esc">Échap</kbd>' +
        '</div>' +
        '<div class="ed-cmdk-body" id="edCmdkList" role="listbox" aria-label="Résultats"></div>' +
        '<div class="ed-cmdk-foot">' +
          '<span><kbd>↑</kbd><kbd>↓</kbd> naviguer</span>' +
          '<span><kbd>↵</kbd> ouvrir la fiche</span>' +
          '<span class="ms-auto" id="edCmdkCount"></span>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    input = overlay.querySelector('#edCmdkInput');
    list = overlay.querySelector('#edCmdkList');
    statusEl = overlay.querySelector('#edCmdkCount');

    overlay.addEventListener('mousedown', function (e) {
      if (e.target === overlay) close();
    });
    input.addEventListener('input', onInput);
    input.addEventListener('keydown', onKeyDown);
    list.addEventListener('click', onListClick);
    list.addEventListener('mousemove', onListHover);
  }

  /* ───────────────────────── Ouverture / fermeture ───────────────────────── */

  function open() {
    if (isOpen) return;
    isOpen = true;
    lastFocused = document.activeElement;
    overlay.hidden = false;
    document.body.classList.add('ed-cmdk-open');
    input.value = '';
    results = [];
    activeIndex = -1;
    renderEmpty();
    window.setTimeout(function () { input.focus(); }, 30);
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    overlay.hidden = true;
    document.body.classList.remove('ed-cmdk-open');
    if (controller) { controller.abort(); controller = null; }
    if (lastFocused && typeof lastFocused.focus === 'function') {
      try { lastFocused.focus({ preventScroll: true }); } catch (e) { lastFocused.focus(); }
    }
  }

  /* ───────────────────────── Rendu ───────────────────────── */

  function renderEmpty() {
    var msg = input.value.trim().length < 2
      ? 'Tapez au moins 2 caractères pour lancer la recherche.'
      : 'Recherche…';
    list.innerHTML = '<div class="ed-cmdk-empty">' +
      '<i class="ri-search-eye-line"></i><p>' + msg + '</p></div>';
    if (statusEl) statusEl.textContent = '';
  }

  function renderNoResult(q) {
    list.innerHTML = '<div class="ed-cmdk-empty">' +
      '<i class="ri-user-search-line"></i>' +
      '<p>Aucun étudiant ne correspond à « ' + escapeHtml(q) + ' ».</p>' +
      '<span class="ed-cmdk-tip">Vérifiez l\'orthographe ou essayez le matricule.</span>' +
      '</div>';
    if (statusEl) statusEl.textContent = '0 résultat';
  }

  function renderResults() {
    if (!results.length) { renderNoResult(input.value.trim()); return; }

    var html = results.map(function (r, i) {
      var reste = r.reste > 0
        ? '<span class="ed-cmdk-badge ed-cmdk-badge-warn">Reste ' + formatMoney(r.reste) + '</span>'
        : '<span class="ed-cmdk-badge ed-cmdk-badge-ok">À jour</span>';

      var meta = [];
      if (r.niveau) meta.push(escapeHtml(r.niveau));
      if (r.annee) meta.push(escapeHtml(r.annee));
      if (r.paiements) meta.push(r.paiements + ' paiement(s)');

      return '<div class="ed-cmdk-item" role="option" id="edCmdkOpt' + i + '"' +
        ' aria-selected="false" data-index="' + i + '" tabindex="-1">' +
          '<span class="ed-cmdk-avatar">' + escapeHtml(r.initiales) + '</span>' +
          '<span class="ed-cmdk-main">' +
            '<span class="ed-cmdk-name">' + escapeHtml(r.nom) + '</span>' +
            '<span class="ed-cmdk-sub">' +
              '<span class="ed-cmdk-mat">' + escapeHtml(r.matricule) + '</span>' +
              (meta.length ? '<span class="ed-cmdk-dot">·</span>' + meta.join('<span class="ed-cmdk-dot">·</span>') : '') +
            '</span>' +
          '</span>' +
          '<span class="ed-cmdk-side">' + reste +
            '<span class="ed-cmdk-actions">' +
              '<a class="ed-cmdk-act" data-act="fiche" href="' + r.actions.fiche + '" title="Voir la fiche">' +
                '<i class="ri-user-line"></i><span>Fiche</span></a>' +
              '<a class="ed-cmdk-act" data-act="paiements" href="' + r.actions.paiements + '" title="Voir les paiements">' +
                '<i class="ri-hand-coin-line"></i><span>Paiements</span></a>' +
              '<a class="ed-cmdk-act" data-act="parcours" href="' + r.actions.parcours + '" title="Voir le parcours">' +
                '<i class="ri-signpost-line"></i><span>Parcours</span></a>' +
            '</span>' +
          '</span>' +
        '</div>';
    }).join('');

    list.innerHTML = html;
    if (statusEl) statusEl.textContent = results.length + ' résultat(s)';
    setActive(-1);
  }

  /* ───────────────────────── Sélection clavier ───────────────────────── */

  function setActive(i) {
    var items = list.querySelectorAll('.ed-cmdk-item');
    if (!items.length) return;
    if (activeIndex >= 0 && items[activeIndex]) {
      items[activeIndex].classList.remove('is-active');
      items[activeIndex].setAttribute('aria-selected', 'false');
    }
    activeIndex = i;
    if (i >= 0 && items[i]) {
      items[i].classList.add('is-active');
      items[i].setAttribute('aria-selected', 'true');
      items[i].scrollIntoView({ block: 'nearest' });
      input.setAttribute('aria-activedescendant', items[i].id);
    } else {
      input.removeAttribute('aria-activedescendant');
    }
  }

  function onKeyDown(e) {
    if (e.key === 'Escape') { e.preventDefault(); close(); return; }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!results.length) return;
      setActive(activeIndex >= results.length - 1 ? 0 : activeIndex + 1);
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (!results.length) return;
      setActive(activeIndex <= 0 ? results.length - 1 : activeIndex - 1);
      return;
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      if (activeIndex >= 0 && results[activeIndex]) {
        window.location.href = results[activeIndex].actions.fiche;
      }
      return;
    }
    if (e.key === 'Tab') {
      // On garde le focus dans la palette
      e.preventDefault();
    }
  }

  /* ───────────────────────── Souris ───────────────────────── */

  function onListHover(e) {
    var item = e.target.closest('.ed-cmdk-item');
    if (item) setActive(parseInt(item.getAttribute('data-index'), 10));
  }

  function onListClick(e) {
    var act = e.target.closest('.ed-cmdk-act');
    if (act) {
      // Laisse le navigateur suivre le lien (fiche / paiements / parcours)
      close();
      return;
    }
    var item = e.target.closest('.ed-cmdk-item');
    if (item) {
      var idx = parseInt(item.getAttribute('data-index'), 10);
      if (results[idx]) window.location.href = results[idx].actions.fiche;
    }
  }

  /* ───────────────────────── Requête réseau ───────────────────────── */

  function onInput() {
    var q = input.value.trim();
    window.clearTimeout(debounceTimer);

    if (q.length < 2) { results = []; renderEmpty(); return; }

    debounceTimer = window.setTimeout(function () {
      if (controller) controller.abort();
      controller = new AbortController();

      fetch(ENDPOINT + '?q=' + encodeURIComponent(q), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        signal: controller.signal,
        credentials: 'same-origin',
      })
        .then(function (res) {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then(function (data) {
          results = data.results || [];
          renderResults();
        })
        .catch(function (err) {
          if (err.name === 'AbortError') return;
          list.innerHTML = '<div class="ed-cmdk-empty">' +
            '<i class="ri-error-warning-line"></i>' +
            '<p>La recherche a échoué. Vérifiez votre connexion.</p></div>';
        });
    }, 220);
  }

  /* ───────────────────────── Utilitaires ───────────────────────── */

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function formatMoney(v) {
    try { return new Intl.NumberFormat('fr-FR').format(v) + ' F'; }
    catch (e) { return v + ' F'; }
  }

  /* ───────────────────────── Initialisation ───────────────────────── */

  function init() {
    if (document.querySelector('.ed-cmdk')) return;
    build();

    /* Ctrl/⌘+K est pris en charge par edulmd-raccourcis.js (palette unifiée :
       étudiants + tous les écrans du menu). Ce module ne garde que la
       recherche d'étudiants quand la palette n'est pas chargée, pour ne
       jamais laisser le raccourci sans effet. */
    if (!window.EduRaccourcis) {
      document.addEventListener('keydown', function (e) {
        var isK = (e.key === 'k' || e.key === 'K');
        if (isK && (e.ctrlKey || e.metaKey)) {
          e.preventDefault();
          isOpen ? close() : open();
        }
      });
    }

    // Déclencheur visible dans le header
    var trigger = window.EduRaccourcis ? null : document.getElementById('edSearchTrigger');
    if (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        open();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.EduSearch = { open: open, close: close };
})();
