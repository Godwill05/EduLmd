<?php
error_reporting(E_ALL & ~E_DEPRECATED);
$racine = '/home/user/EduLmd';
chdir($racine);
require $racine . '/vendor/autoload.php';
$app = require $racine . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = [];
foreach (Illuminate\Support\Facades\Route::getRoutes() as $r) {
    $mw = array_values(array_filter($r->gatherMiddleware(), fn($m) => !in_array($m, ['web'], true)));
    $routes[] = [
        'method' => implode('|', array_diff($r->methods(), ['HEAD'])),
        'uri' => $r->uri(),
        'name' => $r->getName(),
        'action' => $r->getActionName(),
        'middleware' => $mw,
    ];
}
echo json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
