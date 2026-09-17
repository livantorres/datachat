<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = $_POST['login_id'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Llene todos los campos']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE document_number = ? OR email = ? OR phone = ?");
    $stmt->execute([$login_id, $login_id, $login_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user->password)) {
        if ($user->is_active == 0 && $user->role !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Tu cuenta está inactiva. Espera a que un administrador la habilite.']);
            exit;
        }

        // Set sessions
        $_SESSION['user_id'] = $user->id;
        $_SESSION['role'] = $user->role;
        $_SESSION['name'] = $user->name;

        // Update status to online and last_seen
        $update = $pdo->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE id = ?");
        $update->execute([$user->id]);

        echo json_encode(['success' => true, 'message' => 'Login exitoso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos incorrectos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
