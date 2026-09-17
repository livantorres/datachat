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

$check = $pdo->prepare("SELECT 1 FROM conversation_users WHERE conversation_id = ? AND user_id = ?");
$check->execute([$conversation_id, $user_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
$uploadFileDir = __DIR__ . '/../uploads/attachments/';
if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0777, true);
}

$files_uploaded = [];

// Convert $_FILES['file'] to a normalized array format
if (isset($_FILES['file'])) {
    $file_post = $_FILES['file'];
    $file_ary = array();
    $file_count = is_array($file_post['name']) ? count($file_post['name']) : 1;
    $file_keys = array('name', 'type', 'tmp_name', 'error', 'size');

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = is_array($file_post['name']) ? $file_post[$key][$i] : $file_post[$key];
        }
    }

    foreach ($file_ary as $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = $file['name'];
            $fileTmpPath = $file['tmp_name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = md5(time() . $fileName . rand(1,10000)) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $attachment_type = 'txt';
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $attachment_type = 'image';
                    } elseif ($fileExtension === 'pdf') {
                        $attachment_type = 'pdf';
                    } elseif (in_array($fileExtension, ['doc', 'docx'])) {
                        $attachment_type = 'word';
                    } elseif (in_array($fileExtension, ['xls', 'xlsx'])) {
                        $attachment_type = 'excel';
                    }
                    $files_uploaded[] = [
                        'attachment' => $newFileName,
                        'type' => $attachment_type
                    ];
                }
            }
        }
    }
}

if (empty($message) && empty($files_uploaded)) {
    echo json_encode(['success' => false, 'message' => 'Mensaje vacío o formato no permitido']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, attachment, attachment_type) VALUES (?, ?, ?, ?, ?)");
$success = false;

if (!empty($files_uploaded)) {
    // Si hay archivos, creamos un mensaje por cada archivo. El texto acompaña al primero.
    foreach ($files_uploaded as $index => $file) {
        $msg_text = ($index === 0) ? (empty($message) ? null : $message) : null;
        $stmt->execute([$conversation_id, $user_id, $msg_text, $file['attachment'], $file['type']]);
    }
    $success = true;
} else {
    // Mensaje de texto puro sin adjuntos
    $success = $stmt->execute([$conversation_id, $user_id, $message, null, null]);
}

if ($success) {
    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conversation_id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar mensaje']);
}
?>
