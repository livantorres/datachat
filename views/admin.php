<?php
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: chat');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Handle institutional data update
if (isset($_POST['update_inst'])) {
    $app_name = $_POST['app_name'];
    $company_name = $_POST['company_name'];
    
    $logo_query = "";
    $params = [$app_name, $company_name];
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        $upload_dir = __DIR__ . '/../uploads/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $filename)) {
            $logo_query = ", logo = ?";
            $params[] = $filename;
        }
    }
    
    $pdo->prepare("UPDATE datos_institucionales SET app_name = ?, company_name = ? $logo_query")->execute($params);
    $inst_data = getInstData($pdo); // Refresh
    $msg_inst = "Datos institucionales actualizados";
}

// Handle User Actions (Create, Reset Password)
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create_user') {
        $name = $_POST['name'];
        $doc = $_POST['document_number'];
        $phone = $_POST['phone'];
        if(empty($phone)) $phone = null;
        $email = $_POST['email'];
        $pass = password_hash($doc, PASSWORD_DEFAULT); // Default pass is document
        
        try {
            // Se crean activos por defecto desde el panel admin
            $pdo->prepare("INSERT INTO users (name, document_number, phone, email, password, is_active) VALUES (?, ?, ?, ?, ?, 1)")
                ->execute([$name, $doc, $phone, $email, $pass]);
            $msg_user = "Usuario creado exitosamente. La contraseña es su número de documento.";
        } catch(PDOException $e) {
            $err_user = "Error al crear usuario (quizás el documento, email o teléfono ya existe).";
        }
    }
    
    if ($_POST['action'] === 'reset_pass') {
        $uid = $_POST['user_id'];
        $doc = $_POST['user_doc'];
        $pass = password_hash($doc, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$pass, $uid]);
        $msg_user = "Contraseña reiniciada al número de documento.";
    }

    if ($_POST['action'] === 'toggle_active') {
        $uid = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_status, $uid]);
        $msg_user = "Estado del usuario actualizado.";
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - <?= htmlspecialchars($inst_data->app_name) ?></title>
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
        <li class="nav-item">
            <a href="admin" class="nav-link active">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="profile" class="nav-link">
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
            <img src="uploads/avatars/<?= htmlspecialchars($current_user->avatar) ?>" alt="" width="32" height="32" class="rounded-circle me-2" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user->name) ?>'">
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
        <h4 class="m-0" style="color: var(--wa-text-main);">Administración</h4>
        <button class="btn btn-primary d-md-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
    </div>
    
    <div class="container-fluid p-4">
        <div class="row">
            <!-- Institucional -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-bottom">
                        <h5 class="m-0"><i class="bi bi-building me-2"></i> Datos Institucionales</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($msg_inst)) echo "<div class='alert alert-success'>$msg_inst</div>"; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="update_inst" value="1">
                            <div class="mb-3">
                                <label class="form-label text-muted">Nombre de la App</label>
                                <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($inst_data->app_name) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Nombre de la Empresa</label>
                                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($inst_data->company_name) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Logo actual</label><br>
                                <?php if($inst_data->logo): ?>
                                    <div class="bg-light p-2 rounded mb-2 d-inline-block border">
                                        <img src="uploads/logos/<?= $inst_data->logo ?>" height="40" style="cursor:pointer;" onclick="viewImage(this.src, 'logo')">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Actualizar Datos</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Usuarios -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0"><i class="bi bi-people me-2"></i> Gestión de Usuarios</h5>
                        <button class="btn btn-sm" style="background-color: #ffffff; color: #118a66; font-weight: 500;" data-bs-toggle="modal" data-bs-target="#modalCreateUser"><i class="bi bi-plus-lg"></i> Nuevo Usuario</button>
                    </div>
                    <div class="card-body overflow-hidden d-flex flex-column" style="max-height: 600px;">
                        <?php if(isset($msg_user)) echo "<div class='alert alert-success'>$msg_user</div>"; ?>
                        <?php if(isset($err_user)) echo "<div class='alert alert-danger'>$err_user</div>"; ?>
                        
                        <div class="mb-3">
                            <input type="text" id="searchUserAdmin" class="form-control" placeholder="Buscar usuario por nombre, documento o correo...">
                        </div>

                        <div class="table-responsive flex-grow-1 overflow-auto">
                            <table class="table table-hover align-middle" id="usersTableAdmin">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Documento</th>
                                        <th>Rol/Estado</th>
                                        <th>Última Conexión</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="uploads/avatars/<?= $u->avatar ?>" class="rounded-circle me-2 border border-secondary" width="35" height="35" style="cursor:pointer; object-fit: cover;" onclick="viewImage(this.src, 'avatar', <?= $u->id ?>)" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u->name) ?>'">
                                                <div>
                                                    <div class="fw-bold user-name-col"><?= htmlspecialchars($u->name) ?></div>
                                                    <small class="text-muted user-email-col"><?= htmlspecialchars($u->email) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="user-doc-col">
                                            <?= htmlspecialchars($u->document_number) ?><br>
                                            <small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($u->phone ?: 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $u->role == 'superadmin' ? 'bg-danger' : 'bg-secondary' ?>"><?= ucfirst($u->role) ?></span>
                                            <?php if($u->is_active == 0): ?>
                                                <span class="badge bg-warning text-dark"><i class="bi bi-person-dash"></i> Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted d-block"><?= $u->last_seen ? date('d/m/Y H:i', strtotime($u->last_seen)) : 'Nunca' ?></small>
                                            <?php if($u->status == 'online'): ?>
                                                <span class="badge bg-success" style="font-size: 0.7em;">Online</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="chat?chat_user=<?= $u->id ?>" class="btn btn-sm btn-outline-primary" title="Chatear"><i class="bi bi-chat-dots"></i></a>
                                                
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Reiniciar contraseña al documento?');">
                                                    <input type="hidden" name="action" value="reset_pass">
                                                    <input type="hidden" name="user_id" value="<?= $u->id ?>">
                                                    <input type="hidden" name="user_doc" value="<?= htmlspecialchars($u->document_number) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Reiniciar Contraseña"><i class="bi bi-key"></i></button>
                                                </form>

                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="user_id" value="<?= $u->id ?>">
                                                    <?php if($u->is_active == 1): ?>
                                                        <input type="hidden" name="new_status" value="0">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar Cuenta"><i class="bi bi-x-circle"></i></button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="new_status" value="1">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Activar Cuenta"><i class="bi bi-check-circle"></i></button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create User -->
<div class="modal fade" id="modalCreateUser" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <input type="hidden" name="action" value="create_user">
          <div class="modal-header">
            <h5 class="modal-title">Crear Nuevo Usuario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label text-muted">Nombre Completo</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted">N° Documento (Contraseña inicial)</label>
                <input type="text" name="document_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted">Teléfono</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label text-muted">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer" style="background-color: var(--wa-bg-color);">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Crear</button>
          </div>
      </form>
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

    // Buscador de usuarios
    const searchInput = document.getElementById('searchUserAdmin');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTableAdmin tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('.user-name-col')?.textContent.toLowerCase() || '';
                const email = row.querySelector('.user-email-col')?.textContent.toLowerCase() || '';
                const doc = row.querySelector('.user-doc-col')?.textContent.toLowerCase() || '';
                
                if(name.includes(term) || email.includes(term) || doc.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
</script>
</body>
</html>
