#!/usr/bin/env node
/**
 * Vérifie qu'aucune classe utilitaire générée par Tailwind ne porte le même
 * nom qu'une classe héritée de Bootstrap utilisée dans les vues Blade.
 *
 * Ces collisions sont invisibles à la compilation mais cassent l'affichage :
 * Tailwind définit `.collapse { visibility: collapse }`, ce qui rendait tous
 * les sous-menus de la sidebar littéralement invisibles.
 *
 * Usage : node tools/check-collisions.js
 * Sortie : code 1 si une collision est détectée (utilisable en CI).
 */
const fs = require('fs');
const path = require('path');

const VIEWS = path.join(__dirname, '..', 'resources', 'views');
const CSS = path.join(__dirname, '..', 'public', 'assets', 'css', 'edulmd.css');

/* Classes héritées de Bootstrap dont on attend un comportement précis.
   Si Tailwind en génère une définition concurrente, on casse les vues. */
const LEGACY_CLASSES = [
  'collapse', 'collapsing', 'fade', 'show', 'active', 'disabled',
  'modal', 'modal-dialog', 'modal-content', 'modal-header', 'modal-body',
  'modal-footer', 'modal-title', 'modal-backdrop',
  'dropdown', 'dropdown-menu', 'dropdown-item', 'dropdown-divider',
  'nav', 'nav-item', 'nav-link', 'nav-tabs', 'nav-pills', 'tab-content', 'tab-pane',
  'table', 'table-striped', 'table-hover', 'table-bordered', 'table-responsive',
  'btn', 'btn-primary', 'btn-secondary', 'btn-close', 'btn-group',
  'form-control', 'form-select', 'form-label', 'form-group', 'form-check',
  'input-group', 'input-group-text', 'invalid-feedback',
  'card', 'card-body', 'card-header', 'card-title',
  'alert', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info',
  'row', 'container', 'container-fluid', 'col-12', 'col-6',
  'breadcrumb', 'breadcrumb-item', 'pagination', 'badge',
  'spinner-border', 'back-to-top', 'sidebar', 'sidebar-nav',
  'tooltip', 'popover', 'accordion', 'list-group', 'progress', 'carousel',
  'toast', 'offcanvas', 'ratio', 'placeholder', 'close',
];

/* Règles CSS réellement produites par Tailwind pour ces noms : on les
   recherche précisément dans le fichier compilé. */
function readCss() {
  if (!fs.existsSync(CSS)) {
    console.error('✗ CSS compilé introuvable : ' + CSS);
    console.error('  Lancez `npm run css:build` avant la vérification.');
    process.exit(2);
  }
  return fs.readFileSync(CSS, 'utf8');
}

/* Extrait les sélecteurs de premier niveau du CSS (approximation robuste :
   on ne cherche pas à parser, on cherche `.classe{` ou `.classe,`). */
function findRule(css, cls) {
  const escaped = cls.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
  const re = new RegExp('\\.' + escaped + '\\s*(?:\\{|,)', 'g');
  const matches = [];
  let m;
  while ((m = re.exec(css)) !== null) {
    const idx = m.index;
    const end = css.indexOf('}', idx);
    matches.push({
      position: idx,
      declaration: end > -1 ? css.slice(idx, end + 1) : css.slice(idx, idx + 200),
    });
  }
  return matches;
}

/* Un utilitaire Tailwind est problématique seulement s'il n'est pas
   neutralisé plus loin dans la feuille. On repère donc les correctifs
   explicites placés en fin de fichier. */
function isNeutralized(css, cls, rulePosition) {
  /* On cherche, APRÈS la position de l'utilitaire, une règle qui rétablit
     le comportement attendu. */
  const tail = css.slice(rulePosition);
  if (cls === 'collapse') {
    return /\.collapse[^{]*\{[^}]*visibility:visible/.test(tail);
  }
  if (cls === 'table') {
    return /table\.table[^{]*\{/.test(tail);
  }
  return false;
}

/* Déclarations Tailwind « pures » reconnaissables : ce sont elles qui
   écrasent les styles attendus par les vues historiques. */
const TAILWIND_SIGNATURES = [
  /\.collapse\{visibility:collapse\}/,
  /\.fixed\{position:fixed\}/,
  /\.hidden\{display:none\}/,
  /\.block\{display:block\}/,
  /\.flex\{display:flex\}/,
  /\.grid\{display:grid\}/,
  /\.table\{display:table\}/,
  /\.container\{width:100%\}/,
  /\.truncate\{overflow:hidden/,
  /\.relative\{position:relative\}/,
  /\.sticky\{position:sticky\}/,
  /\.absolute\{position:absolute\}/,
];

function main() {
  const css = readCss();
  let problems = 0;

  console.log('Vérification des collisions Tailwind ↔ legacy…\n');

  for (const cls of LEGACY_CLASSES) {
    const rules = findRule(css, cls);
    for (const rule of rules) {
      const isTailwind = TAILWIND_SIGNATURES.some((sig) => sig.test(rule.declaration));
      if (isTailwind && !isNeutralized(css, cls, rule.position)) {
        console.error(`✗ COLLISION  .${cls}`);
        console.error(`   ${rule.declaration.slice(0, 140)}`);
        console.error(`   → cette règle Tailwind écrase le comportement attendu par les vues.\n`);
        problems++;
        break;
      }
    }
  }

  if (problems === 0) {
    console.log('✓ Aucune collision détectée.');
    process.exit(0);
  }

  console.error(`${problems} collision(s). Ajoutez le plugin concerné dans`);
  console.error('`corePlugins: { … : false }` de tailwind.config.js.\n');
  process.exit(1);
}

main();
