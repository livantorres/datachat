<?php
require_once '../includes/db.php';
// Important: Close session to prevent locking other AJAX requests!
session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // For Nginx

if (!isset($_SESSION['user_id'])) {
    echo "data: {\"type\": \"auth_error\"}\n\n";
    flush();
    exit;
}

$user_id = $_SESSION['user_id'];
$last_message_id = isset($_GET['last_msg']) ? (int)$_GET['last_msg'] : 0;
// We also track when users last came online to notify about status changes.
$last_check_time = date('Y-m-d H:i:s');

// Update my own last_seen to keep me online
function updateMyStatus($pdo, $uid) {
    try {
        $pdo->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE id = ?")->execute([$uid]);
    } catch (Exception $e) {}
}

$loop_counter = 0;

while (true) {
    if (connection_aborted()) {
        break;
    }

    if ($loop_counter % 10 == 0) { // Every 10 loops (approx 10-20 seconds), update my own status
        updateMyStatus($pdo, $user_id);
    }

    $events = [];

    // 1. Check for new messages in ANY conversation the user belongs to
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
            $events[] = [
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

    // 2. We could also check for new conversations here, but for simplicity, 
    // the frontend can reload the chat list when a new_message arrives for an unknown conversation.

    // 3. Check for read receipts (messages sent by me that were just read)
    // We'll just pass down the max message id that is read for each conversation
    $sql_reads = "
        SELECT conversation_id, MAX(id) as max_read_id
        FROM messages
        WHERE sender_id = :user_id AND is_read = 1
        GROUP BY conversation_id
    ";
    $stmt_reads = $pdo->prepare($sql_reads);
    $stmt_reads->execute(['user_id' => $user_id]);
    $read_receipts = $stmt_reads->fetchAll();
    
    // We'll send this every loop, the client will only update if it changed
    $events[] = [
        'type' => 'read_receipts',
        'data' => $read_receipts
    ];

    // Send events if any
    foreach ($events as $event) {
        echo "data: " . json_encode($event) . "\n\n";
    }

    if (!empty($events)) {
        ob_flush();
        flush();
    }

    // 4. Online Users Tracking
    // We check which users are online (last_seen > NOW() - 15 SECONDS)
    $stmt_online = $pdo->prepare("SELECT id FROM users WHERE last_seen > DATE_SUB(NOW(), INTERVAL 15 SECOND)");
    $stmt_online->execute();
    $current_online_users = $stmt_online->fetchAll(PDO::FETCH_COLUMN);
    
    // Convert to comma separated string to compare easily
    $current_online_str = implode(',', $current_online_users);
    
    if (!isset($last_online_str) || $last_online_str !== $current_online_str) {
        $last_online_str = $current_online_str;
        $event = [
            'type' => 'online_status',
            'data' => $current_online_users
        ];
        echo "data: " . json_encode($event) . "\n\n";
        ob_flush();
        flush();
    }

    // Update my last seen every 5 seconds
    $loop_counter++;
    if ($loop_counter % 5 == 0) {
        updateMyStatus($pdo, $user_id);
    }

    sleep(2); // Wait 2 seconds before checking again
}
?>
