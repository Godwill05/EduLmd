/**
 * EduLMD Shell — comportements de l'interface
 * Remplace intégralement assets/js/main.js (NiceAdmin/Bootstrap).
 * Aucune dépendance à Bootstrap : s'appuie sur EduUI (edulmd-ui.js).
 */
(function () {
  'use strict';

  var $ = window.jQuery;

  /* ─────────── Sidebar mobile ─────────── */
  var body = document.body;
  var toggleBtn = document.querySelector('.toggle-sidebar-btn');
  var backdrop = document.getElementById('sidebarBackdrop');

  function closeSidebar() { body.classList.remove('toggle-sidebar'); }
  function toggleSidebar() { body.classList.toggle('toggle-sidebar'); }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', toggleSidebar);
    toggleBtn.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleSidebar(); }
    });
  }
  if (backdrop) backdrop.addEventListener('click', closeSidebar);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 1200) closeSidebar();
  });

  /* ─────────── Retour en haut ─────────── */
  var backToTop = document.querySelector('.back-to-top');
  if (backToTop) {
    function toggleBacktotop() {
      if (window.scrollY > 100) backToTop.classList.add('active');
      else backToTop.classList.remove('active');
    }
    window.addEventListener('load', toggleBacktotop);
    document.addEventListener('scroll', toggleBacktotop, { passive: true });
    backToTop.addEventListener('click', function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ─────────── Header : ombre au défilement ─────────── */
  var header = document.getElementById('header');
  if (header) {
    function onScroll() {
      header.classList.toggle('header-scrolled', window.scrollY > 8);
    }
    window.addEventListener('load', onScroll);
    document.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ─────────── Tableaux : Simple-DataTables ─────────── */
  function initDataTables() {
    if (!window.simpleDatatables) return;
    var tables = document.querySelectorAll('table.datatable');
    Array.prototype.forEach.call(tables, function (table) {
      if (table.__edDataTable) return;
      /* Simple-DataTables a besoin d'un thead */
      if (!table.tHead) return;
      table.__edDataTable = new window.simpleDatatables.DataTable(table, {
        labels: {
          placeholder: 'Rechercher…',
          perPage: 'éléments par page',
          noRows: 'Aucun résultat',
          info: 'Affichage de {start} à {end} sur {rows} éléments',
          noResults: 'Aucun résultat pour « {query} »',
        },
        classes: {
          wrapper: 'datatable-wrapper',
          container: 'datatable-container',
          top: 'datatable-top',
          bottom: 'datatable-bottom',
          search: 'datatable-search',
          selector: 'datatable-selector',
          info: 'datatable-info',
          pagination: 'datatable-pagination',
          'pagination-list': 'datatable-pagination-list',
        },
      });
    });
  }

  /* ─────────── Validation de formulaire ─────────── */
  function initValidation() {
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.forEach.call(forms, function (form) {
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

  /* ─────────── Menu latéral : chevron / ARIA ─────────── */
  function initSidebarState() {
    var links = document.querySelectorAll('.sidebar-nav [data-bs-toggle="collapse"]');
    Array.prototype.forEach.call(links, function (link) {
      var sel = link.getAttribute('data-bs-target');
      var target = sel ? document.querySelector(sel) : null;
      if (!target) return;
      link.setAttribute('aria-expanded', target.classList.contains('show') ? 'true' : 'false');
      link.addEventListener('click', function () {
        window.setTimeout(function () {
          link.setAttribute('aria-expanded', target.classList.contains('show') ? 'true' : 'false');
        }, 0);
      });
    });
  }

  function init() {
    initValidation();
    initDataTables();
    initSidebarState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  window.addEventListener('load', init);
})();
