#!/usr/bin/env python3
"""Génère vendor/autoload-classmap.php : classes des paquets qui n'utilisent
ni PSR-4 ni PSR-0 (PHPUnit, Symfony polyfills de test, etc.)."""
import json, os, re

RACINE = '/home/user/EduLmd'
VENDOR = os.path.join(RACINE, 'vendor')
lock = json.load(open(os.path.join(RACINE, 'composer.lock')))
paquets = lock['packages'] + lock.get('packages-dev', [])

classes = {}
for p in paquets:
    base = os.path.join(VENDOR, p['name'])
    cj = os.path.join(base, 'composer.json')
    if not os.path.exists(cj):
        continue
    try:
        autoload = json.load(open(cj)).get('autoload') or {}
    except Exception:
        continue
    for chemin in (autoload.get('classmap') or []):
        cible = os.path.join(base, chemin)
        fichiers = []
        if os.path.isdir(cible):
            for r, _, fs in os.walk(cible):
                fichiers += [os.path.join(r, f) for f in fs if f.endswith('.php')]
        elif os.path.exists(cible):
            fichiers.append(cible)
        for f in fichiers:
            try:
                src = open(f, encoding='utf-8', errors='ignore').read()
            except Exception:
                continue
            # namespace puis classes/interfaces/traits de premier niveau
            ns = None
            m = re.search(r'^namespace\s+([\w\\]+)\s*;', src, re.M)
            if m:
                ns = m.group(1)
            for c in re.finditer(r'^\s*(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+(\w+)', src, re.M):
                nom = (ns + '\\' + c.group(1)) if ns else c.group(1)
                classes.setdefault(nom, f)

lignes = ["<?php\n", "/* Classmap généré par tools/build-classmap.py. */\n", "return [\n"]
for nom, f in sorted(classes.items()):
    lignes.append("    '%s' => '%s',\n" % (nom.replace('\\', '\\\\'), f))
lignes.append("];\n")
open(os.path.join(VENDOR, 'autoload-classmap.php'), 'w').writelines(lignes)
print('classmap :', len(classes), 'classes')
