<?php
require_once 'includes/db.php';
try {
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
        (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message_time
    FROM conversations c
    JOIN conversation_users cu ON c.id = cu.conversation_id
    LEFT JOIN conversation_users cu2 ON c.id = cu2.conversation_id AND cu2.user_id != 1
    LEFT JOIN users u ON cu2.user_id = u.id
    WHERE cu.user_id = 1
    GROUP BY c.id
    ORDER BY COALESCE(last_message_time, c.updated_at) DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
