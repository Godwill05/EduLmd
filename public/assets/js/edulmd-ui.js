/**
 * EduLMD UI — moteur d'interactions maison (remplace Bootstrap JS)
 * ------------------------------------------------------------------
 * Expose une API strictement compatible avec ce que les vues existantes
 * utilisent déjà, afin de ne modifier aucune logique métier :
 *
 *   - data-bs-toggle="modal" / data-bs-target / data-bs-dismiss="modal"
 *   - data-bs-toggle="tab" / "pill"           (onglets)
 *   - data-bs-toggle="collapse" / data-bs-parent
 *   - data-bs-toggle="dropdown"               (menus)
 *   - data-bs-toggle="tooltip"                (bulles natives)
 *   - jQuery  : $('#x').modal('show'|'hide'|'toggle')
 *   - événements : show.bs.modal / shown.bs.modal / hide.bs.modal / hidden.bs.modal
 *   - window.bootstrap.Modal  (getInstance / getOrCreateInstance / show / hide)
 *   - .needs-validation (validation de formulaire)
 *
 * Zéro dépendance externe. ~10 Ko non minifié.
 */
(function () {
  'use strict';

  var SELECTOR_OPEN = '[data-bs-toggle="modal"]';
  var backdropEl = null;
  var openModals = [];
  var lastFocus = null;

  /* ─────────────────────────── Utilitaires ─────────────────────────── */

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function trigger(el, name) {
    if (!el) return;
    var ev;
    if (typeof CustomEvent === 'function') {
      ev = new CustomEvent(name, { bubbles: true, cancelable: true });
    } else {
      ev = document.createEvent('CustomEvent');
      ev.initCustomEvent(name, true, true, null);
    }
    el.dispatchEvent(ev);
    return ev;
  }

  /* ─────────────────────────── MODALES ─────────────────────────── */

  function Modal(el, options) {
    this._element = el;
    this._config = options || {};
    this._isShown = false;
    this._isTransitioning = false;
    el.__edModal = this;
    this._bind();
  }

  Modal.prototype._bind = function () {
    var self = this;
    this._element.addEventListener('click', function (e) {
      var dismiss = e.target.closest('[data-bs-dismiss="modal"], .btn-close');
      if (dismiss) { e.preventDefault(); self.hide(); return; }
      /* clic sur le fond (hors .modal-dialog) */
      if (e.target === self._element && self._config.backdrop !== 'static') {
        self.hide();
      }
    });
  };

  Modal.prototype.show = function (relatedTarget) {
    var self = this;
    if (this._isShown) return;
    var ev = trigger(this._element, 'show.bs.modal');
    if (ev && ev.defaultPrevented) return;

    lastFocus = document.activeElement;

    /* Ferme les modales empilées ? on garde le support d'une seule à la fois
       mais on autorise l'empilement visuel. */
    openModals.forEach(function (m) {
      if (m !== self && m._element === el_parent_of(self)) { /* no-op */ }
    });

    this._isShown = true;
    this._element.classList.add('show');
    this._element.style.display = 'block';
    this._element.removeAttribute('aria-hidden');
    this._element.setAttribute('aria-modal', 'true');
    this._element.setAttribute('role', 'dialog');
    document.body.classList.add('ed-modal-open');

    if (openModals.indexOf(this) === -1) openModals.push(this);

    /* Focus sur le premier champ / bouton */
    window.setTimeout(function () {
      var focusable = self._element.querySelector(
        '.modal-body input:not([type="hidden"]):not([disabled]), .modal-body select:not([disabled]), .modal-body textarea:not([disabled]), .modal-footer .btn, .modal-header .btn-close'
      );
      if (focusable) {
        try { focusable.focus({ preventScroll: true }); } catch (e) { focusable.focus(); }
      }
      self._element.classList.add('ed-modal-ready');
      trigger(self._element, 'shown.bs.modal');
    }, 60);
  };

  Modal.prototype.hide = function () {
    var self = this;
    if (!this._isShown) return;
    var ev = trigger(this._element, 'hide.bs.modal');
    if (ev && ev.defaultPrevented) return;

    this._element.classList.remove('show');
    this._element.classList.remove('ed-modal-ready');
    this._element.style.display = 'none';
    this._element.setAttribute('aria-hidden', 'true');
    this._isShown = false;

    var i = openModals.indexOf(this);
    if (i > -1) openModals.splice(i, 1);

    if (openModals.length === 0) document.body.classList.remove('ed-modal-open');

    trigger(this._element, 'hidden.bs.modal');

    if (lastFocus && typeof lastFocus.focus === 'function') {
      try { lastFocus.focus({ preventScroll: true }); } catch (e) { lastFocus.focus(); }
    }
  };

  Modal.prototype.toggle = function () { this._isShown ? this.hide() : this.show(); };
  Modal.prototype.dispose = function () { this.hide(); delete this._element.__edModal; };

  Modal.getInstance = function (el) { return el ? el.__edModal || null : null; };
  Modal.getOrCreateInstance = function (el, opts) {
    return Modal.getInstance(el) || new Modal(el, opts);
  };

  function el_parent_of() { return null; } /* compat interne */

  /* Délégation globale des déclencheurs de modale */
  document.addEventListener('click', function (e) {
    var opener = e.target.closest(SELECTOR_OPEN);
    if (opener) {
      var sel = opener.getAttribute('data-bs-target') || opener.getAttribute('href');
      if (!sel || sel === '#') return;
      var target = qs(sel);
      if (!target) return;
      e.preventDefault();
      Modal.getOrCreateInstance(target, {
        backdrop: opener.getAttribute('data-bs-backdrop') || true,
        keyboard: opener.getAttribute('data-bs-keyboard') !== 'false'
      }).show(opener);
      return;
    }
    /* Fermeture via bouton dans le header/footer (data-bs-dismiss) */
    var dismisser = e.target.closest('[data-bs-dismiss="modal"]');
    if (dismisser) {
      var modal = dismisser.closest('.modal');
      if (modal) { e.preventDefault(); Modal.getOrCreateInstance(modal).hide(); }
    }
  });

  /* Échap ferme la modale la plus haute */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || openModals.length === 0) return;
    var top = openModals[openModals.length - 1];
    if (top._config.keyboard === false) return;
    top.hide();
  });

  /* ─────────────────────────── ONGLETS ─────────────────────────── */

  function activateTab(triggerEl) {
    var sel = triggerEl.getAttribute('data-bs-target') || triggerEl.getAttribute('href');
    if (!sel || sel === '#') return;
    var pane = qs(sel);
    if (!pane) return;

    var nav = triggerEl.closest('.nav, .nav-tabs, [role="tablist"]');
    if (nav) {
      qsa('.nav-link, [data-bs-toggle="tab"], [data-bs-toggle="pill"]', nav).forEach(function (l) {
        l.classList.remove('active');
        l.setAttribute('aria-selected', 'false');
      });
    }
    triggerEl.classList.add('active');
    triggerEl.setAttribute('aria-selected', 'true');

    var content = pane.parentElement;
    if (content) {
      Array.prototype.forEach.call(content.children, function (child) {
        if (child.classList && child.classList.contains('tab-pane')) {
          child.classList.remove('active', 'show');
        }
      });
    }
    pane.classList.add('active', 'show');
    trigger(pane, 'shown.bs.tab');
  }

  document.addEventListener('click', function (e) {
    var tab = e.target.closest('[data-bs-toggle="tab"], [data-bs-toggle="pill"]');
    if (tab) { e.preventDefault(); activateTab(tab); }
  });

  /* ─────────────────────────── COLLAPSE ─────────────────────────── */

  function collapseToggle(triggerEl) {
    var sel = triggerEl.getAttribute('data-bs-target') || triggerEl.getAttribute('href');
    if (!sel || sel === '#') return;
    var target = qs(sel);
    if (!target) return;

    var isOpen = target.classList.contains('show');
    var parentSel = triggerEl.getAttribute('data-bs-parent');

    if (!isOpen && parentSel) {
      qsa('.collapse.show', qs(parentSel)).forEach(function (other) {
        if (other === target) return;
        other.classList.remove('show');
        var t = qs('[data-bs-target="#' + other.id + '"], [href="#' + other.id + '"]');
        if (t) t.setAttribute('aria-expanded', 'false');
      });
    }

    if (isOpen) {
      target.classList.remove('show');
      target.style.height = '';
      triggerEl.setAttribute('aria-expanded', 'false');
    } else {
      target.classList.add('show');
      target.style.height = '';
      triggerEl.setAttribute('aria-expanded', 'true');
    }
  }

  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-bs-toggle="collapse"]');
    if (c) { e.preventDefault(); collapseToggle(c); }
  });

  /* ─────────────────────────── DROPDOWNS ─────────────────────────── */

  function closeAllDropdowns(except) {
    qsa('.dropdown-menu.show').forEach(function (menu) {
      if (menu === except) return;
      menu.classList.remove('show');
      var t = menu.parentElement && menu.parentElement.querySelector('[data-bs-toggle="dropdown"]');
      if (t) t.setAttribute('aria-expanded', 'false');
    });
  }

  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-bs-toggle="dropdown"]');
    if (toggle) {
      e.preventDefault();
      var parent = toggle.closest('.dropdown, .dropstart, .btn-group, li');
      var menu = parent && parent.querySelector('.dropdown-menu');
      if (!menu) return;
      var willShow = !menu.classList.contains('show');
      closeAllDropdowns(willShow ? menu : null);
      menu.classList.toggle('show', willShow);
      toggle.setAttribute('aria-expanded', willShow ? 'true' : 'false');
      return;
    }
    if (!e.target.closest('.dropdown-menu')) closeAllDropdowns(null);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAllDropdowns(null);
  });

  /* ─────────────────────────── TOOLTIPS (natif) ─────────────────────────── */

  var Tooltip = function (el) {
    var text = el.getAttribute('data-bs-title') || el.getAttribute('title');
    if (text) { el.setAttribute('title', text); }
    return { dispose: function () {} };
  };
  Tooltip.getInstance = function () { return null; };

  /* ─────────────────────────── VALIDATION ─────────────────────────── */

  function initValidation() {
    qsa('.needs-validation').forEach(function (form) {
      if (form.__edValidated) return;
      form.__edValidated = true;
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }

  /* ─────────────────────────── COMPAT jQuery ($.fn.modal) ─────────────────────────── */

  if (window.jQuery) {
    var $ = window.jQuery;
    $.fn.modal = function (action) {
      var method = typeof action === 'string' ? action : 'show';
      return this.each(function () {
        var inst = Modal.getOrCreateInstance(this);
        if (method === 'show') inst.show();
        else if (method === 'hide') inst.hide();
        else if (method === 'toggle') inst.toggle();
        else if (method === 'dispose') inst.dispose();
      });
    };
    $.fn.collapse = function (action) {
      var method = typeof action === 'string' ? action : 'toggle';
      return this.each(function () {
        if (method === 'show') { this.classList.add('show'); this.style.height = ''; }
        else if (method === 'hide') { this.classList.remove('show'); this.style.height = ''; }
        else { this.classList.toggle('show'); this.style.height = ''; }
      });
    };
    $.fn.tab = function () {
      return this.each(function () { activateTab(this); });
    };
  }

  /* ─────────────────────────── INITIALISATION ─────────────────────────── */

  function init() {
    initValidation();
    /* marque les déclencheurs pour l'accessibilité */
    qsa('[data-bs-toggle="collapse"]').forEach(function (el) {
      if (!el.hasAttribute('aria-expanded')) el.setAttribute('aria-expanded', 'false');
    });
    /* les modales présentes mais sans .show sont masquées proprement */
    qsa('.modal').forEach(function (m) {
      if (!m.classList.contains('show')) { m.style.display = 'none'; m.setAttribute('aria-hidden', 'true'); }
      if (!m.__edModal) new Modal(m);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  /* ré-initialise après un chargement AJAX partiel */
  window.addEventListener('load', init);

  /* ─────────────────────────── EXPORT ─────────────────────────── */

  window.EduUI = { Modal: Modal, Tooltip: Tooltip, activateTab: activateTab, init: init };

  /* API attendue par les vues existantes (`new bootstrap.Modal(...)`) */
  window.bootstrap = window.bootstrap || {};
  window.bootstrap.Modal = Modal;
  window.bootstrap.Tooltip = Tooltip;
})();
