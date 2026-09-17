<?php
require_once 'includes/db.php';

// Obtener la URL solicitada
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Obtener el directorio base (en este caso /datachat o / dependiendo de la configuración de laragon)
$script_name = dirname($_SERVER['SCRIPT_NAME']);
if ($script_name !== '/') {
    $request_uri = str_replace($script_name, '', $request_uri);
}

// Limpiar la ruta
$path = trim($request_uri, '/');

// Rutas permitidas y archivos correspondientes
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
    require $routes[$path];
} else {
    // Página 404
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
    echo "<p>La página solicitada no existe.</p>";
}
