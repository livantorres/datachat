<?php
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: chat');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($inst_data->app_name) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            background-color: var(--wa-panel-color);
            border: 1px solid var(--wa-border-color);
            color: var(--wa-text-main);
        }
        .logo-img {
            max-width: 150px;
            margin-bottom: 1.5rem;
        }
        /* Fix floating labels in dark mode */
        .form-floating > label {
            color: var(--wa-text-muted);
        }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label,
        .form-floating > .form-select ~ label {
            color: var(--wa-primary);
            background-color: transparent !important;
        }
        .form-floating > label::after {
            background-color: transparent !important;
        }
        .form-floating > .form-control:focus, 
        .form-floating > .form-control:not(:placeholder-shown) {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }
    </style>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

<div class="login-card text-center">
    <?php if ($inst_data->logo): ?>
        <img src="uploads/logos/<?= htmlspecialchars($inst_data->logo) ?>" alt="Logo" class="logo-img">
    <?php else: ?>
        <h2 class="text-primary mb-3"><i class="bi bi-chat-dots-fill"></i> <?= htmlspecialchars($inst_data->app_name) ?></h2>
    <?php endif; ?>
    
    <h5 class="mb-4 text-muted"><?= htmlspecialchars($inst_data->company_name) ?></h5>

    <form id="loginForm">
        <div class="form-floating mb-3">
            <input type="text" class="form-control" id="document_number" name="document_number" placeholder="N° de Documento" required>
            <label for="document_number">N° de Documento</label>
        </div>
        <div class="form-floating mb-4">
            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
            <label for="password">Contraseña</label>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/login.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'chat';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Hubo un problema de conexión', 'error');
    });
});
</script>
</body>
</html>
