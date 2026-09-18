<?php
require_once '../includes/db.php';
// Important: Close session to prevent locking other AJAX requests!
session_write_close();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'auth_error']);
    exit;
}

$user_id = $_SESSION['user_id'];
$last_message_id = isset($_GET['last_msg']) ? (int)$_GET['last_msg'] : 0;

// Update my own last_seen to keep me online
function updateMyStatus($pdo, $uid) {
    try {
        $pdo->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE id = ?")->execute([$uid]);
    } catch (Exception $e) {}
}

// Update my status on every poll request
updateMyStatus($pdo, $user_id);

$response = [
    'events' => [],
    'last_msg' => $last_message_id
];

// 1. Check for new messages
if ($last_message_id > 0) {
    $sql = "
        SELECT m.*, c.type as chat_type, c.name as group_name
        FROM messages m
        JOIN conversation_users cu ON m.conversation_id = cu.conversation_id
        JOIN conversations c ON m.conversation_id = c.id
        WHERE cu.user_id = :user_id AND m.id > :last_id AND m.sender_id != :user_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'last_id' => $last_message_id]);
    $new_messages = $stmt->fetchAll();

    foreach ($new_messages as $msg) {
        $response['events'][] = [
            'type' => 'new_message',
            'data' => $msg
        ];
        $last_message_id = max($last_message_id, $msg->id);
    }
} else {
    // If 0, just find the max ID to start tracking
    $sql = "
        SELECT MAX(m.id) as max_id
        FROM messages m
        JOIN conversation_users cu ON m.conversation_id = cu.conversation_id
        WHERE cu.user_id = :user_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $max = $stmt->fetch();
    $last_message_id = $max->max_id ?? 0;
}

$response['last_msg'] = $last_message_id;

// 2. Check for read receipts
$sql_reads = "
    SELECT conversation_id, MAX(id) as max_read_id
    FROM messages
    WHERE sender_id = :user_id AND is_read = 1
    GROUP BY conversation_id
";
$stmt_reads = $pdo->prepare($sql_reads);
$stmt_reads->execute(['user_id' => $user_id]);
$read_receipts = $stmt_reads->fetchAll();

$response['events'][] = [
    'type' => 'read_receipts',
    'data' => $read_receipts
];

// 3. Online Users Tracking
$stmt_online = $pdo->prepare("SELECT id FROM users WHERE last_seen > DATE_SUB(NOW(), INTERVAL 15 SECOND)");
$stmt_online->execute();
$current_online_users = $stmt_online->fetchAll(PDO::FETCH_COLUMN);

$response['events'][] = [
    'type' => 'online_status',
    'data' => $current_online_users
];

echo json_encode($response);
?>
