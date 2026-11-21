/**
 * EduLMD — Paiement en un clic
 * ------------------------------------------------------------------
 * Encaissement sans quitter la page courante. S'ouvre depuis n'importe
 * quel élément portant l'attribut `data-ed-pay="MATRICULE"`.
 *
 *   <button data-ed-pay="20240001">Encaisser</button>
 *
 * Le modal liste les inscriptions non soldées, l'utilisateur choisit la
 * ligne, saisit un montant (prérempli au reste à payer), valide.
 */
(function () {
  'use strict';

  if (window.EduPay) return;

  var CFG = {
    situationUrl: (window.EDULMD_PAY_SITUATION || '/api/paiement-rapide') + '/',
    storeUrl: window.EDULMD_PAY_STORE || '/api/paiement-rapide',
    csrf: (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
  };

  var modal = null;
  var state = { matricule: null, inscriptions: [], selection: 0, busy: false };

  /* ─────────────────────────── Gabarit ─────────────────────────── */

  function construireModal() {
    var el = document.createElement('div');
    el.className = 'ed-pay-overlay';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-labelledby', 'edPayTitle');
    el.hidden = true;

    el.innerHTML =
      '<div class="ed-pay-panel">' +
        '<header class="ed-pay-head">' +
          '<div>' +
            '<h2 class="ed-pay-title" id="edPayTitle">Encaisser un paiement</h2>' +
            '<p class="ed-pay-sub" data-role="sous-titre"></p>' +
          '</div>' +
          '<button type="button" class="ed-pay-close" data-role="fermer" aria-label="Fermer">' +
            '<i class="ri-close-line"></i>' +
          '</button>' +
        '</header>' +
        '<div class="ed-pay-body" data-role="corps"></div>' +
        '<footer class="ed-pay-foot">' +
          '<div class="ed-pay-recap" data-role="recap"></div>' +
          '<div class="ed-pay-actions">' +
            '<button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" data-role="fermer">Annuler</button>' +
            '<button type="button" class="ed-btn ed-btn-primary ed-btn-sm" data-role="valider" disabled>' +
              '<i class="ri-check-line"></i><span>Encaisser</span>' +
            '</button>' +
          '</div>' +
        '</footer>' +
      '</div>';

    document.body.appendChild(el);
    return el;
  }

  function fmt(n) {
    return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)) + ' F';
  }

  /* ────────────────────────── Ouverture ─────────────────────────── */

  function ouvrir(matricule) {
    if (!modal) {
      modal = construireModal();
      brancherEvenements(modal);
    }

    state.matricule = matricule;
    state.inscriptions = [];
    state.selection = 0;

    /* On nettoie les messages de la fois précédente. */
    modal.querySelector('[data-role="corps"]').innerHTML =
      '<div class="ed-pay-loading"><i class="ri-loader-4-line"></i> Chargement des inscriptions…</div>';
    modal.querySelector('[data-role="sous-titre"]').textContent = 'Matricule ' + matricule;
    modal.querySelector('[data-role="recap"]').innerHTML = '';
    var btn = modal.querySelector('[data-role="valider"]');
    btn.disabled = true;
    btn.querySelector('span').textContent = 'Encaisser';

    modal.hidden = false;
    document.body.classList.add('ed-pay-open');

    charger(matricule);
  }

  function fermer() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('ed-pay-open');
    state.matricule = null;
  }

  /* ───────────────────────── Chargement ─────────────────────────── */

  function charger(matricule) {
    fetch(CFG.situationUrl + encodeURIComponent(matricule), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        state.inscriptions = (data.inscriptions || []).filter(function (i) { return !i.solde; });
        rendre();
      })
      .catch(function () {
        modal.querySelector('[data-role="corps"]').innerHTML =
          '<div class="ed-pay-error">Impossible de charger la situation financière de cet étudiant.</div>';
      });
  }

  /* ─────────────────────────── Rendu ───────────────────────────── */

  function rendre() {
    var corps = modal.querySelector('[data-role="corps"]');

    if (!state.inscriptions.length) {
      corps.innerHTML =
        '<div class="ed-pay-vide">' +
          '<i class="ri-checkbox-circle-line"></i>' +
          '<p>Cet étudiant est à jour : aucun reste à payer.</p>' +
        '</div>';
      modal.querySelector('[data-role="valider"]').disabled = true;
      return;
    }

    if (state.selection >= state.inscriptions.length) state.selection = 0;
    var ins = state.inscriptions[state.selection];

    var lignes = state.inscriptions.map(function (i, idx) {
      return '' +
        '<button type="button" class="ed-pay-ligne' + (idx === state.selection ? ' on' : '') + '" data-idx="' + idx + '">' +
          '<span class="ed-pay-ligne-titre">' + (i.anneeEtude || '—') + ' · ' + (i.anneeAcad || '—') + '</span>' +
          '<span class="ed-pay-ligne-meta">' + fmt(i.paye) + ' payés sur ' + fmt(i.frais) + '</span>' +
          '<span class="ed-pay-ligne-reste">' + fmt(i.reste) + '</span>' +
        '</button>';
    }).join('');

    corps.innerHTML =
      '<div class="ed-pay-lignes">' + lignes + '</div>' +
      '<div class="ed-pay-form">' +
        '<div class="ed-pay-champ">' +
          '<label for="edPayMontant">Montant à encaisser</label>' +
          '<div class="ed-pay-input">' +
            '<input type="number" id="edPayMontant" name="edPayMontant" min="1" max="' + ins.reste + '" step="1" value="' + ins.reste + '">' +
            '<span>F</span>' +
          '</div>' +
        '</div>' +
        '<div class="ed-pay-champ">' +
          '<label for="edPayMode">Mode de paiement</label>' +
          '<select id="edPayMode" name="edPayMode">' +
            '<option>Espèces</option>' +
            '<option>Chèque</option>' +
            '<option>Virement</option>' +
            '<option>Mobile Money</option>' +
          '</select>' +
        '</div>' +
        '<div class="ed-pay-champ">' +
          '<label for="edPayDate">Date</label>' +
          '<input type="date" id="edPayDate" name="edPayDate" value="' + new Date().toISOString().slice(0, 10) + '">' +
        '</div>' +
      '</div>' +
      '<div class="ed-pay-note" data-role="note"></div>';

    /* Sélection d'une autre inscription */
    corps.querySelectorAll('.ed-pay-ligne').forEach(function (b) {
      b.addEventListener('click', function () {
        state.selection = parseInt(b.getAttribute('data-idx'), 10) || 0;
        rendre();
      });
    });

    var input = corps.querySelector('#edPayMontant');
    var btnValider = modal.querySelector('[data-role="valider"]');

    function majRecap() {
      var v = parseInt(input.value, 10) || 0;
      var reste = ins.reste;
      var apres = Math.max(0, reste - v);
      var note = modal.querySelector('[data-role="note"]');

      if (v <= 0) {
        btnValider.disabled = true;
        note.className = 'ed-pay-note bad';
        note.textContent = 'Saisissez un montant supérieur à zéro.';
      } else if (v > reste) {
        btnValider.disabled = true;
        note.className = 'ed-pay-note bad';
        note.textContent = 'Maximum encaissable : ' + fmt(reste) + '.';
      } else {
        btnValider.disabled = false;
        note.className = 'ed-pay-note' + (apres === 0 ? ' ok' : '');
        note.textContent = apres === 0
          ? 'Solder le compte : reste 0 F après ce paiement.'
          : 'Reste après paiement : ' + fmt(apres) + '.';
      }

      modal.querySelector('[data-role="recap"]').innerHTML =
        '<span>Reste actuel <b>' + fmt(reste) + '</b></span>' +
        '<span>Tranche n°<b>' + ((ins.tranches || 0) + 1) + '</b></span>';
    }

    input.addEventListener('input', majRecap);
    majRecap();
    input.focus();
    input.select();
  }

  /* ───────────────────────── Validation ────────────────────────── */

  function valider() {
    if (state.busy) return;
    var ins = state.inscriptions[state.selection];
    if (!ins) return;

    var montant = parseInt((modal.querySelector('#edPayMontant') || {}).value, 10) || 0;
    var mode = (modal.querySelector('#edPayMode') || {}).value || 'Espèces';
    var date = (modal.querySelector('#edPayDate') || {}).value || '';

    var btn = modal.querySelector('[data-role="valider"]');
    state.busy = true;
    btn.disabled = true;
    btn.querySelector('span').textContent = 'Encaissement…';

    fetch(CFG.storeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CFG.csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        matricule: state.matricule,
        idAnnee: ins.idAnnee,
        idFiliere: ins.idFiliere,
        montant: montant,
        mode: mode,
        date: date
      })
    })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
      .then(function (res) {
        state.busy = false;
        btn.querySelector('span').textContent = 'Encaisser';

        if (!res.ok || !res.data.ok) {
          var note = modal.querySelector('[data-role="note"]');
          if (note) {
            note.className = 'ed-pay-note bad';
            note.textContent = res.data.message || 'Le paiement a échoué.';
          }
          btn.disabled = false;
          return;
        }

        /* Succès : on rafraîchit la situation pour refléter le nouveau reste. */
        afficherSucces(res.data);
      })
      .catch(function () {
        state.busy = false;
        btn.disabled = false;
        btn.querySelector('span').textContent = 'Encaisser';
        var note = modal.querySelector('[data-role="note"]');
        if (note) {
          note.className = 'ed-pay-note bad';
          note.textContent = 'Erreur réseau. Le paiement n\'a pas été enregistré.';
        }
      });
  }

  function afficherSucces(data) {
    var corps = modal.querySelector('[data-role="corps"]');
    corps.innerHTML =
      '<div class="ed-pay-succes">' +
        '<i class="ri-checkbox-circle-fill"></i>' +
        '<h3>' + data.message + '</h3>' +
        '<p>' + (data.solde
          ? 'Le compte est désormais soldé.'
          : 'Nouveau reste à payer : <strong>' + fmt(data.reste) + '</strong>.') + '</p>' +
        '<div class="ed-pay-succes-actions">' +
          '<button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" data-role="fermer">Fermer</button>' +
          '<button type="button" class="ed-btn ed-btn-primary ed-btn-sm" data-role="encore">Encaisser encore</button>' +
        '</div>' +
      '</div>';

    modal.querySelector('[data-role="valider"]').disabled = true;
    modal.querySelector('[data-role="recap"]').innerHTML = '';

    corps.querySelector('[data-role="fermer"]').addEventListener('click', fermer);
    corps.querySelector('[data-role="encore"]').addEventListener('click', function () {
      charger(state.matricule);
    });

    /* Prévient la page hôte qu'un paiement a eu lieu (rafraîchissement
       éventuel des totaux). */
    document.dispatchEvent(new CustomEvent('ed:payment', { detail: data }));
  }

  /* ───────────────────────── Événements ────────────────────────── */

  function brancherEvenements(el) {
    el.addEventListener('click', function (e) {
      var cible = e.target.closest('[data-role="fermer"]');
      if (cible) { fermer(); return; }
      if (e.target.closest('[data-role="valider"]')) { valider(); return; }
      if (e.target === el) fermer(); // clic sur l'arrière-plan
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal && !modal.hidden) {
        e.preventDefault();
        fermer();
      }
    });
  }

  /* Ouverture depuis n'importe quelle page (délégation globale). */
  document.addEventListener('click', function (e) {
    var declencheur = e.target.closest('[data-ed-pay]');
    if (!declencheur) return;
    e.preventDefault();
    var m = declencheur.getAttribute('data-ed-pay');
    if (m) ouvrir(m);
  });

  window.EduPay = { open: ouvrir, close: fermer };

  /* Support du paramètre ?matricule=…&encaisser=1 sur les pages listes. */
  document.addEventListener('DOMContentLoaded', function () {
    var p = new URLSearchParams(window.location.search);
    if (p.get('encaisser') === '1' && p.get('matricule')) {
      ouvrir(p.get('matricule'));
    }
  });
})();
