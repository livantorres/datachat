<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$conversation_id = $_POST['conversation_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

// Verify membership
$check = $pdo->prepare("SELECT 1 FROM conversation_users WHERE conversation_id = ? AND user_id = ?");
$check->execute([$conversation_id, $user_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$attachment = null;
$attachment_type = null;

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    
    // Valid extensions
    $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    
    if (in_array($fileExtension, $allowedfileExtensions)) {
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $uploadFileDir = '../uploads/attachments/';
        $dest_path = $uploadFileDir . $newFileName;
        
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $attachment = $newFileName;
            
            // Determine general type for frontend preview
            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $attachment_type = 'image';
            } elseif ($fileExtension === 'pdf') {
                $attachment_type = 'pdf';
            } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                $attachment_type = 'word';
            } elseif (in_array($fileExtension, ['xls', 'xlsx'])) {
                $attachment_type = 'excel';
            } elseif ($fileExtension === 'txt') {
                $attachment_type = 'txt';
            }
        }
    }
}

if (empty($message) && !$attachment) {
    echo json_encode(['success' => false, 'message' => 'Mensaje vacío']);
    exit;
}

// Insert message
$stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, attachment, attachment_type) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute([$conversation_id, $user_id, $message, $attachment, $attachment_type])) {
    // Update conversation updated_at for sorting
    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conversation_id]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar mensaje']);
}
?>
