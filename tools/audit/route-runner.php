<?php
/**
 * route-runner.php — harnais d'audit : rejoue de vraies requêtes HTTP sur le
 * noyau Laravel, sans serveur web.
 *
 * PHP-WASM n'expose pas de serveur HTTP ; ce script construit des objets
 * Request Symfony/Laravel, les fait traverser le noyau, puis sérialise le
 * résultat en JSON (statut, en-têtes, corps, exceptions).
 *
 * Un même processus enchaîne les requêtes : la session et les cookies sont
 * donc conservés, ce qui permet de se connecter puis de tester les routes
 * protégées par le middleware auth.
 *
 * Usage :
 *   php route-runner.php scenario.json > resultat.json
 *
 * Format du scénario :
 *   [
 *     {"id":"accueil", "method":"GET", "uri":"/"},
 *     {"id":"login",   "method":"POST","uri":"/login",
 *      "data":{"email":"...","password":"..."}},
 *     {"id":"fiche",   "method":"GET", "uri":"/etudiants/fiche/20015926"}
 *   ]
 */

/* Les dépendances du framework génèrent des dépréciations PHP 8.4/8.5 qui
   pollueraient la sortie JSON : on les tait, mais on garde les vraies erreurs. */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

$racine = '/home/user/EduLmd';
chdir($racine);

require $racine . '/vendor/autoload.php';

/* Une étape portant "nouvelle_session": true repart d'un cookie jar vide
   (utile pour tester les connexions successives ou vérifier qu'une route
   protégée soit bien inaccessible sans authentification). */
$scenario = json_decode(file_get_contents($argv[1]), true);

$app = require $racine . '/bootstrap/app.php';
$noyau = $app->make(Illuminate\Contracts\Http\Kernel::class);

/* ─── Jar de cookies partagé entre les requêtes ─────────────────────────── */

$cookies = [];
$jetonCsrf = null;

function entetes_requete(array $cookies, array $extra = []): array
{
    $lignes = [];
    if ($cookies) {
        $paires = [];
        foreach ($cookies as $nom => $valeur) {
            $paires[] = $nom . '=' . $valeur;
        }
        $lignes['HTTP_COOKIE'] = implode('; ', $paires);
    }
    return array_merge($lignes, $extra);
}

function memoriser_cookies($reponse, array &$cookies): void
{
    foreach ($reponse->headers->getCookies() as $cookie) {
        $nom = $cookie->getName();
        $valeur = $cookie->getValue();
        if ($valeur === null || $valeur === '' || $cookie->getExpiresTime() === 0) {
            unset($cookies[$nom]);
        } else {
            $cookies[$nom] = $valeur;
        }
    }
}

/* ─── Exécution d'une requête ───────────────────────────────────────────── */

function extraire_jeton(string $corps): ?string
{
    /* Laravel place le jeton dans <input name="_token">, la meta csrf-token
       ou l'en-tête XSRF-TOKEN (cookie chiffré). */
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $corps, $m)) {
        return $m[1];
    }
    if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $corps, $m)) {
        return $m[1];
    }
    return null;
}

function executer(array $etape, $app, $noyau, array &$cookies, ?string &$jetonCsrf): array
{
    $methode = strtoupper($etape['method'] ?? 'GET');
    $uri = $etape['uri'] ?? '/';
    $donnees = $etape['data'] ?? [];
    $json = $etape['json'] ?? null;

    /* Le jeton CSRF de la session courante est ajouté automatiquement :
       sans lui, toutes les écritures répondent 419. */
    if (in_array($methode, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $jetonCsrf) {
        if ($json !== null) {
            $json['_token'] = $jetonCsrf;
        } else {
            $donnees['_token'] = $jetonCsrf;
        }
    }

    parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

    $serveur = entetes_requete($cookies, [
        'HTTP_HOST' => 'localhost',
        'HTTPS' => '',
        'SERVER_PORT' => '8000',
        'SERVER_NAME' => 'localhost',
        'REMOTE_ADDR' => '127.0.0.1',
        'SCRIPT_NAME' => '/index.php',
        'SCRIPT_FILENAME' => '/home/user/EduLmd/public/index.php',
        'DOCUMENT_ROOT' => '/home/user/EduLmd/public',
        'REQUEST_URI' => $uri,
        'REQUEST_METHOD' => $methode,
        'QUERY_STRING' => (string) parse_url($uri, PHP_URL_QUERY),
        'HTTP_ACCEPT' => $json ? 'application/json' : 'text/html,application/xhtml+xml',
        'HTTP_USER_AGENT' => 'EduLMD-Audit/1.0',
        'HTTP_X_REQUESTED_WITH' => $json ? 'XMLHttpRequest' : '',
        'CONTENT_TYPE' => $json ? 'application/json' : 'application/x-www-form-urlencoded',
    ]);

    $requete = Illuminate\Http\Request::createFromGlobals(
        $serveur,
    );
    /* createFromGlobals ne lit pas les tableaux passés en argument de façon
       fiable selon les versions : on reconstruit la requête explicitement. */
    $requete = Illuminate\Http\Request::create(
        $uri,
        $methode,
        $methode === 'GET' ? $query : $donnees,
        $query,
        [],
        $serveur,
        $json !== null ? json_encode($json) : null
    );

    if ($json !== null) {
        $requete->headers->set('Content-Type', 'application/json');
        $requete->headers->set('Accept', 'application/json');
        $requete->headers->set('X-Requested-With', 'XMLHttpRequest');
    }

    $debut = microtime(true);
    $erreur = null;
    $statut = 0;
    $corps = '';
    $entetes = [];

    try {
        $reponse = $noyau->handle($requete);
        $statut = $reponse->getStatusCode();
        $corps = $reponse->getContent();
        foreach ($reponse->headers->all() as $nom => $valeurs) {
            $entetes[$nom] = $valeurs;
        }
        memoriser_cookies($reponse, $cookies);
        $noyau->terminate($requete, $reponse);
    } catch (Throwable $e) {
        $erreur = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'fichier' => str_replace('/home/user/EduLmd/', '', $e->getFile()),
            'ligne' => $e->getLine(),
        ];
        $statut = 500;
    }

    $duree = round((microtime(true) - $debut) * 1000);

    if (is_string($corps)) {
        $nouveau = extraire_jeton($corps);
        if ($nouveau) {
            $jetonCsrf = $nouveau;
        }
    }

    /* Les réponses HTML sont résumées : titre, taille, indices d'erreur. */
    $titre = null;
    $indices = [];
    if (stripos($corps, '<html') !== false || stripos($corps, '<!DOCTYPE') !== false) {
        if (preg_match('#<title>(.*?)</title>#is', $corps, $m)) {
            $titre = trim(html_entity_decode($m[1]));
        }
        foreach (['Whoops', 'ErrorException', 'SQLSTATE', 'Undefined variable',
                  'Undefined array key', 'Attempt to read property',
                  'Call to a member function', 'Too few arguments',
                  'Trying to access array offset', 'htmlspecialchars()'] as $motif) {
            if (stripos($corps, $motif) !== false) {
                $indices[] = $motif;
            }
        }
    }

    return [
        'id' => $etape['id'] ?? $uri,
        'method' => $methode,
        'uri' => $uri,
        'statut' => $statut,
        'duree_ms' => $duree,
        'titre' => $titre,
        'octets' => strlen($corps),
        'indices_erreur' => $indices,
        'erreur' => $erreur,
        'extrait' => $json || strlen($corps) < 400
            ? substr($corps, 0, 900)
            : null,
        'corps' => strlen($corps) < 60000 ? $corps : substr($corps, 0, 60000),
        'entetes' => array_intersect_key($entetes, array_flip(['content-type', 'location', 'set-cookie'])),
    ];
}

/* ─── Boucle ────────────────────────────────────────────────────────────── */

$resultats = [];

/* Première requête « d'amorçage » : elle dépose le cookie de session et
   fournit un jeton CSRF utilisable par les POST suivants. */
$amorce = executer(['id' => '__amorce', 'method' => 'GET', 'uri' => '/'],
    $app, $noyau, $cookies, $jetonCsrf);

foreach ($scenario as $etape) {
    if (!empty($etape['nouvelle_session'])) {
        $cookies = [];
        $jetonCsrf = null;
        $resultats[] = executer(['id' => '__amorce', 'method' => 'GET', 'uri' => '/'],
            $app, $noyau, $cookies, $jetonCsrf);
    }
    $resultats[] = executer($etape, $app, $noyau, $cookies, $jetonCsrf);
}
echo json_encode($resultats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
