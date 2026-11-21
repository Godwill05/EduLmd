# Tests DOM (sans navigateur)

Le sandbox ne permet pas d'installer Chromium (téléchargement bloqué), donc
les comportements JavaScript sont vérifiés dans un DOM simulé par jsdom.

```bash
npm i jsdom --no-save     # une seule fois
node tools/tests-dom/palette.test.cjs        # palette Ctrl+K : 14 contrôles
node tools/tests-dom/notes-cascade.test.cjs  # restauration /notes : 9 contrôles
node tools/tests-dom/notes-changement.test.cjs # changement de sélection : 8 contrôles
```

Chaque script sort en code 0 si tous les contrôles passent.
