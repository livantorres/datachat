<?php
require_once __DIR__ . '/../includes/db.php';

// Calcular la ruta base de la aplicación respecto al Document Root
$base_dir = str_replace('\\', '/', dirname(__DIR__)); // Ej. C:/laragon/www/datachat
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']); // Ej. C:/laragon/www
$app_path = str_replace($doc_root, '', $base_dir); // Ej. /datachat

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remover el app_path de la URI para quedarnos solo con la ruta relativa
if (!empty($app_path) && strpos($request_uri, $app_path) === 0) {
    $path = substr($request_uri, strlen($app_path));
} else {
    $path = $request_uri;
}
$path = trim($path, '/');

// Rutas permitidas y archivos correspondientes (ubicados en el directorio raíz)
$routes = [
    '' => 'index.php',
    'index' => 'index.php',
    'chat' => 'chat.php',
    'admin' => 'admin.php',
    'profile' => 'profile.php'
];

if (array_key_exists($path, $routes)) {
    // Definimos una constante para saber que estamos pasando por el router
    define('ROUTER_LOADED', true);
    require __DIR__ . '/../views/' . $routes[$path];
} else {
    // Página 404
    http_response_code(404);
    require __DIR__ . '/../views/404.php';
}
