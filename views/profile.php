<?php
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ./');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user = $current_user; // For profile specific context

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (empty($phone)) $phone = null;
    
    try {
        if (!empty($email)) {
            $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?")->execute([$email, $phone, $user_id]);
            $user->email = $email;
            $user->phone = $phone;
            $current_user->email = $email;
        }
        
        // Avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = md5($user_id . time()) . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
                $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$filename, $user_id]);
                $user->avatar = $filename;
                $current_user->avatar = $filename; // Update session context var too
            }
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
        }
        
        $success = "Perfil actualizado correctamente";
    } catch(PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $error = "El correo o teléfono ya están en uso por otra cuenta.";
        } else {
            $error = "Error al actualizar perfil.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - <?= htmlspecialchars($inst_data->app_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
    <style>
        /* Admin Layout Styles */
        body { overflow-y: auto; }
        .admin-sidebar {
            width: 250px;
            background-color: var(--wa-panel-color);
            border-right: 1px solid var(--wa-border-color);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .admin-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        .admin-header {
            background-color: var(--wa-bg-color);
            border-bottom: 1px solid var(--wa-border-color);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-link { color: var(--wa-text-main); }
        .nav-link:hover, .nav-link.active {
            background-color: var(--wa-hover-color);
            color: var(--wa-primary);
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .admin-sidebar.sidebar-open { transform: translateX(0); }
            .admin-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="admin-sidebar d-flex flex-column p-3">
    <a href="chat" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none" style="color: var(--wa-text-main);">
        <?php if($inst_data->logo): ?>
            <img src="uploads/logos/<?= htmlspecialchars($inst_data->logo) ?>" alt="Logo" width="30" class="me-2">
        <?php else: ?>
            <i class="bi bi-gear-fill fs-4 me-2 text-primary"></i>
        <?php endif; ?>
        <span class="fs-5 fw-bold"><?= htmlspecialchars($inst_data->app_name) ?></span>
    </a>
    <hr style="border-color: var(--wa-border-color);">
    <ul class="nav nav-pills flex-column mb-auto">
        <?php if($current_user->role === 'superadmin'): ?>
        <li class="nav-item">
            <a href="admin" class="nav-link">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="profile" class="nav-link active">
                <i class="bi bi-person-circle me-2"></i> Mi Perfil
            </a>
        </li>
        <li>
            <a href="chat" class="nav-link">
                <i class="bi bi-chat-dots me-2"></i> Volver al Chat
            </a>
        </li>
    </ul>
    <hr style="border-color: var(--wa-border-color);">
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" style="color: var(--wa-text-main);" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="uploads/avatars/<?= htmlspecialchars($current_user->avatar) ?>" alt="" width="32" height="32" class="rounded-circle me-2 border border-secondary" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user->name) ?>'">
            <strong><?= htmlspecialchars($current_user->name) ?></strong>
        </a>
        <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="#" onclick="setTheme('light'); return false;"><i class="bi bi-sun me-2"></i>Claro</a></li>
            <li><a class="dropdown-item" href="#" onclick="setTheme('dark'); return false;"><i class="bi bi-moon me-2"></i>Oscuro</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="api/logout.php">Cerrar sesión</a></li>
        </ul>
    </div>
</div>

<!-- Main Content -->
<div class="admin-content p-0">
    <div class="admin-header">
        <h4 class="m-0" style="color: var(--wa-text-main);">Configuración de Perfil</h4>
        <button class="btn btn-primary d-md-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
    </div>
    
    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-bottom">
                        <h5 class="m-0"><i class="bi bi-person-vcard me-2"></i> Mis Datos</h5>
                    </div>
                    <div class="card-body">
                        <?php if($success): ?>
                            <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> <?= $success ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?= $error ?></div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-4">
                                <img src="uploads/avatars/<?= htmlspecialchars($user->avatar) ?>" alt="Avatar" class="rounded-circle img-thumbnail border-secondary" style="width: 150px; height: 150px; object-fit: cover; background-color: var(--wa-bg-color); cursor:pointer;" onclick="viewImage(this.src, 'avatar')" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user->name) ?>&size=150'">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Cambiar Avatar</label>
                                <input type="file" class="form-control" name="avatar" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Nombre</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user->name) ?>" disabled style="background-color: var(--wa-bg-color) !important; opacity: 0.8;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Documento</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user->document_number) ?>" disabled style="background-color: var(--wa-bg-color) !important; opacity: 0.8;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Email</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Teléfono</label>
                                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted">Nueva Contraseña <small>(dejar en blanco para no cambiar)</small></label>
                                <input type="password" class="form-control" name="password">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-save me-1"></i> Guardar Cambios</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.admin-sidebar').classList.toggle('sidebar-open');
    });

    function viewImage(src, type, id = null) {
        if(src.includes('ui-avatars.com') || src.includes('default.png')) {
            Swal.fire({
                imageUrl: src,
                imageAlt: 'Imagen',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Cerrar',
                customClass: { image: 'img-fluid rounded' }
            });
            return;
        }

        Swal.fire({
            imageUrl: src,
            imageAlt: 'Imagen',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="bi bi-trash"></i> Eliminar Foto',
            cancelButtonText: 'Cerrar',
            customClass: { image: 'img-fluid rounded' }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('type', type);
                if(id) formData.append('user_id', id);

                fetch('api/delete_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }
</script>
</body>
</html>
