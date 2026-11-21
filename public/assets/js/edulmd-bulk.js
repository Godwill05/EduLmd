/**
 * EduLMD — Actions groupées sur les listes.
 * ------------------------------------------------------------------
 * Activation : un tableau `<table data-ed-bulk>` (ou un conteneur
 * `[data-ed-bulk-scope]`) contenant des cases
 * `<input type="checkbox" data-ed-bulk-item="MATRICULE">`.
 *
 * Une barre flottante apparaît dès qu'une ligne est cochée :
 *   - changer le statut (si la page fournit window.EDULMD_STATUTS)
 *   - exporter la sélection en CSV
 *   - imprimer la sélection (liste d'émargement)
 */
(function () {
  'use strict';

  if (window.EduBulk) return;

  var CFG = {
    statutUrl: window.EDULMD_BULK_STATUT || '/api/actions-groupees/statut',
    exportUrl: window.EDULMD_BULK_EXPORT || '/api/actions-groupees/export',
    printUrl: window.EDULMD_BULK_PRINT || '/api/actions-groupees/impression',
    csrf: (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
  };

  /* Contexte facultatif (année / filière) fourni par la page. */
  var CONTEXTE = window.EDULMD_BULK_CONTEXTE || {};

  var barre = null;
  var selection = new Set();

  /* ───────────────────────── Détection ─────────────────────────── */

  function cases() {
    return Array.prototype.slice.call(
      document.querySelectorAll('[data-ed-bulk] input[data-ed-bulk-item], [data-ed-bulk-scope] input[data-ed-bulk-item]')
    );
  }

  function creerEnTete() {
    /* Ajoute une case « tout sélectionner » en tête de chaque tableau. */
    document.querySelectorAll('table[data-ed-bulk]').forEach(function (table) {
      var thead = table.querySelector('thead tr');
      if (!thead || thead.querySelector('[data-ed-bulk-all]')) return;

      var th = document.createElement('th');
      th.className = 'ed-bulk-col';
      th.style.width = '2.2rem';
      th.innerHTML = '<input type="checkbox" data-ed-bulk-all aria-label="Tout sélectionner">';
      thead.insertBefore(th, thead.firstChild);

      /* Autant de cellules que de lignes dans le corps. */
      table.querySelectorAll('tbody tr').forEach(function (tr) {
        var td = document.createElement('td');
        td.className = 'ed-bulk-col text-center';
        var mat = tr.getAttribute('data-ed-matricule') || '';
        td.innerHTML = '<input type="checkbox" data-ed-bulk-item value="' + mat + '" aria-label="Sélectionner">';
        tr.insertBefore(td, tr.firstChild);
      });
    });
  }

  /* ─────────────────────────── Barre ───────────────────────────── */

  function creerBarre() {
    var el = document.createElement('div');
    el.className = 'ed-bulk-bar';
    el.hidden = true;
    el.innerHTML =
      '<span class="ed-bulk-count"><b>0</b> sélectionné(s)</span>' +
      '<div class="ed-bulk-sep"></div>' +
      '<button type="button" class="ed-bulk-action" data-act="statut">' +
        '<i class="ri-toggle-line"></i><span>Changer le statut</span>' +
      '</button>' +
      '<button type="button" class="ed-bulk-action" data-act="export">' +
        '<i class="ri-file-excel-line"></i><span>Exporter (CSV)</span>' +
      '</button>' +
      '<button type="button" class="ed-bulk-action" data-act="print">' +
        '<i class="ri-printer-line"></i><span>Imprimer en lot</span>' +
      '</button>' +
      '<button type="button" class="ed-bulk-close" data-act="clear" aria-label="Vider la sélection">' +
        '<i class="ri-close-line"></i>' +
      '</button>' +
      '<div class="ed-bulk-statut" hidden>' +
        '<select aria-label="Nouveau statut"><option value="">— Choisir un statut —</option></select>' +
        '<button type="button" class="ed-btn ed-btn-primary ed-btn-sm" data-act="statut-ok">Appliquer</button>' +
      '</div>';
    document.body.appendChild(el);
    return el;
  }

  function rafraichir() {
    if (!barre) barre = creerBarre();

    var n = selection.size;
    barre.hidden = n === 0;
    barre.querySelector('.ed-bulk-count b').textContent = n;
    document.body.classList.toggle('ed-bulk-active', n > 0);

    /* Les actions qui nécessitent un statut connu sont désactivées sinon. */
    var bStatut = barre.querySelector('[data-act="statut"]');
    bStatut.disabled = !(window.EDULMD_STATUTS && window.EDULMD_STATUTS.length);
    if (bStatut.disabled) bStatut.title = 'Aucun statut disponible sur cette page';

    /* Cocher/décocher la case « tout » */
    var toutes = cases();
    document.querySelectorAll('[data-ed-bulk-all]').forEach(function (c) {
      c.checked = n > 0 && toutes.every(function (x) { return x.checked; });
      c.indeterminate = n > 0 && !c.checked;
    });

    /* Ligne en surbrillance */
    toutes.forEach(function (c) {
      var tr = c.closest('tr');
      if (tr) tr.classList.toggle('ed-bulk-row', c.checked);
    });
  }

  /* ───────────────────── Requêtes serveur ──────────────────────── */

  function poster(url, charge) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CFG.csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify(charge)
    }).then(function (r) {
      return r.json().then(function (d) { return { ok: r.ok, data: d }; });
    });
  }

  function toast(message, ton) {
    var t = document.createElement('div');
    t.className = 'ed-bulk-toast ' + (ton || 'ok');
    t.innerHTML = '<i class="ri-' + (ton === 'bad' ? 'error-warning-line' : 'checkbox-circle-line') + '"></i><span></span>';
    t.querySelector('span').textContent = message;
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('on'); });
    setTimeout(function () {
      t.classList.remove('on');
      setTimeout(function () { t.remove(); }, 250);
    }, 4200);
  }

  /* ───────────────────────── Actions ───────────────────────────── */

  function changerStatut() {
    var zone = barre.querySelector('.ed-bulk-statut');
    zone.hidden = !zone.hidden;
    if (zone.hidden) return;

    var sel = zone.querySelector('select');

    /* Remplissage paresseux, une seule fois. */
    if (sel.options.length <= 1 && window.EDULMD_STATUTS) {
      window.EDULMD_STATUTS.forEach(function (s) {
        var o = document.createElement('option');
        o.value = s.code;
        o.textContent = s.libelle;
        sel.appendChild(o);
      });
    }
    sel.focus();
  }

  function appliquerStatut() {
    var sel = barre.querySelector('.ed-bulk-statut select');
    if (!sel.value) { toast('Choisissez d\'abord un statut.', 'bad'); return; }

    var libelle = sel.options[sel.selectedIndex].textContent;
    var charge = {
      matricules: Array.from(selection),
      statut: sel.value
    };
    if (CONTEXTE.idAnnee) charge.idAnnee = CONTEXTE.idAnnee;
    if (CONTEXTE.idFiliere) charge.idFiliere = CONTEXTE.idFiliere;

    poster(CFG.statutUrl, charge)
      .then(function (res) {
        if (!res.ok || !res.data.ok) {
          toast(res.data.message || 'Le changement de statut a échoué.', 'bad');
          return;
        }
        toast(res.data.message);
        vider();

        /* On recharge pour refléter le nouveau statut dans la liste. */
        window.setTimeout(function () { window.location.reload(); }, 900);
      })
      .catch(function () { toast('Erreur réseau.', 'bad'); });
  }

  function exporter() {
    /* Le CSV est un flux : on passe par un formulaire POST pour que le
       navigateur déclenche le téléchargement (pas d'AJAX). */
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = CFG.exportUrl;
    f.style.display = 'none';

    f.appendChild(champ('_token', CFG.csrf));
    Array.from(selection).forEach(function (m) { f.appendChild(champ('matricules[]', m)); });

    document.body.appendChild(f);
    f.submit();
    setTimeout(function () { f.remove(); }, 1500);
    toast(selection.size + ' étudiant(s) exporté(s).');
  }

  function champ(nom, valeur) {
    var i = document.createElement('input');
    i.type = 'hidden';
    i.name = nom;
    i.value = valeur;
    return i;
  }

  function imprimer() {
    var charge = { matricules: Array.from(selection) };
    if (CONTEXTE.idAnnee) charge.idAnnee = CONTEXTE.idAnnee;

    poster(CFG.printUrl, charge)
      .then(function (res) {
        if (!res.ok || !res.data.ok) { toast('Impression impossible.', 'bad'); return; }
        ouvrirFenetreImpression(res.data);
      })
      .catch(function () { toast('Erreur réseau.', 'bad'); });
  }

  function ouvrirFenetreImpression(data) {
    var lignes = data.lignes.map(function (l, i) {
      return '<tr>' +
        '<td>' + (i + 1) + '</td>' +
        '<td>' + l.matricule + '</td>' +
        '<td>' + echapper(l.nom) + '</td>' +
        '<td>' + echapper(l.prenoms) + '</td>' +
        '<td>' + (l.sexe || '') + '</td>' +
        '<td>' + echapper(l.dateNais || '') + '</td>' +
        '<td>' + echapper(l.tel || '') + '</td>' +
        '<td class="sig"></td>' +
      '</tr>';
    }).join('');

    var html =
      '<!doctype html><html lang="fr"><head><meta charset="utf-8">' +
      '<title>' + echapper(data.titre) + '</title>' +
      '<style>' +
        '@page { size: A4; margin: 12mm; }' +
        'body { font-family: "Segoe UI", Arial, sans-serif; color: #000; font-size: 11pt; }' +
        'h1 { font-size: 15pt; margin: 0 0 2mm; }' +
        '.meta { font-size: 9pt; color: #444; margin-bottom: 6mm; }' +
        'table { width: 100%; border-collapse: collapse; }' +
        'th, td { border: 0.4pt solid #555; padding: 1.6mm 2mm; text-align: left; font-size: 9.5pt; }' +
        'th { background: #eee; font-weight: 700; text-transform: uppercase; font-size: 8pt; letter-spacing: .03em; }' +
        '.sig { width: 28mm; }' +
        'tbody tr:nth-child(even) td { background: #fafafa; }' +
        'footer { margin-top: 6mm; font-size: 8.5pt; color: #555; display: flex; justify-content: space-between; }' +
      '</style></head><body>' +
      '<h1>' + echapper(data.titre) + '</h1>' +
      '<div class="meta">' + data.total + ' étudiant(s) — généré le ' + echapper(data.genereLe) + '</div>' +
      '<table><thead><tr>' +
        '<th style="width:8mm">N°</th><th>Matricule</th><th>Nom</th><th>Prénoms</th>' +
        '<th>Sexe</th><th>Date de naissance</th><th>Téléphone</th><th>Signature</th>' +
      '</tr></thead><tbody>' + lignes + '</tbody></table>' +
      '<footer><span>EduLMD — LMD Web Solutions</span><span>Document généré automatiquement</span></footer>' +
      '</body></html>';

    var w = window.open('', '_blank');
    if (!w) { toast('Autorisez les fenêtres surgissantes pour imprimer.', 'bad'); return; }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(function () { w.print(); }, 350);
  }

  function echapper(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ───────────────────────── Sélection ─────────────────────────── */

  function vider() {
    selection.clear();
    cases().forEach(function (c) { c.checked = false; });
    if (barre) barre.querySelector('.ed-bulk-statut').hidden = true;
    rafraichir();
  }

  function brancher() {
    document.addEventListener('change', function (e) {
      var c = e.target;
      if (c.matches('[data-ed-bulk-item]')) {
        if (c.checked) selection.add(c.value);
        else selection.delete(c.value);
        rafraichir();
      }

      if (c.matches('[data-ed-bulk-all]')) {
        var table = c.closest('table');
        if (!table) return;
        table.querySelectorAll('[data-ed-bulk-item]').forEach(function (x) {
          x.checked = c.checked;
          if (c.checked) selection.add(x.value);
          else selection.delete(x.value);
        });
        rafraichir();
      }
    });

    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-act]');
      if (!b || !barre || !barre.contains(b)) return;

      var act = b.getAttribute('data-act');
      if (act === 'statut') changerStatut();
      else if (act === 'statut-ok') appliquerStatut();
      else if (act === 'export') exporter();
      else if (act === 'print') imprimer();
      else if (act === 'clear') vider();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && barre && !barre.hidden) vider();
    });
  }

  /* ───────────────────────── Démarrage ─────────────────────────── */

  document.addEventListener('DOMContentLoaded', function () {
    creerEnTete();
    brancher();
    rafraichir();
  });

  window.EduBulk = {
    refresh: rafraichir,
    selection: function () { return Array.from(selection); },
    clear: vider
  };
})();
