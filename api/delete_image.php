<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$type = $_POST['type'] ?? '';

if ($type === 'logo') {
    if ($_SESSION['role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $inst_data = getInstData($pdo);
    if ($inst_data->logo) {
        $file_path = '../uploads/logos/' . $inst_data->logo;
        if (file_exists($file_path)) @unlink($file_path);
        
        $pdo->query("UPDATE datos_institucionales SET logo = NULL");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay logo para eliminar']);
    }
} elseif ($type === 'avatar') {
    $user_id = $_POST['user_id'] ?? $_SESSION['user_id'];
    
    // Solo el superadmin puede borrar avatares de otros, o el propio usuario
    if ($_SESSION['role'] !== 'superadmin' && $user_id != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user->avatar && $user->avatar !== 'default.png') {
        $file_path = '../uploads/avatars/' . $user->avatar;
        if (file_exists($file_path)) @unlink($file_path);
        
        $pdo->prepare("UPDATE users SET avatar = 'default.png' WHERE id = ?")->execute([$user_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay avatar para eliminar o es el por defecto']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
}
