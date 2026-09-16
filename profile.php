<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = md5($user_id . time()) . '.' . $ext;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], "uploads/avatars/$filename")) {
            $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$filename, $user_id]);
            $user->avatar = $filename;
        }
    }

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
    }
    
    $success = "Perfil actualizado correctamente";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil - <?= htmlspecialchars($inst_data->app_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mi Perfil</h5>
                    <a href="chat.php" class="btn btn-sm btn-light">Volver al Chat</a>
                </div>
                <div class="card-body">
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="uploads/avatars/<?= htmlspecialchars($user->avatar) ?>" alt="Avatar" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user->name) ?>&size=150'">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cambiar Avatar</label>
                            <input type="file" class="form-control" name="avatar" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user->name) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
