<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$message_id = $data['message_id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$message_id]);
$msg = $stmt->fetch();

if (!$msg) {
    echo json_encode(['success' => false, 'message' => 'Mensaje no encontrado']);
    exit;
}

if ($msg->sender_id != $user_id) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso']);
    exit;
}

// -------------------------------------------------------------
// TIEMPO LÍMITE PARA ELIMINAR UN MENSAJE (En horas)
// Por defecto 24 horas. Puedes cambiar este valor aquí en cualquier momento.
$delete_time_limit_hours = 24; 
// -------------------------------------------------------------

$created_at = new DateTime($msg->created_at);
$now = new DateTime();
$diff = $now->diff($created_at);
$hours = $diff->h + ($diff->days * 24) + ($diff->i / 60);

if ($hours > $delete_time_limit_hours) {
    echo json_encode(['success' => false, 'message' => "Ya no puedes eliminar este mensaje. El límite es de $delete_time_limit_hours horas."]);
    exit;
}

// Delete from DB
if ($msg->attachment) {
    $filepath = __DIR__ . '/../uploads/attachments/' . $msg->attachment;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

$pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([$message_id]);

echo json_encode(['success' => true]);
?>
