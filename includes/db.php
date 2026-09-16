<?php
session_start();
// Prevent session locking during long SSE requests if we ever use this connection in a script that doesn't need to write to session.
// In normal scripts, we don't close it immediately.

$db_host = 'localhost';
$db_name = 'datasis_datachat';
$db_user = 'root'; // Change if necessary on production
$db_pass = '';     // Change if necessary on production

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch objects by default for easier syntax ($row->name instead of $row['name'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch(PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Helper function to get institutional data
function getInstData($pdo) {
    $stmt = $pdo->query("SELECT * FROM datos_institucionales LIMIT 1");
    $data = $stmt->fetch();
    if (!$data) {
        $data = (object)[
            'app_name' => 'DataChat',
            'company_name' => 'Mi Empresa',
            'logo' => null
        ];
    }
    return $data;
}

$inst_data = getInstData($pdo);
?>
