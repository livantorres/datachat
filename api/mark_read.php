<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$conversation_id = $data['conversation_id'] ?? 0;

if ($conversation_id) {
    // Mark messages as read where I am NOT the sender
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0");
    $stmt->execute([$conversation_id, $user_id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
