<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? 'private';

try {
    $pdo->beginTransaction();

    if ($type === 'private') {
        $contact_id = $data['contact_id'];
        
        // Check if chat already exists
        $check_sql = "
            SELECT c.id FROM conversations c
            JOIN conversation_users cu1 ON c.id = cu1.conversation_id
            JOIN conversation_users cu2 ON c.id = cu2.conversation_id
            WHERE c.type = 'private' AND cu1.user_id = ? AND cu2.user_id = ?
        ";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$user_id, $contact_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            echo json_encode(['success' => true, 'conversation_id' => $existing->id]);
            $pdo->rollBack();
            exit;
        }

        // Create new
        $stmt = $pdo->prepare("INSERT INTO conversations (type) VALUES ('private')");
        $stmt->execute();
        $conversation_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO conversation_users (conversation_id, user_id) VALUES (?, ?), (?, ?)");
        $stmt->execute([$conversation_id, $user_id, $conversation_id, $contact_id]);

        echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);
    } 
    else if ($type === 'group') {
        $name = $data['name'];
        $members = $data['members']; // array of user ids
        
        $stmt = $pdo->prepare("INSERT INTO conversations (type, name) VALUES ('group', ?)");
        $stmt->execute([$name]);
        $conversation_id = $pdo->lastInsertId();

        // Add creator
        $members[] = $user_id;
        
        $sql_users = "INSERT INTO conversation_users (conversation_id, user_id) VALUES ";
        $values = [];
        $params = [];
        foreach (array_unique($members) as $uid) {
            $values[] = "(?, ?)";
            $params[] = $conversation_id;
            $params[] = $uid;
        }
        
        $stmt = $pdo->prepare($sql_users . implode(', ', $values));
        $stmt->execute($params);

        echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
