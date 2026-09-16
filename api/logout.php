<?php
require_once '../includes/db.php';

if (isset($_SESSION['user_id'])) {
    // Set offline
    $update = $pdo->prepare("UPDATE users SET status = 'offline', last_seen = NOW() WHERE id = ?");
    $update->execute([$_SESSION['user_id']]);
}

session_unset();
session_destroy();
header('Location: ../');
exit;
?>
