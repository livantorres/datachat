<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

// Recibir token POST como JSON
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Token de Google no recibido']);
    exit;
}

// Para validación simple (sin la librería PHP de Google) utilizamos el endpoint de tokeninfo
// NOTA: Para producción es más seguro instalar 'google/apiclient' mediante Composer.
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
$response = @file_get_contents($url);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar el token con Google']);
    exit;
}

$payload = json_decode($response, true);

if (isset($payload['error'])) {
    echo json_encode(['success' => false, 'message' => 'Token de Google inválido']);
    exit;
}

// Datos proporcionados por Google
$email = $payload['email'] ?? '';
$name = $payload['name'] ?? 'Usuario Google';
$google_id = $payload['sub'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el correo de la cuenta de Google']);
    exit;
}

// Verificar si el usuario ya existe por email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // El usuario existe. Comprobar si está activo
    if ($user->is_active == 0 && $user->role !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Tu cuenta está inactiva. Espera a que un administrador la habilite.', 'is_pending' => false]);
        exit;
    }

    // Login exitoso
    $_SESSION['user_id'] = $user->id;
    $_SESSION['role'] = $user->role;
    $_SESSION['name'] = $user->name;

    // Actualizar última conexión
    $pdo->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE id = ?")->execute([$user->id]);

    echo json_encode(['success' => true]);
} else {
    // El usuario NO existe. Se registra automáticamente como Inactivo
    try {
        // Documento y Password se rellenan con el ID de google al ser campos NOT NULL
        $document = 'G-' . substr($google_id, 0, 15); 
        $hash = password_hash(random_bytes(10), PASSWORD_DEFAULT); // Contraseña aleatoria, ingresarán por Google
        
        $insert = $pdo->prepare("INSERT INTO users (name, document_number, email, password, is_active) VALUES (?, ?, ?, ?, 0)");
        $insert->execute([$name, $document, $email, $hash]);

        echo json_encode([
            'success' => false, 
            'is_pending' => true,
            'message' => 'Tu cuenta ha sido creada exitosamente usando Google. Un administrador debe activarla antes de que puedas ingresar.'
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al registrar mediante Google: ' . $e->getMessage()]);
    }
}
?>
