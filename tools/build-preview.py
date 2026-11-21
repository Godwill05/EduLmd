#!/usr/bin/env python3
"""
Génère public/preview-*.html à partir des vrais fichiers Blade,
pour vérification visuelle uniquement (jamais committé en prod).
Mini-évaluateur de directives Blade : @if/@elseif/@else/@endif,
@can/@endcan, @php/@endphp, {{ }} et {!! !!}.
"""
import re
import sys
import datetime

VARS = {}


def strip_php(expr):
    expr = expr.strip()
    expr = expr.replace('true', 'True').replace('false', 'False').replace('null', 'None')
    return expr


def eval_php(expr, ctx):
    """Évalue une expression PHP simple avec les valeurs du contexte."""
    expr = strip_php(expr)
    # Auth::user()->... -> ctx
    expr = re.sub(r"Auth::user\(\)->firstname", repr(ctx['firstname']), expr)
    expr = re.sub(r"Auth::user\(\)->lastname", repr(ctx['lastname']), expr)
    expr = re.sub(r"Auth::user\(\)->fullname\(\)", repr(ctx['fullname']), expr)
    expr = re.sub(r"Auth::user\(\)->email \?\? ''", repr(ctx['email']), expr)
    expr = re.sub(r"substr\([^,]+,\s*0,\s*1\)", repr(ctx['initial']), expr)
    expr = re.sub(r"number_format\([^)]*\)", repr('0'), expr)
    expr = re.sub(r"\\Carbon\\Carbon::now\(\)->locale\('fr'\)->isoFormat\([^)]*\)",
                  repr(ctx['date']), expr)
    expr = re.sub(r"now\(\)->year", repr(ctx['year']), expr)
    expr = re.sub(r"session\([^)]*\)", repr(None), expr)
    expr = re.sub(r"request\([^)]*\)", repr(None), expr)
    expr = re.sub(r"csrf_token\(\)", repr('token'), expr)
    expr = re.sub(r"old\([^)]*\)", repr(''), expr)
    expr = re.sub(r"route\('([^']+)'\)", r"'#\1'", expr)
    expr = re.sub(r"asset\('([^']*)'\)", r"'\1'", expr)
    expr = expr.replace('asset("', "'").replace('")', "'")
    # Opérateurs PHP -> Python
    expr = expr.replace('->', '.')
    expr = re.sub(r'(\$\w+)', r'ctx.get("\1", "")', expr)
    expr = re.sub(r'\bctx\.get\("(\$\w+)", ""\)\.', r'ctx["\1"].', expr)
    try:
        return eval(expr, {'ctx': ctx, '__builtins__': {}})
    except Exception:
        return ''


def render_conditionals(src, ctx):
    """Traite @if/@elseif/@else/@endif de façon récursive (sans imbrication complexe)."""
    out = []
    # pile de blocs : [etat_actif, deja_pris]
    stack = []  # chaque élément: {'taken': bool, 'active': bool}
    pos = 0
    pattern = re.compile(r"@(if|elseif|else|endif)\b\s*(\([^\n]*?\))?", re.S)

    def active():
        return all(b['active'] for b in stack)

    for m in pattern.finditer(src):
        out.append(src[pos:m.start()])
        pos = m.end()
        keyword, cond = m.group(1), m.group(2)
        if keyword == 'if':
            cond_expr = cond[1:-1] if cond else ''
            val = bool(eval_php(cond_expr, ctx))
            stack.append({'taken': val, 'active': val})
        elif keyword == 'elseif':
            cond_expr = cond[1:-1] if cond else ''
            if stack:
                top = stack[-1]
                if top['taken']:
                    top['active'] = False
                else:
                    val = bool(eval_php(cond_expr, ctx))
                    top['active'] = val
                    top['taken'] = top['taken'] or val
        elif keyword == 'else':
            if stack:
                top = stack[-1]
                top['active'] = not top['taken']
                top['taken'] = True
        elif keyword == 'endif':
            if stack:
                stack.pop()
        if not active():
            # on ignore le contenu jusqu'à la prochaine directive
            pass
    out.append(src[pos:])

    # filtrage : ne garder que les morceaux actifs -> on refait le parcours proprement
    result = []
    seg_pos = 0
    stack = []
    for m in pattern.finditer(src):
        seg = src[seg_pos:m.start()]
        if all(b['active'] for b in stack):
            result.append(seg)
        seg_pos = m.end()
        keyword, cond = m.group(1), m.group(2)
        if keyword == 'if':
            val = bool(eval_php(cond[1:-1] if cond else '', ctx))
            stack.append({'taken': val, 'active': val})
        elif keyword == 'elseif':
            if stack:
                top = stack[-1]
                if top['taken']:
                    top['active'] = False
                else:
                    val = bool(eval_php(cond[1:-1] if cond else '', ctx))
                    top['active'] = val
                    top['taken'] = top['taken'] or val
        elif keyword == 'else':
            if stack:
                top = stack[-1]
                top['active'] = not top['taken']
                top['taken'] = True
        elif keyword == 'endif':
            if stack:
                stack.pop()
    if all(b['active'] for b in stack):
        result.append(src[seg_pos:])
    return ''.join(result)


def render(src, ctx):
    # @can / @endcan -> tout inclure (aperçu)
    src = re.sub(r"@can\([^)]*\)", "", src)
    src = src.replace("@endcan", "")
    # @php ... @endphp -> retirer
    src = re.sub(r"@php.*?@endphp", "", src, flags=re.S)
    src = re.sub(r"@isset\([^)]*\)", "", src)
    src = src.replace("@endisset", "")
    # @error('champ') ... @enderror : en l'absence d'erreur, tout le bloc disparaît
    src = re.sub(r"@error\([^)]*\)(.*?)@enderror", "", src, flags=re.S)
    # résidus de parenthèses laissés par les directives supprimées
    src = re.sub(r"\('(?:email|password|name|code|libelle)[^)]*\)\s*is-invalid", "is-invalid", src)
    # conditions
    src = render_conditionals(src, ctx)
    # expressions
    src = re.sub(r"\{\{--.*?--\}\}", "", src, flags=re.S)
    src = re.sub(r"\{!!\s*(.*?)\s*!!\}", lambda m: str(eval_php(m.group(1), ctx)), src, flags=re.S)
    src = re.sub(r"\{\{\s*(.*?)\s*\}\}", lambda m: str(eval_php(m.group(1), ctx)), src, flags=re.S)
    # résidus
    src = re.sub(r"@(yield|extends|section|endsection|include|csrf|method)\([^)]*\)", "", src)
    return src


CTX = dict(
    firstname='Awa', lastname='DOSSOU', fullname='Awa DOSSOU',
    email='awa.dossou@univ.bj', initial='A',
    date='vendredi 21 novembre 2026', year='2026',
)


def build_dashboard():
    layout = open('resources/views/layout.blade.php', encoding='utf-8').read()
    accueil = open('resources/views/accueil.blade.php', encoding='utf-8').read()

    content = accueil.split("@section('content')", 1)[1]
    content = content.rsplit("@endsection", 1)[0]
    content_html = render(content, dict(CTX))

    # compteurs fictifs
    content_html = re.sub(r"\$\{?[^}]*\}", "", content_html)
    for pat, val in [
        (r"\b0\b(?=[^0-9]*</div>\s*<div class=\"kpi-label\">Étudiants)", '1 248'),
    ]:
        pass
    content_html = content_html.replace('>0</div>\n            <div class="kpi-label">Étudiants', '>1 248</div>\n            <div class="kpi-label">Étudiants')

    # on force les compteurs dans l'ordre des KPI
    vals = iter(['1 248', '3 106', '87', '142'])
    def repl_kpi(m):
        return m.group(1) + next(vals, '0') + m.group(2)
    content_html = re.sub(r'(<div class="kpi-value">).*?(</div>)', repl_kpi, content_html, count=4)

    out = layout
    out = out.replace("@yield('title')", "Accueil")
    out = out.replace("@yield('content')", content_html)
    out = out.replace("@yield('css')", "")
    out = out.replace("@yield('js')", "")
    out = render(out, dict(CTX))
    out = re.sub(r"@\w+", "", out)  # sécurité
    # état actif de l'aperçu : Accueil
    out = out.replace('class="nav-link " href="#home"',
                      'class="nav-link active" href="#home"')
    open('public/preview-dashboard.html', 'w', encoding='utf-8').write(out)
    return len(out)


def build_login():
    login = open('resources/views/login.blade.php', encoding='utf-8').read()
    out = render(login, dict(CTX))
    out = re.sub(r"@\w+", "", out)
    open('public/preview-login.html', 'w', encoding='utf-8').write(out)
    return len(out)


if __name__ == '__main__':
    print('dashboard:', build_dashboard())
    print('login:', build_login())
