<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $document_number = $_POST['document_number'] ?? '';
    $phone = $_POST['phone'] ?? null;
    if (empty($phone)) $phone = null;
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($document_number) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Llene los campos obligatorios']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // is_active = 0 para nuevos registros públicos
        $stmt = $pdo->prepare("INSERT INTO users (name, document_number, phone, email, password, is_active) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$name, $document_number, $phone, $email, $hash]);
        
        echo json_encode(['success' => true, 'message' => 'Cuenta creada. Un administrador debe activarla.']);
    } catch(PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            echo json_encode(['success' => false, 'message' => 'El documento, correo o teléfono ya están registrados']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()]);
        }
    }
}
