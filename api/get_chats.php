<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all conversations for the user
$sql = "
    SELECT 
        c.id, 
        c.type, 
        c.name as group_name,
        c.updated_at,
        ANY_VALUE(u.id) as contact_id,
        ANY_VALUE(u.name) as contact_name,
        ANY_VALUE(u.avatar) as avatar,
        ANY_VALUE(u.status) as status,
        ANY_VALUE(u.last_seen) as last_seen,
        (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != :user_id AND is_read = 0) as unread_count,
        (SELECT sender_id FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_sender_id,
        (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message_time
    FROM conversations c
    JOIN conversation_users cu ON c.id = cu.conversation_id
    LEFT JOIN conversation_users cu2 ON c.id = cu2.conversation_id AND cu2.user_id != :user_id
    LEFT JOIN users u ON cu2.user_id = u.id
    WHERE cu.user_id = :user_id
    GROUP BY c.id
    ORDER BY COALESCE(last_message_time, c.updated_at) DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$chats = $stmt->fetchAll();

$formatted_chats = [];
foreach ($chats as $chat) {
    // Determine chat name and avatar
    if ($chat->type === 'private') {
        $name = $chat->contact_name ?? 'Usuario Desconocido';
        $avatar = $chat->avatar ?? 'default.png';
        $status = $chat->status;
        $is_online = ($status === 'online');
    } else {
        $name = $chat->group_name;
        $avatar = 'group.png'; // Make sure this exists in avatars folder
        $is_online = false; // Groups don't have online status directly like this
    }

    $formatted_chats[] = [
        'id' => $chat->id,
        'type' => $chat->type,
        'name' => $name,
        'avatar' => 'uploads/avatars/' . $avatar,
        'last_message' => $chat->last_message ?? 'Sin mensajes aún',
        'time' => $chat->last_message_time ? date('H:i', strtotime($chat->last_message_time)) : '',
        'is_online' => $is_online,
        'contact_id' => $chat->contact_id, // Only for private
        'unread_count' => $chat->unread_count,
        'is_mine' => ($chat->last_sender_id == $user_id)
    ];
}

echo json_encode(['success' => true, 'data' => $formatted_chats]);
?>
