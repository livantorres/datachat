<?php
require_once 'includes/db.php';
$hash = password_hash('123456789', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = ? WHERE document_number = '123456789'")->execute([$hash]);
echo "Password fixed";
