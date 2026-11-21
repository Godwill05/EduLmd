# Tests DOM (sans navigateur)

Le sandbox ne permet pas d'installer Chromium (téléchargement bloqué), donc
les comportements JavaScript sont vérifiés dans un DOM simulé par jsdom.

```bash
npm i jsdom --no-save     # une seule fois

node tools/tests-dom/palette.test.cjs          # palette Ctrl+K      : 14 contrôles
node tools/tests-dom/notes-interface.test.cjs  # gestion des notes   : 14 contrôles
```

`notes-interface.test.cjs` charge une page réellement rendue par Laravel
après un POST de filtres (fichier `/tmp/notes-debug/apres-post2.html`).
Pour la régénérer : `php /tmp/rendre-notes.php`.

Chaque script sort en code 0 si tous les contrôles passent.
