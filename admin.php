<?php
require_once 'includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: chat.php');
    exit;
}

// Handle institutional data update
if (isset($_POST['update_inst'])) {
    $app_name = $_POST['app_name'];
    $company_name = $_POST['company_name'];
    
    $logo_query = "";
    $params = [$app_name, $company_name];
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], "uploads/logos/$filename")) {
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
        $email = $_POST['email'];
        $pass = password_hash($doc, PASSWORD_DEFAULT); // Default pass is document
        
        try {
            $pdo->prepare("INSERT INTO users (name, document_number, email, password) VALUES (?, ?, ?, ?)")
                ->execute([$name, $doc, $email, $pass]);
            $msg_user = "Usuario creado exitosamente. La contraseña es su número de documento.";
        } catch(PDOException $e) {
            $err_user = "Error al crear usuario (quizás el documento o email ya existe).";
        }
    }
    
    if ($_POST['action'] === 'reset_pass') {
        $uid = $_POST['user_id'];
        $doc = $_POST['user_doc'];
        $pass = password_hash($doc, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$pass, $uid]);
        $msg_user = "Contraseña reiniciada al número de documento.";
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración - <?= htmlspecialchars($inst_data->app_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Panel de Administración</h2>
        <a href="chat.php" class="btn btn-secondary">Volver al Chat</a>
    </div>

    <div class="row">
        <!-- Institucional -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">Datos Institucionales</div>
                <div class="card-body">
                    <?php if(isset($msg_inst)) echo "<div class='alert alert-success'>$msg_inst</div>"; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="update_inst" value="1">
                        <div class="mb-3">
                            <label>Nombre de la App</label>
                            <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($inst_data->app_name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Nombre de la Empresa</label>
                            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($inst_data->company_name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Logo actual</label><br>
                            <?php if($inst_data->logo): ?>
                                <img src="uploads/logos/<?= $inst_data->logo ?>" height="50" class="mb-2">
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
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span class="m-0">Gestión de Usuarios</span>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalCreateUser">Nuevo Usuario</button>
                </div>
                <div class="card-body overflow-auto" style="max-height: 500px;">
                    <?php if(isset($msg_user)) echo "<div class='alert alert-success'>$msg_user</div>"; ?>
                    <?php if(isset($err_user)) echo "<div class='alert alert-danger'>$err_user</div>"; ?>
                    
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Documento</th>
                                <th>Rol</th>
                                <th>Última Conexión</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td>
                                    <img src="uploads/avatars/<?= $u->avatar ?>" class="rounded-circle me-2" width="30" height="30" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u->name) ?>'">
                                    <?= htmlspecialchars($u->name) ?>
                                    <?php if($u->status == 'online') echo '<span class="badge bg-success">Online</span>'; ?>
                                </td>
                                <td><?= htmlspecialchars($u->document_number) ?></td>
                                <td><span class="badge bg-secondary"><?= $u->role ?></span></td>
                                <td><small class="text-muted"><?= $u->last_seen ? date('d/m/Y H:i', strtotime($u->last_seen)) : 'Nunca' ?></small></td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Reiniciar contraseña al documento?');">
                                        <input type="hidden" name="action" value="reset_pass">
                                        <input type="hidden" name="user_id" value="<?= $u->id ?>">
                                        <input type="hidden" name="user_doc" value="<?= htmlspecialchars($u->document_number) ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Reiniciar Contraseña"><i class="bi bi-key"></i></button>
                                    </form>
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
                <label>Nombre Completo</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>N° Documento (Será su contraseña inicial)</label>
                <input type="text" name="document_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Crear</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
