<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$search = $_GET['q'] ?? '';

$sql = "SELECT id, name, avatar, status, last_seen FROM users WHERE id != ?";
$params = [$user_id];

if ($search) {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}

$sql .= " ORDER BY name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $users]);
?>
