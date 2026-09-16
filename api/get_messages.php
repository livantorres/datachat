<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$conversation_id = $_GET['conversation_id'] ?? 0;

// Verify user is part of the conversation
$check = $pdo->prepare("SELECT 1 FROM conversation_users WHERE conversation_id = ? AND user_id = ?");
$check->execute([$conversation_id, $user_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No perteneces a este chat']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.conversation_id = ? 
    ORDER BY m.id ASC
");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $messages]);
?>
