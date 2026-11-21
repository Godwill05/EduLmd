#!/usr/bin/env python3
"""
Génère public/preview-<page>.html à partir des vraies vues Blade, pour
vérification visuelle des nouvelles pages. Jamais utilisé en production.

Stratégie : le layout et les sections CSS sont rendus tels quels ; seules
les boucles de données (@forelse / @foreach) sont développées avec des
objets d'essai, en respectant la syntaxe des expressions de la vue.
"""
import datetime
import importlib.util
import re


# ───────────────────────── évaluateur Blade minimal ─────────────────────────


class Obj:
    """Objet simple accessible en notation `->prop` après conversion."""

    def __init__(self, **kw):
        self.__dict__.update(kw)

    def links(self, *a, **k):
        return '<ul class="pagination"><li class="page-item active"><a class="page-link">1</a></li></ul>'

    def total(self):
        return len(self.__dict__.get('_rows', []))

    def sum(self):
        return 0

    def count(self):
        return len(self.__dict__.get('_rows', []))


def traduire_ternaire(expr):
    """Convertit `cond ? a : b` en `a if cond else b`, y compris imbriqué.
    """
    expr = expr.replace('->', '\x01')
    return _ternaire(expr).replace('\x01', '->')


def _ternaire(expr):
    """Version interne de traduire_ternaire (sans protection).

    La condition est délimitée par la parenthèse englobante la plus proche ;
    la branche fausse va jusqu'à la parenthèse fermante correspondante.
    """
    for _ in range(25):
        q = expr.find('?')
        if q == -1:
            return expr

        # Début de la condition : après la '(' englobante la plus proche
        prof = 0
        debut = 0
        j = q - 1
        while j >= 0:
            c = expr[j]
            if c == ')':
                prof += 1
            elif c == '(':
                if prof == 0:
                    debut = j + 1
                    break
                prof -= 1
            elif prof == 0 and c in ',&|':
                debut = j + 1
                break
            j -= 1

        # ':' de même niveau
        prof = 0
        c2 = None
        k = q + 1
        while k < len(expr):
            c = expr[k]
            if c == '(':
                prof += 1
            elif c == ')':
                if prof == 0:
                    break
                prof -= 1
            elif c == ':' and prof == 0:
                c2 = k
                break
            k += 1
        if c2 is None:
            return expr

        # Fin de la branche fausse : parenthèse fermante de même niveau ou fin
        prof = 0
        m = c2 + 1
        fin_else = len(expr)
        while m < len(expr):
            c = expr[m]
            if c == '(':
                prof += 1
            elif c == ')':
                if prof == 0:
                    fin_else = m
                    break
                prof -= 1
            m += 1

        cond = expr[debut:q].strip()
        vrai = expr[q + 1:c2].strip()
        faux = expr[c2 + 1:fin_else].strip()
        if vrai == '':          # ternaire court  x ?: y
            vrai = cond

        expr = (expr[:debut] + '(' + vrai + ') if (' + cond + ') else ('
                + faux + ')' + expr[fin_else:])
    return expr


def preparer_expr(expr):
    expr = expr.strip()
    expr = re.sub(r'\btrue\b', 'True', expr)
    expr = re.sub(r'\bfalse\b', 'False', expr)
    expr = re.sub(r'\bnull\b', 'None', expr)

    # Helpers Laravel
    expr = re.sub(r"route\('([^']+)'[^)]*\)", r"'#'", expr)
    expr = re.sub(r'route\("([^"]+)"[^)]*\)', r"'#'", expr)
    expr = re.sub(r"asset\('([^']*)'\)", r"'\1'", expr)
    expr = re.sub(r"url\('([^']*)'\)", r"'\1'", expr)
    expr = re.sub(r"session\([^)]*\)", "''", expr)
    expr = re.sub(r"csrf_token\(\)", "'tok'", expr)
    expr = re.sub(r'@csrf', '', expr)
    expr = re.sub(r"now\(\)", "datetime.datetime(2026,11,21)", expr)
    expr = re.sub(r"\\Carbon\\Carbon::parse\(([^)]*)\)\.format\(['\"]([^'\"]*)['\"]\)",
                  r"'21/11/2026 à 14:30'", expr)
    expr = re.sub(r"\\Carbon\\Carbon::parse\(([^)]*)\)",
                  r"datetime.datetime(2026,11,21)", expr)
    expr = re.sub(r"number_format\(([^,]+),\s*0,\s*','\s*,\s*' '\)", r"\1", expr)
    expr = re.sub(r"number_format\(([^)]*)\)", r"'\1'", expr)
    expr = re.sub(r"optional\(([^)]*)\)", r"\1", expr)
    expr = re.sub(r"@json\(([^()]*)\)", r"\1", expr)
    expr = re.sub(r"urlencode\(([^)]*)\)", r"\1", expr)
    expr = re.sub(r"implode\(([^,]+),\s*([^)]+)\)", r"', '.join(\2)", expr)
    expr = re.sub(r"count\(([^()]*)\)", r"len(\1)", expr)
    expr = re.sub(r"mb_strlen\(([^)]*)\)", r"len(\1)", expr)
    expr = re.sub(r"mb_substr\(([^,]+),\s*0,\s*([0-9]+)\)", r"\1[: \2]", expr)

    # Chaînes simples -> chaînes Python (les ' internes deviennent \')
    expr = expr.replace('"', "'")

    # Accès aux variables (tolérant : une clé absente vaut None, comme en PHP)
    expr = re.sub(r'\$(\w+)\[([^\]]*)\]', r'V.get("\1", {}).get(\2)', expr)
    expr = re.sub(r'\$(\w+)', r'V.get("\1")', expr)

    # Concaténation PHP : " . " entre deux opérandes -> +
    # (avant la conversion -> en . pour ne pas confondre les deux)
    expr = re.sub(r"\s\.\s", " \x02 ", expr)

    # Opérateurs et fonctions PHP
    expr = expr.replace('->', '.')
    expr = expr.replace('===', '==').replace('!==', '!=')
    expr = re.sub(r'&&', ' and ', expr)
    expr = re.sub(r'\|\|', ' or ', expr)
    # !$x  ->  not $x   (uniquement devant une variable)
    expr = re.sub(r'!\s*(?=\$)', 'not ', expr)
    expr = re.sub(r'\bnew\s+\\?[\w\\]+', "V.get('_dummy')", expr)
    expr = expr.replace('=>', ': ')
    return expr


def traduire_coalesce(expr):
    """Convertit `a ?? b` en `a if a is not None else b`."""
    # Neutralise l'opérateur objet pour ne pas générer de faux " . "
    expr = expr.replace('->', '\x01')
    for _ in range(8):
        i = expr.find('??')
        if i == -1:
            return expr
        # début de l'opérande gauche : parenthèse englobante ou début
        prof = 0
        debut = 0
        j = i - 1
        while j >= 0:
            c = expr[j]
            if c == ')':
                prof += 1
            elif c == '(':
                if prof == 0:
                    debut = j + 1
                    break
                prof -= 1
            elif prof == 0 and c in ',&|':
                debut = j + 1
                break
            j -= 1
        gauche = expr[debut:i].strip()

        prof = 0
        fin = len(expr)
        k = i + 2
        while k < len(expr):
            c = expr[k]
            if c == '(':
                prof += 1
            elif c == ')':
                if prof == 0:
                    fin = k
                    break
                prof -= 1
            elif prof == 0 and c in ',&|':
                fin = k
                break
            k += 1
        droite = expr[i + 2:fin].strip()

        expr = expr[:debut] + f'({gauche} if {gauche} is not None else {droite})' + expr[fin:]
    return expr.replace('\x01', '->')


def _concat_php(expr, env):
    """Évalue une expression contenant des ` \x02 ` (concaténation PHP).

    Découpe au niveau 0 de parenthèses et joint les morceaux en chaîne,
    exactement comme l'opérateur `.` de PHP.
    """
    morceaux = []
    prof = 0
    courant = ''
    i = 0
    while i < len(expr):
        c = expr[i]
        if c == '\x02' and prof == 0:
            morceaux.append(courant)
            courant = ''
            i += 1
            continue
        if c == '(':
            prof += 1
        elif c == ')':
            prof -= 1
        courant += c
        i += 1
    morceaux.append(courant)

    resultat = ''
    for m in morceaux:
        m = m.strip()
        if not m:
            continue
        try:
            v = eval(m, {'__builtins__': {}}, env)
        except Exception:
            v = ''
        resultat += '' if v is None else str(v)
    return resultat


def _env(ctx):
    """Environnement d'évaluation (fonctions PHP émulées)."""
    return {
        'V': ctx, 'datetime': datetime, 'len': len, 'str': str,
        'int': int, 'float': float, 'round': round, 'sorted': sorted,
        'str_contains': lambda a, b: b in str(a),
        'str_starts_with': lambda a, b: str(a).startswith(b),
        'in_array': lambda a, b: a in b,
        'isset': lambda x=None: x is not None and x != '',
        'array_slice': lambda l, d, n=None: (list(l)[d:] if n is None else list(l)[d:d + n]),
        'array_reverse': lambda l: list(reversed(list(l))),
        'count': lambda x: (len(x) if hasattr(x, '__len__') else 0),
        'is_null': lambda x: x is None,
        'empty': lambda x: not x,
        'urlencode': str,
        'max': max, 'min': min, 'abs': abs,
        'date': lambda *a: '21/11/2026',
        'trim': lambda s, *a: str('' if s is None else s).strip(),
        'strtoupper': lambda s: str('' if s is None else s).upper(),
        'ucfirst': lambda s: str('' if s is None else s).capitalize(),
        'lcfirst': lambda s: str('' if s is None else s)[:1].lower() + str('' if s is None else s)[1:],
        'uniqid': lambda: 'x',
        'class_basename': lambda s: str(s).split('\\')[-1],
    }


def _decouper_concat(expr):
    """Découpe une expression PHP sur les `.` de niveau 0."""
    morceaux = []
    prof = 0
    courant = ''
    i = 0
    while i < len(expr):
        c = expr[i]
        if c in "'\"":
            j = i + 1
            while j < len(expr):
                if expr[j] == '\\':
                    j += 2
                    continue
                if expr[j] == c:
                    break
                j += 1
            courant += expr[i:j + 1]
            i = j + 1
            continue
        if c == '(':
            prof += 1
        elif c == ')':
            prof -= 1
        if c == '.' and prof == 0:
            avant = expr[i - 1] if i > 0 else ''
            apres = expr[i + 1] if i + 1 < len(expr) else ''
            # ignore ->  et  .5  et  $a->b
            if avant in '=<>!.' or apres == '=' or avant.isdigit() and apres.isdigit():
                courant += c
                i += 1
                continue
            morceaux.append(courant)
            courant = ''
            i += 1
            continue
        courant += c
        i += 1
    morceaux.append(courant)
    return morceaux


def _evaluer_appel(expr, ctx):
    """Évalue `fonction(args…)` en évaluant chaque argument récursivement.

    Nécessaire pour que `trim($a . $b)` concatène correctement.
    """
    m = re.match(r'^(\w+)\((.*)\)$', expr.strip(), re.S)
    if not m:
        return None, False

    nom, args = m.group(1), m.group(2)
    env = _env(ctx)
    if nom not in env:
        return None, False

    # découpe des arguments au niveau 0
    parts = []
    prof = 0
    courant = ''
    for c in args:
        if c == '(':
            prof += 1
        elif c == ')':
            prof -= 1
        if c == ',' and prof == 0:
            parts.append(courant)
            courant = ''
            continue
        courant += c
    if args.strip():
        parts.append(courant)

    try:
        valeurs = [evaluer(a, ctx) for a in parts]
        return env[nom](*valeurs), True
    except Exception:
        return None, False


def evaluer(expr, ctx):
    # Concaténation PHP : on découpe sur les `.` de niveau 0
    if ' . ' in expr:
        morceaux = _decouper_concat(expr)
        if len(morceaux) > 1:
            return ''.join(
                '' if v is None else str(v)
                for v in (evaluer(m, ctx) for m in morceaux)
            )

    # Appel de fonction : arguments évalués récursivement
    valeur, trouve = _evaluer_appel(expr, ctx)
    if trouve:
        return valeur

    expr = traduire_coalesce(expr)
    expr = traduire_ternaire(expr)
    expr = preparer_expr(expr)
    # Conserve les espaces multiples dans les chaînes
    env = _env(ctx)
    try:
        if '\x02' in expr:
            return _concat_php(expr, env)
        return eval(expr, {'__builtins__': {}}, env)
    except Exception:
        return ''


# ───────────────────────────── rendu Blade ─────────────────────────────

class _Directive:
    """Motif minimal : on repère le mot-clé, la condition est extraite après."""

    def __init__(self):
        self.base = re.compile(r'@(if|elseif|else|endif|unless|endunless|isset|endisset|can|endcan)\b', re.S)

    def finditer(self, src):
        for m in self.base.finditer(src):
            # lit la condition équilibrée qui suit, si présente
            i = m.end()
            while i < len(src) and src[i] in ' \t':
                i += 1
            if i < len(src) and src[i] == '(':
                prof = 0
                j = i
                while j < len(src):
                    if src[j] == '(':
                        prof += 1
                    elif src[j] == ')':
                        prof -= 1
                        if prof == 0:
                            j += 1
                            break
                    j += 1
                yield m, src[i:j], j
            else:
                yield m, None, m.end()


_MOTIF_DIRECTIVE = _Directive()

DIRECTIVE = re.compile(r'@(forelse|endforeach|endforelse|endphp|endcan|endisset'
                       r'|empty|else|if|elseif|endif|foreach|php|can|isset|unless|endunless'
                       r'|section|endsection|yield|include)\b', re.S)


def developper_foreach(src, ctx, profondeur=0):
    """Développe récursivement les @foreach / @forelse / @php / @if."""
    if profondeur > 40:
        return src

    # @php ... @endphp
    m = re.search(r'@php(.*?)@endphp', src, re.S)
    if m:
        code = m.group(1)
        exec_in_ctx(code, ctx)
        return developper_foreach(src[:m.start()] + src[m.end():], ctx, profondeur + 1)

    # @forelse ($coll as $item) ... @empty ... @endforelse
    m = re.search(r'@forelse\s*\((.*?)\bas\s+(?:\$(\w+)\s*=>\s*)?\$(\w+)\s*\)(.*?)@empty(.*?)@endforelse', src, re.S)
    if m:
        coll_expr = m.group(1) + ' as ' + ('$' + m.group(2) + ' => ' if m.group(2) else '') + '$' + m.group(3)
        corps, vide = m.group(4), m.group(5)
        coll, var, cle = separer_as(coll_expr)
        items = evaluer(coll, ctx)
        try:
            items = list(items)
        except Exception:
            items = []

        rendu = ''
        paires = list(enumerate(items))
        for i, (k, item) in enumerate(paires):
            sous = dict(ctx)
            sous[var] = item
            if cle:
                sous[cle] = k
            sous['loop'] = Obj(iteration=i + 1, index=i, first=i == 0,
                               last=i == len(items) - 1, count=len(items),
                               remaining=len(items) - i - 1, even=(i % 2 == 1), odd=(i % 2 == 0))
            morceau = developper_foreach(corps, sous, profondeur + 1)
            morceau = rendre_conditions(morceau, sous)
            morceau = rendre_expressions(morceau, sous)
            rendu += morceau

        if not items:
            rendu = developper_foreach(vide, ctx, profondeur + 1)

        return developper_foreach(src[:m.start()] + rendu + src[m.end():], ctx, profondeur + 1)

    # @foreach ($coll as $item) ... @endforeach
    m = re.search(r'@foreach\s*\((.*?)\bas\s+(?:\$(\w+)\s*=>\s*)?\$(\w+)\s*\)(.*?)@endforeach', src, re.S)
    if m:
        coll_expr = m.group(1) + ' as ' + ('$' + m.group(2) + ' => ' if m.group(2) else '') + '$' + m.group(3)
        corps = m.group(4)
        coll, var, cle = separer_as(coll_expr)
        items = evaluer(coll, ctx)
        try:
            items = list(items)
        except Exception:
            items = []

        rendu = ''
        paires = list(enumerate(items))
        for i, (k, item) in enumerate(paires):
            sous = dict(ctx)
            sous[var] = item
            if cle:
                sous[cle] = k
            sous['loop'] = Obj(iteration=i + 1, index=i, first=i == 0,
                               last=i == len(items) - 1, count=len(items),
                               remaining=len(items) - i - 1, even=(i % 2 == 1), odd=(i % 2 == 0))
            morceau = developper_foreach(corps, sous, profondeur + 1)
            morceau = rendre_conditions(morceau, sous)
            morceau = rendre_expressions(morceau, sous)
            rendu += morceau

        return developper_foreach(src[:m.start()] + rendu + src[m.end():], ctx, profondeur + 1)

    return src


def separer_as(expr):
    # "$coll as $var"  (éventuellement "$coll as $k => $v")
    m = re.match(r'(.*?)\s+as\s+(?:\$(\w+)\s*=>\s*)?\$(\w+)\s*$', expr, re.S)
    if not m:
        return ('[]', 'x', None)
    coll = m.group(1).strip()
    var = m.group(3)
    cle = m.group(2)
    coll = coll.replace('$', 'V["', 1)
    # Referme la parenthèse de la variable
    coll = re.sub(r'V\["(\w+)', r'V["\1"]', coll)
    return (coll, var, cle)


def convertir_accolades(code):
    """Traduit les blocs PHP `{ ... }` en indentation Python."""
    lignes = code.split('\n')
    sortie = []
    niveau = 0
    for ligne in lignes:
        nu = ligne.strip()
        if nu in ('}', '};', '} else {', '} elseif (', '}'):
            niveau = max(0, niveau - 1)
            if nu in ('};', '}'):
                continue
        if nu.startswith('}'):
            niveau = max(0, niveau - 1)
            nu = nu[1:].strip()
            if not nu:
                continue
        if nu.endswith('{'):
            corps = nu[:-1].strip()
            indent = '    ' * niveau
            if corps.startswith('else'):
                corps = 'else:'
            elif corps.startswith('elseif'):
                corps = corps.replace('elseif', 'elif', 1).rstrip()
                if not corps.endswith(':'):
                    corps += ':'
            else:
                if not corps.endswith(':'):
                    corps += ':'
            sortie.append(indent + corps)
            niveau += 1
            continue
        sortie.append('    ' * niveau + nu)
    return '\n'.join(sortie)


def exec_in_ctx(code, ctx):
    """Exécute un bloc @php (affectations simples de variables de vue)."""
    import textwrap
    code = textwrap.dedent(code)
    # Neutralise les commentaires /* */ et // qui contiennent du texte libre
    code = re.sub(r'/\*.*?\*/', '', code, flags=re.S)
    code = re.sub(r'//[^\n]*', '', code)
    code = re.sub(r'#[^\n\[]*', '', code)
    code = code.replace('->', '.')
    # $tableau[] = valeur  ->  tableau.append(valeur)
    code = re.sub(r'\$(\w+)\[\]\s*=\s*([^;\n]+);', r'ctx["\1"].append(\2);', code)
    # $tableau['clé'] = valeur  ->  tableau['clé'] = valeur
    code = re.sub(r'\$(\w+)\[', r'ctx["\1"][', code)
    code = re.sub(r'\$(\w+)', r'ctx["\1"]', code)
    code = re.sub(r'\btrue\b', 'True', code)
    code = re.sub(r'\bfalse\b', 'False', code)
    code = re.sub(r'\bnull\b', 'None', code)
    code = convertir_accolades(code)

    try:
        exec(code, {'__builtins__': {'len': len, 'str': str, 'int': int,
                                     'float': float, 'round': round, 'min': min,
                                     'max': max, 'abs': abs, 'sorted': sorted,
                                     'range': lambda *a: list(range(*a)), 'list': list, 'dict': dict,
                                     'enumerate': enumerate, 'zip': zip}},
             {'ctx': ctx, 'datetime': datetime, 'date': lambda *a: '21/11/2026',
              'strtotime': lambda *a: 0, 'number_format': lambda *a, **k: '0',
              'urlencode': str, 'count': len, 'trim': lambda s, *a: (s or '').strip(),
              'strtoupper': lambda s: (s or '').upper(),
              'range': lambda *a: list(range(*a)),
              'array_unshift': lambda l, *v: [l.insert(0, x) for x in reversed(v)],
              'array_splice': lambda l, i, n, v: l.insert(i, v[0])})
    except Exception as e:
        print(f'  ⚠ @php ignoré : {e}')


def rendre_conditions(src, ctx):
    """Traite @if / @elseif / @else / @endif de gauche à droite."""
    sortie = []
    pile = []          # liste de True/False/None (None = branche déjà prise)
    pos = 0
    motif = _MOTIF_DIRECTIVE

    for m, cond, fin_directive in motif.finditer(src):
        sortie.append(src[pos:m.start()])
        pos = fin_directive
        kw = m.group(1)
        # condition normalisée : sans les parenthèses englobantes
        if cond and cond.startswith('(') and cond.endswith(')'):
            cond = cond[1:-1]

        actif = all(x is True for x in pile)

        if kw == 'if':
            val = bool(evaluer(cond if cond else '', ctx)) if cond else False
            pile.append(val)
        elif kw == 'unless':
            val = not (bool(evaluer(cond if cond else '', ctx)) if cond else False)
            pile.append(val)
        elif kw == 'elseif':
            if pile:
                if pile[-1] is True:
                    pile[-1] = None
                elif pile[-1] is None:
                    pass
                else:
                    val = bool(evaluer(cond if cond else '', ctx)) if cond else False
                    pile[-1] = val
        elif kw == 'else':
            if pile:
                pile[-1] = None if pile[-1] is True else (True if pile[-1] is False else None)
        elif kw in ('endif', 'endunless', 'endisset', 'endcan'):
            if pile:
                pile.pop()
        elif kw in ('isset', 'can'):
            pile.append(True)   # on affiche le contenu

        # le contenu est ajouté au tour suivant ; ici on ne fait que
        # décider de garder ou non le segment déjà collecté
        if sortie and not actif:
            sortie[-1] = ''

    sortie.append(src[pos:])
    if not all(x is True for x in pile) and sortie:
        sortie[-1] = ''

    # Deuxième passe : on refait proprement en filtrant chaque segment
    res = []
    pile = []
    pos = 0
    for m, cond, fin_directive in motif.finditer(src):
        seg = src[pos:m.start()]
        if all(x is True for x in pile):
            res.append(seg)
        pos = fin_directive
        kw = m.group(1)
        if cond and cond.startswith('(') and cond.endswith(')'):
            cond = cond[1:-1]
        if kw == 'if':
            pile.append(bool(evaluer(cond if cond else '', ctx)) if cond else False)
        elif kw == 'unless':
            pile.append(not (bool(evaluer(cond if cond else '', ctx)) if cond else False))
        elif kw == 'elseif':
            if pile:
                if pile[-1] is True:
                    pile[-1] = None
                elif pile[-1] is None:
                    pass
                else:
                    pile[-1] = bool(evaluer(cond if cond else '', ctx)) if cond else False
        elif kw == 'else':
            if pile:
                pile[-1] = None if pile[-1] is True else (True if pile[-1] is False else None)
        elif kw in ('endif', 'endunless', 'endisset', 'endcan'):
            if pile:
                pile.pop()
        elif kw in ('isset', 'can'):
            pile.append(True)

    if all(x is True for x in pile):
        res.append(src[pos:])
    return ''.join(res)


def rendre_expressions(src, ctx):
    src = re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)
    # @json(...) Blade
    src = re.sub(r'@json\(([^)]*)\)', lambda m: "'{}'", src)
    src = re.sub(r'\{!!\s*(.*?)\s*!!\}',
                 lambda m: str(evaluer(m.group(1), ctx)), src, flags=re.S)
    src = re.sub(r'\{\{\s*(.*?)\s*\}\}',
                 lambda m: str(evaluer(m.group(1), ctx)), src, flags=re.S)
    return src


def traiter_includes(src, ctx, profondeur=0):
    """Développe @include('vue', ['var' => $expr]) avec sa propre portée."""
    if profondeur > 6:
        return src

    motif = re.compile(r"@include\('([^']+)'\s*(?:,\s*\[(.*?)\])?\)", re.S)
    m = motif.search(src)
    if not m:
        return src

    vue, args = m.group(1), m.group(2) or ''
    sous = dict(ctx)

    if args.strip():
        for morceau in re.finditer(r"'([\w.]+)'\s*=>\s*([^,]+)", args):
            nom, expr = morceau.group(1), morceau.group(2).strip()
            sous[nom] = evaluer(expr, ctx)

    chemin = f'resources/views/{vue.replace(".", "/")}.blade.php'
    try:
        contenu = open(chemin, encoding='utf-8').read()
    except FileNotFoundError:
        contenu = ''

    # Les @php du partial doivent s'exécuter : on rend le partial complet
    contenu = re.sub(r'@php(.*?)@endphp', lambda mm: exec_php_bloc(mm.group(1), sous), contenu, flags=re.S)
    contenu = developper_foreach(contenu, sous)
    contenu = rendre_conditions(contenu, sous)
    contenu = rendre_expressions(contenu, sous)

    return traiter_includes(src[:m.start()] + contenu + src[m.end():], ctx, profondeur + 1)


def exec_php_bloc(code, ctx):
    exec_in_ctx(code, ctx)
    return ''


def rendre(src, ctx):
    src = traiter_includes(src, ctx)
    src = re.sub(r'@can\([^)]*\)|@endcan|@csrf|@method\([^)]*\)', '', src)
    src = re.sub(r'@extends\([^)]*\)|@endsection|@stop', '', src)
    src = developper_foreach(src, ctx)
    src = rendre_conditions(src, ctx)
    src = rendre_expressions(src, ctx)
    # résidus de directives
    src = re.sub(r'@[a-zA-Z_]+', '', src)
    return src


# ──────────────────────────── construction ────────────────────────────

LAYOUT = open('resources/views/layout.blade.php', encoding='utf-8').read()

BASE_CTX = dict(
    firstname='Awa', lastname='DOSSOU', fullname='Awa DOSSOU',
    email='awa.dossou@univ.bj', initial='A',
    errors=None,
)


def extraire_section(src, nom):
    m = re.search(r"@section\('" + nom + r"'\)(.*?)@endsection", src, re.S)
    if nom == 'title':
        # le titre peut aussi être en ligne après @section('title')
        m2 = re.search(r"@section\('title'\)\s*(.*?)\s*@endsection", src, re.S)
        return (m2.group(1).strip() if m2 else '') or ''
    return m.group(1) if m else ''


def construire(page, titre, ctx):
    src = open(f'resources/views/{page}.blade.php', encoding='utf-8').read()

    contenu = extraire_section(src, 'content')
    css = extraire_section(src, 'css')
    js = extraire_section(src, 'js')
    titre_page = extraire_section(src, 'title') or titre

    entete = css + extraire_section(LAYOUT, 'css')

    contenu = rendre(contenu, ctx)
    entete = rendre(entete, ctx)
    js = rendre(js, ctx)

    out = LAYOUT
    # enlève les sections du layout
    out = re.sub(r"@section\('title'\).*?@endsection", '', out, flags=re.S)
    out = re.sub(r"@section\('css'\).*?@endsection", '', out, flags=re.S)
    out = re.sub(r"@section\('js'\).*?@endsection", '', out, flags=re.S)
    out = out.replace("@yield('title')", titre_page)
    out = out.replace("@yield('css')", entete)
    out = out.replace("@yield('content')", contenu)
    out = out.replace("@yield('js')", js)

    out = rendre(out, ctx)
    return out


if __name__ == '__main__':
    print('Utiliser les builders ci-dessous.')


# ─────────────────────────── jeux de données ───────────────────────────

def ctx_qualite(tab='doublons'):
    doublons = [
        Obj(nom_norm='ADJOVI', prenoms_norm='MARIE CLAIRE', occurrences=2,
            matricules='20240012, 20240117', dates='2003-05-14 | 2003-05-14'),
        Obj(nom_norm='DOSSOU', prenoms_norm='JEAN PAUL', occurrences=3,
            matricules='20230104, 20230299, 20240056', dates='2002-11-03 | 2002-11-03 | 2002-11-03'),
    ]

    def etu(mat, nom, dn, tel, email, ins=1):
        return dict(matricule=mat, nom=nom, dateNais=dn, sexe='M',
                    tel=tel, email=email, inscriptions=ins)

    detail = [
        dict(nom='ADJOVI', prenoms='MARIE CLAIRE', occurrences=2, etudiants=[
            etu('20240012', 'ADJOVI MARIE CLAIRE', '2003-05-14', '97000001', '', 1),
            etu('20240117', 'ADJOVI MARIE CLAIRE', '2003-05-14', '', '', 1),
        ]),
        dict(nom='DOSSOU', prenoms='JEAN PAUL', occurrences=3, etudiants=[
            etu('20230104', 'DOSSOU JEAN PAUL', '2002-11-03', '97000002', 'jp@x.bj', 2),
            etu('20230299', 'DOSSOU JEAN PAUL', '2002-11-03', '', '', 1),
            etu('20240056', 'DOSSOU JEAN PAUL', '2002-11-03', '97000003', '', 1),
        ]),
    ]

    class Paginateur(list):
        def links(self, *a, **k):
            return '<ul class="pagination"><li class="page-item active"><a class="page-link">1</a></li><li class="page-item"><a class="page-link">2</a></li></ul>'

    incomplets = Paginateur()
    rows = []
    for i, (mat, nom, pre) in enumerate([
        ('20240031', 'AKPOVI', 'Sylvain'),
        ('20240052', 'BADA', 'Rachidatou'),
        ('20240077', 'CHABI', 'Nestor'),
        ('20240091', 'GBAGUIDI', 'Estelle'),
    ]):
        rows.append(Obj(MATRICULE=mat, nomEtu=nom, prenomsEtu=pre, sexeEtu='M',
                        telEtu='' if i % 2 == 0 else '97123456',
                        emailEtu='' if i % 2 == 0 else f'{nom.lower()}@univ.bj'))
    incomplets.extend(rows)

    sansPaiement = Paginateur()
    sp = [
        Obj(MATRICULE='20240103', nomEtu='HOUNKPATIN', prenomsEtu='Boris',
            Libelle_AnEtude='Licence 1', ANNEE_ACAD='2025-2026', reste_paye=250000),
        Obj(MATRICULE='20240158', nomEtu='KPOSSOU', prenomsEtu='Ariane',
            Libelle_AnEtude='Licence 2', ANNEE_ACAD='2025-2026', reste_paye=180000),
        Obj(MATRICULE='20240222', nomEtu='LAWANI', prenomsEtu='Farid',
            Libelle_AnEtude='Master 1', ANNEE_ACAD='2025-2026', reste_paye=320000),
    ]
    sansPaiement.extend(sp)

    stats = dict(total=1248, sans_photo=87, sans_tel=142, sans_email=311,
                 sans_date=18, sans_sexe=4, tauxComplet=85.6)

    return dict(BASE_CTX, tab=tab, stats=stats,
                doublons=Obj(_rows=doublons), doublonsDetail=detail,
                champ='photo', incomplets=incomplets, sansPaiement=sansPaiement)


class DictSomme(dict):
    def sum(self, *a, **k):
        return sum(self.values())


def ctx_journal():
    diff1 = [
        {'champ': 'reste_paye', 'avant': '250 000', 'apres': '150 000'},
        {'champ': 'S_Finance', 'avant': 'NON', 'apres': 'NON'},
    ]
    diff2 = [
        {'champ': 'telEtu', 'avant': '—', 'apres': '97112233'},
        {'champ': 'emailEtu', 'avant': '—', 'apres': 'a.dossou@univ.bj'},
    ]

    audits = []
    for i, (ev, ton, lib, modele, aid, user, url, diff) in enumerate([
        ('created', 'ok', 'Création', 'Paiement', 8421, 'Awa DOSSOU',
         'http://localhost/paiements', []),
        ('updated', 'info', 'Modification', 'Inscription', '20240012',
         'Cyrille AGBODJAN', 'http://localhost/inscriptions', diff1),
        ('updated', 'info', 'Modification', 'Étudiant', '20240052',
         'Awa DOSSOU', 'http://localhost/etudiants', diff2),
        ('deleted', 'bad', 'Suppression', 'Note', 991, 'Cyrille AGBODJAN',
         'http://localhost/notes', []),
    ]):
        audits.append(Obj(
            event=ev, evenement_libelle=lib, evenement_ton=ton,
            modele_libelle=modele, auditable_id=aid, user_nom=user,
            created_at=datetime.datetime(2026, 11, 20 - i, 14, 30 - i * 7),
            moment_lisible=f'2{i}/11/2026 à 1{4 - i}:3{i}0',
            icone=['add-line', 'edit-line', 'edit-line', 'delete-bin-line'][i],
            ip_address='102.64.190.' + str(12 + i), url=url, diff=diff,
        ))

    class Paginateur(list):
        def total(self):
            return max(len(self), 1)

        def currentPage(self):
            return 1

        def lastPage(self):
            return 3

        def firstItem(self):
            return 1

        def lastItem(self):
            return len(self)

        def previousPageUrl(self):
            return '?page=1'

        def nextPageUrl(self):
            return '?page=2'

        def url(self, p):
            return '?page=' + str(p)

        def links(self, *a, **k):
            return ''

    paginateur = Paginateur(audits)

    utilisateurs = [
        Obj(id=1, name='Awa DOSSOU', email='awa@univ.bj'),
        Obj(id=2, name='Cyrille AGBODJAN', email='cyr@univ.bj'),
    ]

    evenements = {
        'created': ['Création', 'ok'],
        'updated': ['Modification', 'info'],
        'deleted': ['Suppression', 'bad'],
        'restored': ['Restauration', 'ok'],
    }
    modeles = {
        'App\\Models\\Etudiant': 'Étudiant',
        'App\\Models\\Inscription': 'Inscription',
        'App\\Models\\Paiement': 'Paiement',
        'App\\Models\\Note': 'Note',
    }

    resume = {'created': 512, 'updated': 1874, 'deleted': 23}

    return dict(BASE_CTX, audits=paginateur, utilisateurs=utilisateurs,
                resume=DictSomme(resume),
                userId=None, event=None, modele=None,
                du=None, au=None, q='', modeles=modeles, evenements=evenements)


def ctx_imports():
    types = {
        'inscriptions': 'Inscriptions + étudiants',
        'etudiants': 'Étudiants',
        'ues': "Unités d'enseignement (UE)",
    }
    return dict(BASE_CTX, types=types)


def ctx_apercu():
    valides = []
    noms = [('AKPOVI', 'Sylvain'), ('BADA', 'Rachidatou'), ('CHABI', 'Nestor'),
            ('DOSSOU', 'Mireille'), ('GBAGUIDI', 'Estelle'), ('HOUNKPATIN', 'Boris')]
    for i, (n, p) in enumerate(noms):
        valides.append({
            '_ligne': i + 2, 'matricule': 20240300 + i, 'nom': n, 'prenoms': p,
            'anneeEtude': 'L1', 'montant': 250000,
        })

    erreurs = [
        {'ligne': 5, 'matricule': '20240304', 'nom': 'FOLLY',
         'motifs': ["Année académique inconnue (« 2026 »).", 'Statut manquant.']},
        {'ligne': 9, 'matricule': '', 'nom': 'KOUASSI',
         'motifs': ['Matricule manquant.', "Type d'étudiant inconnu (« Interne »)."]},
        {'ligne': 12, 'matricule': '20240311', 'nom': 'SEGBO',
         'motifs': ["Date de naissance illisible (« 31/02/2003 »)."]},
    ]

    avertissements = [
        {'ligne': 3, 'matricule': '20240301', 'nom': 'AKPOVI',
         'motifs': ['Téléphone absent.']},
        {'ligne': 8, 'matricule': '20240306', 'nom': 'HOUNKPATIN',
         'motifs': ['Date de naissance absente.']},
    ]

    rapport = dict(cle='x', type='inscriptions', valides=valides, erreurs=erreurs,
                   avertissements=avertissements, total=14,
                   entetes=['a'] * 17)

    return dict(BASE_CTX, rapport=rapport, typeLibelle='Inscriptions + étudiants')


if __name__ == '__main__':
    pages = [
        ('qualite-donnees', 'Qualité des données', ctx_qualite('doublons')),
        ('journal-activite', "Journal d'activité", ctx_journal()),
        ('imports', 'Import de données', ctx_imports()),
        ('imports-apercu', "Aperçu de l'import", ctx_apercu()),
    ]
    for page, titre, ctx in pages:
        html = construire(page, titre, ctx)
        chemin = f'public/preview-{page}.html'
        open(chemin, 'w', encoding='utf-8').write(html)
        print(f'{chemin:45s} {len(html):>7} octets')
