#!/usr/bin/env python3
"""
install-vendor.py — installe vendor/ SANS Composer ni Packagist.

Contexte : l'environnement n'a ni Composer ni accès à packagist.org, mais
composer.lock référence exclusivement des zipballs api.github.com, accessibles.
On télécharge donc chaque archive, on la place sous vendor/<nom>, on écrit
l'autoload PSR-4 mappé depuis composer.json / composer.lock, puis un
autoload.php compatible avec la structure genérée par Composer.

Sortie : vendor/ prêt pour bootstrap/autoload.php.
"""
import json, os, re, shutil, sys, urllib.request, zipfile, tempfile
from concurrent.futures import ThreadPoolExecutor

RACINE = '/home/user/EduLmd'
VENDOR = os.path.join(RACINE, 'vendor')
CACHE = '/tmp/composer-cache'
UA = {'User-Agent': 'edulmd-audit/1.0'}

os.makedirs(CACHE, exist_ok=True)
os.makedirs(VENDOR, exist_ok=True)


def telecharger(paquet):
    nom = paquet['name']
    dist = paquet.get('dist') or {}
    url = dist.get('url')
    cible = os.path.join(CACHE, nom.replace('/', '__') + '.zip')
    if os.path.exists(cible) and os.path.getsize(cible) > 0:
        return nom, cible, 'cache'
    if not url:
        return nom, None, 'pas de dist'
    try:
        req = urllib.request.Request(url, headers=UA)
        with urllib.request.urlopen(req, timeout=180) as r, open(cible + '.part', 'wb') as f:
            shutil.copyfileobj(r, f)
        os.replace(cible + '.part', cible)
        return nom, cible, 'ok'
    except Exception as e:
        return nom, None, 'ERREUR: %s' % e


def extraire(paquet, archive):
    nom = paquet['name']
    dest = os.path.join(VENDOR, nom)
    if os.path.isdir(dest) and os.listdir(dest):
        return nom, 'déjà extrait'
    tmp = tempfile.mkdtemp(dir=CACHE)
    try:
        with zipfile.ZipFile(archive) as z:
            z.extractall(tmp)
        entrees = os.listdir(tmp)
        if len(entrees) != 1:
            return nom, 'architecture inattendue: %s' % entrees
        src = os.path.join(tmp, entrees[0])
        os.makedirs(os.path.dirname(dest), exist_ok=True)
        if os.path.isdir(dest):
            shutil.rmtree(dest)
        shutil.copytree(src, dest)
        return nom, 'ok'
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def main():
    lock = json.load(open(os.path.join(RACINE, 'composer.lock')))
    paquets = lock['packages'] + lock.get('packages-dev', [])

    print('Téléchargement de %d paquets…' % len(paquets), flush=True)
    with ThreadPoolExecutor(max_workers=8) as ex:
        resultats = list(ex.map(telecharger, paquets))

    echecs = [(n, s) for n, a, s in resultats if not a]
    for n, s in echecs:
        print('  ÉCHEC téléchargement %-45s %s' % (n, s), flush=True)

    archives = {n: a for n, a, s in resultats if a}
    print('%d/%d téléchargés' % (len(archives), len(paquets)), flush=True)

    print('Extraction…', flush=True)
    with ThreadPoolExecutor(max_workers=8) as ex:
        res = list(ex.map(lambda p: extraire(p, archives[p['name']]), [p for p in paquets if p['name'] in archives]))
    mauvais = [(n, s) for n, s in res if s not in ('ok', 'déjà extrait')]
    for n, s in mauvais:
        print('  PROBLÈME %-45s %s' % (n, s), flush=True)
    print('%d paquets extraits' % (len(res) - len(mauvais)), flush=True)

    ecrire_autoload(paquets)


def ecrire_autoload(paquets):
    """Autoload minimal : PSR-4 + classmap, à la manière de Composer."""
    composer = json.load(open(os.path.join(RACINE, 'composer.json')))

    psr4 = {}
    for nom, chemins in (composer.get('autoload', {}).get('psr-4') or {}).items():
        psr4[nom] = [os.path.join(RACINE, c) for c in (chemins if isinstance(chemins, list) else [chemins])]
    for paquet in paquets:
        chemin = os.path.join(VENDOR, paquet['name'], 'composer.json')
        if not os.path.exists(chemin):
            continue
        try:
            autoload = json.load(open(chemin)).get('autoload') or {}
        except Exception:
            continue
        for nom, chemins in (autoload.get('psr-4') or {}).items():
            liste = chemins if isinstance(chemins, list) else [chemins]
            psr4.setdefault(nom, []).extend(
                os.path.join(VENDOR, paquet['name'], c) for c in liste)

    # Fichiers non autoloadables (helpers, constantes…) déclarés en "files".
    fichiers = []
    for chemin in (composer.get('autoload', {}).get('files') or []):
        fichiers.append(os.path.join(RACINE, chemin))
    for paquet in paquets:
        chemin = os.path.join(VENDOR, paquet['name'], 'composer.json')
        if not os.path.exists(chemin):
            continue
        try:
            autoload = json.load(open(chemin)).get('autoload') or {}
        except Exception:
            continue
        for rel in (autoload.get('files') or []):
            fichiers.append(os.path.join(VENDOR, paquet['name'], rel))

    # Le projet utilise une architecture "Laravel 8" classique (app/ + namespace App\).
    psr4.setdefault('App\\', [os.path.join(RACINE, 'app')])
    psr4.setdefault('Database\\Factories\\', [os.path.join(RACINE, 'database/factories')])
    psr4.setdefault('Database\\Seeders\\', [os.path.join(RACINE, 'database/seeders')])
    psr4.setdefault('Tests\\', [os.path.join(RACINE, 'tests')])

    # PSR-4 trié du préfixe le plus long au plus court
    entrees = sorted(psr4.items(), key=lambda kv: -len(kv[0]))

    lignes = []
    lignes.append("<?php\n")
    lignes.append("/* Autoload généré par tools/install-vendor.py (audit local, sans Composer). */\n")
    lignes.append("spl_autoload_register(function ($classe) {\n")
    lignes.append("    static $map = null;\n")
    lignes.append("    if ($map === null) {\n        $map = [\n")
    for prefixe, chemins in entrees:
        chemins_php = ', '.join("'%s'" % c.replace('\\', '\\\\') for c in chemins)
        lignes.append("            %s => [%s],\n" % ("'" + prefixe.replace('\\', '\\\\') + "'", chemins_php))
    lignes.append("        ];\n    }\n")
    lignes.append("""
    foreach ($map as $prefixe => $repertoires) {
        if (strncmp($classe, $prefixe, strlen($prefixe)) !== 0) {
            continue;
        }
        $relatif = substr($classe, strlen($prefixe));
        $fichier = str_replace('\\\\', '/', $relatif) . '.php';
        foreach ($repertoires as $rep) {
            $chemin = $rep . '/' . $fichier;
            if (file_exists($chemin)) {
                require $chemin;
                return true;
            }
        }
    }
    return false;
});
""")
    # Chargement des fichiers "files" avant l'enregistrement de l'autoloader,
    # exactement comme le fait l'autoload_real.php de Composer.
    with open(os.path.join(VENDOR, 'autoload.php'), 'w') as f:
        f.writelines(lignes)
        f.write("\n")
        # Composer\InstalledVersions, attendu par plusieurs paquets.
        f.write("require_once '%s';\n" % os.path.join(VENDOR, 'composer/InstalledVersions.php'))
        for chemin in fichiers:
            if os.path.exists(chemin):
                f.write("require_once '%s';\n" % chemin.replace('\\', '\\\\'))
    print('autoload.php écrit (%d préfixes PSR-4, %d fichiers "files")'
          % (len(entrees), len(fichiers)), flush=True)


if __name__ == '__main__':
    main()
