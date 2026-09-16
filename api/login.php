<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_number = $_POST['document_number'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($document_number) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Llene todos los campos']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE document_number = ?");
    $stmt->execute([$document_number]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user->password)) {
        // Set sessions
        $_SESSION['user_id'] = $user->id;
        $_SESSION['role'] = $user->role;
        $_SESSION['name'] = $user->name;

        // Update status to online and last_seen
        $update = $pdo->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE id = ?");
        $update->execute([$user->id]);

        echo json_encode(['success' => true, 'message' => 'Login exitoso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Documento o contraseña incorrectos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
