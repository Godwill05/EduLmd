/**
 * EduLMD — Échéancier / prévisions de paiement depuis la fiche étudiant.
 * ----------------------------------------------------------------------
 * Réutilise les endpoints existants du module Prévisions :
 *   GET  /previsions/etudiant-echeancier?matricule=…&anneeAcademique=…&anneeEtude=…
 *   POST /previsions/enregistrer-echeancier  { matricule, anneeAcademique, anneeEtude, tranches[] }
 *
 * Usage :
 *   <button data-ed-echeancier="MATRICULE" data-annee="3" data-filiere="L2">Prévisions</button>
 */
(function () {
  'use strict';

  if (window.EduEcheancier) return;

  /* Une URL « # » signale un aperçu statique (hors Laravel) : on retombe
     alors sur les chemins réels pour que les requêtes restent testables. */
  function urlOu(v, defaut) {
    return (!v || v.charAt(0) === '#') ? defaut : v;
  }

  var CFG = {
    lecture: urlOu(window.EDULMD_ECHEANCIER_URL, '/previsions/etudiant-echeancier'),
    ecriture: urlOu(window.EDULMD_ECHEANCIER_SAVE, '/previsions/enregistrer-echeancier'),
    csrf: (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
  };

  var modal = null;
  var state = { matricule: null, annee: null, filiere: null, tranches: [], data: null, busy: false };

  /* ─────────────────────────── Gabarit ─────────────────────────── */

  function construire() {
    var el = document.createElement('div');
    el.className = 'ed-ech-overlay';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-labelledby', 'edEchTitle');
    el.hidden = true;

    el.innerHTML =
      '<div class="ed-ech-panel">' +
        '<header class="ed-ech-head">' +
          '<div>' +
            '<h2 class="ed-ech-title" id="edEchTitle">Prévisions de paiement</h2>' +
            '<p class="ed-ech-sub" data-role="sous-titre"></p>' +
          '</div>' +
          '<button type="button" class="ed-ech-close" data-role="fermer" aria-label="Fermer">' +
            '<i class="ri-close-line"></i>' +
          '</button>' +
        '</header>' +
        '<div class="ed-ech-body" data-role="corps"></div>' +
        '<footer class="ed-ech-foot">' +
          '<div class="ed-ech-recap" data-role="recap"></div>' +
          '<div class="ed-ech-actions">' +
            '<button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" data-role="fermer">Annuler</button>' +
            '<button type="button" class="ed-btn ed-btn-primary ed-btn-sm" data-role="enregistrer" disabled>' +
              '<i class="ri-save-line"></i><span>Enregistrer l\'échéancier</span>' +
            '</button>' +
          '</div>' +
        '</footer>' +
      '</div>';

    document.body.appendChild(el);
    return el;
  }

  /* Formatage manuel : certaines build de Chromium/Node sont livrées sans
     les données ICU complètes et `Intl` y perd les séparateurs de milliers. */
  function fmt(n) {
    var v = Math.round(Number(n) || 0);
    var negatif = v < 0;
    var chiffres = String(Math.abs(v));
    var sortie = '';
    while (chiffres.length > 3) {
      sortie = ' ' + chiffres.slice(-3) + sortie;
      chiffres = chiffres.slice(0, -3);
    }
    return (negatif ? '-' : '') + chiffres + sortie + ' F';
  }

  function echapper(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ───────────────────────── Ouverture ─────────────────────────── */

  function ouvrir(matricule, annee, filiere) {
    if (!modal) {
      modal = construire();
      brancher(modal);
    }

    state.matricule = matricule;
    state.annee = annee;
    state.filiere = filiere;
    state.tranches = [];
    state.data = null;

    modal.querySelector('[data-role="corps"]').innerHTML =
      '<div class="ed-ech-loading"><i class="ri-loader-4-line"></i> Chargement de l\'échéancier…</div>';
    modal.querySelector('[data-role="recap"]').innerHTML = '';
    modal.querySelector('.ed-ech-foot').hidden = false;
    var b = modal.querySelector('[data-role="enregistrer"]');
    b.disabled = true;
    b.querySelector('span').textContent = 'Enregistrer l\'échéancier';

    modal.hidden = false;
    document.body.classList.add('ed-ech-open');

    charger();
  }

  function fermer() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('ed-ech-open');
  }

  function charger() {
    var url = CFG.lecture
      + '?matricule=' + encodeURIComponent(state.matricule)
      + '&anneeAcademique=' + encodeURIComponent(state.annee)
      + '&anneeEtude=' + encodeURIComponent(state.filiere);

    fetch(url, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        state.data = data;
        state.tranches = (data.tranches || []).map(function (t) {
          return {
            date: t.date,
            montant: Number(t.montant) || 0,
            paye: !!t.paye,
            reste: Number(t.reste) || 0
          };
        });
        rendre();
      })
      .catch(function () {
        modal.querySelector('[data-role="corps"]').innerHTML =
          '<div class="ed-ech-vide bad">Impossible de charger l\'échéancier de cet étudiant.</div>';
      });
  }

  /* ─────────────────────────── Rendu ───────────────────────────── */

  function rendre() {
    var d = state.data;
    var corps = modal.querySelector('[data-role="corps"]');

    modal.querySelector('[data-role="sous-titre"]').textContent =
      (d.nom_complet || state.matricule)
      + ' · ' + (d.filiere_libelle || state.filiere || '')
      + ' · ' + (d.annee_acad || '');

    var lignes = state.tranches.map(function (t, i) {
      return '' +
        '<div class="ed-ech-ligne" data-idx="' + i + '">' +
          '<span class="ed-ech-num">' + (i + 1) + '</span>' +
          '<label class="ed-ech-champ">' +
            '<span>Date d\'échéance</span>' +
            '<input type="date" data-champ="date" value="' + echapper(t.date) + '">' +
          '</label>' +
          '<label class="ed-ech-champ">' +
            '<span>Montant</span>' +
            '<input type="number" min="0" step="1000" data-champ="montant" value="' + (t.montant || 0) + '">' +
          '</label>' +
          (t.paye
            ? '<span class="ed-ech-etat ok"><i class="ri-check-line"></i>Payée</span>'
            : (t.reste > 0 && t.reste < t.montant
                ? '<span class="ed-ech-etat part">Reste ' + fmt(t.reste) + '</span>'
                : '<span class="ed-ech-etat att">À venir</span>')) +
          '<button type="button" class="ed-ech-suppr" data-role="supprimer" title="Retirer cette tranche">' +
            '<i class="ri-delete-bin-line"></i>' +
          '</button>' +
        '</div>';
    }).join('');

    corps.innerHTML =
      '<div class="ed-ech-kpis">' +
        '<div><span class="v">' + fmt(d.frais_total) + '</span><span class="l">Frais attendus</span></div>' +
        '<div><span class="v ok">' + fmt(d.cumul_paye) + '</span><span class="l">Déjà encaissé</span></div>' +
        '<div><span class="v bad">' + fmt(d.reste_total) + '</span><span class="l">Reste à payer</span></div>' +
        '<div><span class="v">' + state.tranches.length + '</span><span class="l">Tranche(s)</span></div>' +
      '</div>' +
      (d.existe_en_base
        ? ''
        : '<p class="ed-ech-note"><i class="ri-information-line"></i> ' +
          'Aucun échéancier enregistré : les tranches ci-dessous sont proposées ' +
          'automatiquement (répartition en parts égales). Ajustez puis enregistrez.</p>') +
      '<div class="ed-ech-lignes">' + lignes + '</div>' +
      '<div class="ed-ech-addrow">' +
        '<button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" data-role="ajouter">' +
          '<i class="ri-add-line"></i>Ajouter une tranche' +
        '</button>' +
        '<button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" data-role="repartir">' +
          '<i class="ri-equalizer-line"></i>Répartir le reste en parts égales' +
        '</button>' +
      '</div>' +
      '<div class="ed-ech-message" data-role="message"></div>';

    /* Écoute des modifications */
    corps.querySelectorAll('.ed-ech-ligne').forEach(function (ligne) {
      var idx = parseInt(ligne.getAttribute('data-idx'), 10);

      ligne.querySelectorAll('input').forEach(function (inp) {
        inp.addEventListener('input', function () {
          var champ = inp.getAttribute('data-champ');
          var val = champ === 'montant' ? (parseInt(inp.value, 10) || 0) : inp.value;
          state.tranches[idx][champ] = val;
          majRecap();
        });
      });

      var suppr = ligne.querySelector('[data-role="supprimer"]');
      if (suppr) {
        suppr.addEventListener('click', function () {
          state.tranches.splice(idx, 1);
          rendre();
        });
      }
    });

    corps.querySelector('[data-role="ajouter"]').addEventListener('click', function () {
      var derniere = state.tranches[state.tranches.length - 1];
      state.tranches.push({
        date: derniere ? derniere.date : new Date().toISOString().slice(0, 10),
        montant: 0,
        paye: false,
        reste: 0
      });
      rendre();
    });

    corps.querySelector('[data-role="repartir"]').addEventListener('click', function () {
      repartir();
    });

    majRecap();
  }

  /* Répartit le montant encore à planifier sur les tranches non soldées.
     Les tranches déjà payées gardent leur montant : leur somme reste donc
     égale aux frais attendus, ce qui passe la validation sans écart. */
  function repartir() {
    var frais = Number(state.data.frais_total) || 0;
    var ouvertes = state.tranches.filter(function (t) { return !t.paye; });

    if (!ouvertes.length) {
      var m0 = modal.querySelector('[data-role="message"]');
      if (m0) {
        m0.className = 'ed-ech-message bad';
        m0.textContent = 'Toutes les tranches sont déjà soldées : rien à répartir.';
      }
      return;
    }

    var dejaPaye = state.tranches.reduce(function (s, t) {
      return s + (t.paye ? (Number(t.montant) || 0) : 0);
    }, 0);

    var aRepartir = Math.max(0, frais - dejaPaye);
    var n = ouvertes.length;
    var base = Math.floor(aRepartir / n);

    ouvertes.forEach(function (t, i) {
      t.montant = (i === n - 1) ? (aRepartir - base * (n - 1)) : base;
    });

    rendre();

    var infos = modal.querySelector('[data-role="message"]');
    if (infos) {
      infos.className = 'ed-ech-message ok';
      infos.textContent = aRepartir > 0
        ? 'Reste réparti sur ' + n + ' tranche(s) non soldée(s).'
        : 'Aucun reste à répartir.';
    }
  }

  function majRecap() {
    var total = state.tranches.reduce(function (s, t) { return s + (Number(t.montant) || 0); }, 0);
    var attendu = state.data ? Number(state.data.frais_total) || 0 : 0;
    var ecart = total - attendu;

    modal.querySelector('[data-role="recap"]').innerHTML =
      '<span>Total planifié <b>' + fmt(total) + '</b></span>'
      + '<span>Frais attendus <b>' + fmt(attendu) + '</b></span>';

    var bouton = modal.querySelector('[data-role="enregistrer"]');
    var msg = modal.querySelector('[data-role="message"]');

    var datesOk = state.tranches.every(function (t) { return !!t.date; });
    var montantsOk = state.tranches.length > 0 && total > 0;

    bouton.disabled = !(datesOk && montantsOk);

    if (!msg) return;

    if (!state.tranches.length) {
      msg.className = 'ed-ech-message bad';
      msg.textContent = 'Ajoutez au moins une tranche.';
    } else if (!datesOk) {
      msg.className = 'ed-ech-message bad';
      msg.textContent = 'Chaque tranche doit avoir une date d\'échéance.';
    } else if (total <= 0) {
      msg.className = 'ed-ech-message bad';
      msg.textContent = 'Le total des tranches doit être supérieur à zéro.';
    } else if (ecart !== 0) {
      msg.className = 'ed-ech-message warn';
      msg.textContent = 'Le total planifié diffère de ' + fmt(Math.abs(ecart))
        + (ecart > 0 ? ' de plus' : ' de moins') + ' que les frais attendus.';
    } else {
      msg.className = 'ed-ech-message ok';
      msg.textContent = 'L\'échéancier correspond exactement aux frais attendus.';
    }
  }

  /* ───────────────────────── Enregistrement ────────────────────── */

  function enregistrer() {
    if (state.busy) return;

    var bouton = modal.querySelector('[data-role="enregistrer"]');
    state.busy = true;
    bouton.disabled = true;
    bouton.querySelector('span').textContent = 'Enregistrement…';

    fetch(CFG.ecriture, {
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
        anneeAcademique: state.annee,
        anneeEtude: state.filiere,
        tranches: state.tranches.map(function (t) {
          return { date: t.date, montant: Number(t.montant) || 0 };
        })
      })
    })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
      .then(function (res) {
        state.busy = false;
        bouton.querySelector('span').textContent = 'Enregistrer l\'échéancier';

        if (!res.ok || res.data.success === false) {
          bouton.disabled = false;
          var msg = modal.querySelector('[data-role="message"]');
          if (msg) {
            msg.className = 'ed-ech-message bad';
            msg.textContent = res.data.message || 'L\'enregistrement a échoué.';
          }
          return;
        }

        modal.querySelector('[data-role="corps"]').innerHTML =
          '<div class="ed-ech-succes">' +
            '<i class="ri-checkbox-circle-fill"></i>' +
            '<h3>' + echapper(res.data.message || 'Échéancier enregistré.') + '</h3>' +
            '<p>Les prévisions de paiement de cet étudiant sont à jour.</p>' +
            '<div class="ed-ech-succes-actions">' +
              '<button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" data-role="fermer">Fermer</button>' +
            '</div>' +
          '</div>';
        modal.querySelector('[data-role="recap"]').innerHTML = '';
        modal.querySelector('[data-role="enregistrer"]').disabled = true;
        modal.querySelector('.ed-ech-foot').hidden = true;
        modal.querySelector('[data-role="corps"] [data-role="fermer"]')
          .addEventListener('click', fermer);

        document.dispatchEvent(new CustomEvent('ed:echeancier', { detail: res.data }));
      })
      .catch(function () {
        state.busy = false;
        bouton.disabled = false;
        bouton.querySelector('span').textContent = 'Enregistrer l\'échéancier';
        var msg = modal.querySelector('[data-role="message"]');
        if (msg) {
          msg.className = 'ed-ech-message bad';
          msg.textContent = 'Erreur réseau : l\'échéancier n\'a pas été enregistré.';
        }
      });
  }

  /* ───────────────────────── Événements ────────────────────────── */

  function brancher(el) {
    el.addEventListener('click', function (e) {
      var cible = e.target.closest('[data-role]');
      if (!cible) {
        if (e.target === el) fermer();
        return;
      }
      var role = cible.getAttribute('data-role');
      if (role === 'fermer') fermer();
      else if (role === 'enregistrer') enregistrer();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal && !modal.hidden) {
        e.preventDefault();
        fermer();
      }
    });
  }

  /* Ouverture depuis n'importe quel bouton de la page. */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-ed-echeancier]');
    if (!b) return;
    e.preventDefault();
    ouvrir(
      b.getAttribute('data-ed-echeancier'),
      b.getAttribute('data-annee'),
      b.getAttribute('data-filiere')
    );
  });

  window.EduEcheancier = { open: ouvrir, close: fermer };
})();
