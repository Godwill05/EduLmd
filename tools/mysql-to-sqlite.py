#!/usr/bin/env python3
"""
mysql-to-sqlite.py — convertit un dump mysqldump en base SQLite.

Utile pour faire tourner EduLMD en local sans serveur MySQL : le dump de
production (sql_backup/prod_avant_restauration.sql) est rejoué dans un
fichier SQLite que Laravel peut ouvrir via DB_CONNECTION=sqlite.

Usage :
    python3 tools/mysql-to-sqlite.py sql_backup/prod_avant_restauration.sql /tmp/edulmd/edulmd.sqlite

Limites volontaires : les index et contraintes de clé étrangère ne sont pas
recréés (l'audit ne dépend pas d'eux), les types sont ramenés aux affinités
SQLite, et les colonnes AUTO_INCREMENT deviennent INTEGER PRIMARY KEY.
"""
import re
import sqlite3
import sys

# ── Découpage du script MySQL en instructions ────────────────────────────────


def instructions(sql: str):
    """Découpe le dump en instructions, en respectant chaînes et commentaires."""
    i, n = 0, len(sql)
    debut = 0
    while i < n:
        c = sql[i]
        if c == "'" or c == '"':
            quote = c
            i += 1
            while i < n:
                if sql[i] == '\\':
                    i += 2
                    continue
                if sql[i] == quote:
                    if i + 1 < n and sql[i + 1] == quote:   # '' échappé
                        i += 2
                        continue
                    break
                i += 1
            i += 1
            continue
        if c == '-' and sql.startswith('--', i):
            j = sql.find('\n', i)
            i = n if j < 0 else j + 1
            continue
        if c == '/' and sql.startswith('/*', i):
            j = sql.find('*/', i + 2)
            i = n if j < 0 else j + 2
            continue
        if c == ';':
            yield sql[debut:i].strip()
            i += 1
            debut = i
            continue
        i += 1
    reste = sql[debut:].strip()
    if reste:
        yield reste


# ── Traduction des CREATE TABLE ─────────────────────────────────────────────


def decouper_definitions(corps: str):
    """Découpe le corps d'un CREATE TABLE sur les virgules de premier niveau."""
    morceaux, courant, profondeur, i = [], [], 0, 0
    while i < len(corps):
        c = corps[i]
        if c in "'\"":
            quote = c
            courant.append(c)
            i += 1
            while i < len(corps):
                courant.append(corps[i])
                if corps[i] == '\\':
                    if i + 1 < len(corps):
                        courant.append(corps[i + 1])
                        i += 2
                        continue
                if corps[i] == quote:
                    if i + 1 < len(corps) and corps[i + 1] == quote:
                        courant.append(corps[i + 1])
                        i += 2
                        continue
                    break
                i += 1
            i += 1
            continue
        if c == '(':
            profondeur += 1
        elif c == ')':
            profondeur -= 1
        if c == ',' and profondeur == 0:
            morceaux.append(''.join(courant).strip())
            courant = []
        else:
            courant.append(c)
        i += 1
    if courant:
        morceaux.append(''.join(courant).strip())
    return morceaux


def type_sqlite(type_mysql: str) -> str:
    t = type_mysql.lower()
    if re.match(r'^(tiny|small|medium|big)?int', t):
        return 'INTEGER'
    if re.match(r'^(decimal|numeric|float|double|real)', t):
        return 'NUMERIC'
    if re.match(r'^(date|datetime|timestamp|time|year)', t):
        return 'TEXT'
    return 'TEXT'


def convertir_create(instruction: str):
    m = re.match(r'CREATE TABLE\s+`?(\w+)`?\s*\((.*)\)\s*ENGINE', instruction, re.S)
    if not m:
        return None
    table, corps = m.group(1), m.group(2)
    colonnes, cles_uniques = [], []

    for defi in decouper_definitions(corps):
        if re.match(r'^(PRIMARY KEY|UNIQUE KEY|KEY|FULLTEXT|CONSTRAINT|INDEX)\b', defi, re.I):
            if re.match(r'^UNIQUE (KEY|INDEX)', defi, re.I):
                cols = re.search(r'\(([^)]*)\)', defi)
                if cols:
                    cles_uniques.append([c.strip(' `') for c in cols.group(1).split(',')])
            continue

        cm = re.match(r'`(\w+)`\s+(\w+(?:\s*\([^)]*\))?(?:\s+unsigned)?(?:\s+zerofill)?)(.*)$', defi, re.S)
        if not cm:
            continue
        nom, type_mysql, reste = cm.group(1), cm.group(2), cm.group(3)

        auto = bool(re.search(r'AUTO_INCREMENT', reste, re.I))
        notnull = bool(re.search(r'NOT NULL', reste, re.I))
        cle_primaire = bool(re.search(r'PRIMARY KEY', reste, re.I))

        # UNIQUE posé sur la colonne elle-même
        if re.search(r'\bUNIQUE\b', reste, re.I):
            cles_uniques.append([nom])

        defaut = None
        dm = re.search(r"DEFAULT\s+('(?:[^'\\]|\\.|'')*'|[\w.+\-]+)", reste, re.I)
        if dm:
            valeur = dm.group(1)
            if valeur.upper() == 'NULL':
                defaut = None
            elif valeur.startswith("'"):
                defaut = re.sub(r"\\(.)", r"\1", valeur[1:-1]).replace('NULL', 'NULL')
                defaut = "'" + defaut.replace("'", "''") + "'"
            elif valeur.upper() in ('CURRENT_TIMESTAMP',):
                defaut = 'CURRENT_TIMESTAMP'
            else:
                defaut = valeur

        morceau = '"%s" %s' % (nom, type_sqlite(type_mysql))
        if cle_primaire or auto:
            morceau = '"%s" INTEGER PRIMARY KEY' % nom
            if auto:
                morceau += ' AUTOINCREMENT'
        elif notnull:
            morceau += ' NOT NULL'
        if defaut is not None and not (cle_primaire or auto):
            morceau += ' DEFAULT %s' % defaut
        colonnes.append(morceau)

    if not colonnes:
        return None

    sql = 'CREATE TABLE "%s" (\n  %s\n)' % (table, ',\n  '.join(colonnes))
    # Les index uniques sont posés séparément ; le nom SQLite étant global,
    # on le préfixe par la table pour éviter toute collision entre tables.
    vus = set()
    for cols in cles_uniques:
        etiquette = '_'.join(cols)
        if etiquette in vus:
            continue
        vus.add(etiquette)
        sql += ';\nCREATE UNIQUE INDEX IF NOT EXISTS "uniq_%s_%s" ON "%s" (%s)' % (
            table, etiquette, table, ', '.join('"%s"' % c for c in cols))
    return sql


# ── Traduction des INSERT ───────────────────────────────────────────────────


ECHAPPEMENTS = {'0': '\x00', 'b': '\b', 'n': '\n', 'r': '\r', 't': '\t',
                'Z': '\x1a', '\\': '\\', "'": "'", '"': '"', '%': '%', '_': '_'}


def convertir_valeurs(groupe: str) -> str:
    """Traduit un tuple (...) de valeurs MySQL vers la syntaxe SQLite."""
    sortie = []
    i, n = 0, len(groupe)
    while i < n:
        c = groupe[i]
        if c == "'":
            i += 1
            buf = []
            while i < n:
                if groupe[i] == '\\' and i + 1 < n:
                    suite = groupe[i + 1]
                    buf.append(ECHAPPEMENTS.get(suite, suite))
                    i += 2
                    continue
                if groupe[i] == "'":
                    if i + 1 < n and groupe[i + 1] == "'":
                        buf.append("'")
                        i += 2
                        continue
                    break
                buf.append(groupe[i])
                i += 1
            i += 1
            sortie.append("'" + ''.join(buf).replace("'", "''") + "'")
            continue
        sortie.append(c)
        i += 1
    return ''.join(sortie)


def decouper_tuples(chaine: str):
    tuples, courant, profondeur, i = [], [], 0, 0
    while i < len(chaine):
        c = chaine[i]
        if c == "'":
            courant.append(c)
            i += 1
            while i < len(chaine):
                courant.append(chaine[i])
                if chaine[i] == '\\' and i + 1 < len(chaine):
                    courant.append(chaine[i + 1])
                    i += 2
                    continue
                if chaine[i] == "'":
                    if i + 1 < len(chaine) and chaine[i + 1] == "'":
                        courant.append(chaine[i + 1])
                        i += 2
                        continue
                    break
                i += 1
            i += 1
            continue
        if c == '(':
            profondeur += 1
            if profondeur == 1:
                courant = []
                i += 1
                continue
        elif c == ')':
            profondeur -= 1
            if profondeur == 0:
                tuples.append(''.join(courant))
                courant = []
                i += 1
                continue
        if profondeur >= 1:
            courant.append(c)
        i += 1
    return tuples


def convertir_insert(instruction: str):
    m = re.match(r'INSERT INTO\s+`?(\w+)`?\s*(\([^)]*\))?\s*VALUES\s*(.*)$', instruction, re.S | re.I)
    if not m:
        return None
    table = m.group(1)
    colonnes = m.group(2) or ''
    if colonnes:
        colonnes = '(' + ', '.join('"%s"' % c.strip(' `') for c in colonnes.strip('()').split(',')) + ')'
    tuples = decouper_tuples(m.group(3))
    if not tuples:
        return None
    valeurs = ',\n'.join('(' + convertir_valeurs(t) + ')' for t in tuples)
    return 'INSERT INTO "%s" %s VALUES\n%s' % (table, colonnes, valeurs)


# ── Programme principal ─────────────────────────────────────────────────────


def main():
    if len(sys.argv) < 3:
        print(__doc__)
        sys.exit(1)
    source, cible = sys.argv[1], sys.argv[2]

    sql = open(source, encoding='utf-8', errors='replace').read()
    connexion = sqlite3.connect(cible)
    connexion.execute('PRAGMA journal_mode=MEMORY')
    connexion.execute('PRAGMA synchronous=OFF')

    compteurs = {'create': 0, 'insert': 0, 'ignorees': 0, 'erreurs': []}
    lignes = {}

    for ins in instructions(sql):
        if not ins:
            continue
        tete = ins.split(None, 1)[0].upper()

        if tete in ('SET', 'LOCK', 'UNLOCK', 'USE', 'DELIMITER', 'START', 'COMMIT'):
            compteurs['ignorees'] += 1
            continue
        if tete == 'DROP':
            try:
                connexion.execute(ins)
            except sqlite3.Error:
                pass
            continue
        if tete == 'CREATE':
            if 'CREATE TABLE' not in ins.upper():
                compteurs['ignorees'] += 1
                continue
            traduit = convertir_create(ins)
            if traduit:
                try:
                    for morceau in traduit.split(';\n'):
                        connexion.execute(morceau)
                    compteurs['create'] += 1
                except sqlite3.Error as e:
                    compteurs['erreurs'].append('CREATE %s: %s' % (ins[:60], e))
            continue
        if tete == 'INSERT':
            traduit = convertir_insert(ins)
            if traduit:
                try:
                    connexion.execute(traduit)
                    compteurs['insert'] += 1
                    nom = re.match(r'INSERT INTO\s+`?(\w+)', ins, re.I).group(1)
                    lignes[nom] = lignes.get(nom, 0) + traduit.count('),\n(') + 1
                except sqlite3.Error as e:
                    compteurs['erreurs'].append('INSERT %s: %s' % (ins[:60], e))
            continue
        compteurs['ignorees'] += 1

    connexion.commit()
    connexion.close()

    print('Tables créées   : %d' % compteurs['create'])
    print('INSERT exécutés : %d' % compteurs['insert'])
    print('Instructions ignorées : %d' % compteurs['ignorees'])
    if compteurs['erreurs']:
        print('Erreurs (%d) :' % len(compteurs['erreurs']))
        for e in compteurs['erreurs'][:15]:
            print('  -', e)
    print('Lignes par table (top 15) :')
    for nom, nb in sorted(lignes.items(), key=lambda kv: -kv[1])[:15]:
        print('  %-24s %d' % (nom, nb))


if __name__ == '__main__':
    main()
