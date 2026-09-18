<?php
require_once __DIR__ . '/../includes/db.php';

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
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
<style>
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: var(--wa-panel-color);
        margin: 0;
    }
    .top-header {
        position: absolute;
        top: 0;
        width: 100%;
        padding: 15px 30px;
        display: flex;
        justify-content: flex-end;
        z-index: 10;
        background: transparent;
    }
    .social-icon {
        color: var(--wa-text-muted);
        font-size: 1.2rem;
        margin-left: 15px;
        transition: transform 0.3s ease, color 0.3s ease;
        display: inline-block;
    }
    .social-icon:hover {
        transform: translateY(-4px) scale(1.1);
        color: var(--wa-primary);
    }
    [data-theme="dark"] .social-icon:hover {
        color: #ffffff;
    }
    .login-wrapper {
        flex-grow: 1;
        display: flex;
    }
    .login-left {
        background: linear-gradient(135deg, #009640 0%, #1a73e8 100%);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 3rem;
        position: relative;
        overflow: hidden;
    }
    .login-left::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        animation: rotate 20s linear infinite;
    }
    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .login-right {
        background-color: var(--wa-panel-color);
        display: flex;
        overflow-y: auto;
        padding: 3rem;
        position: relative;
        z-index: 2;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
    }
    [data-theme="dark"] .login-right {
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.6);
    }
    .login-form-box {
        margin: auto;
        width: 100%;
        max-width: 400px;
    }
    .divider:after,
    .divider:before {
        content: "";
        flex: 1;
        height: 1px;
        background: var(--wa-border-color);
    }
    .site-footer {
        
        background-color: var(--wa-bg-color);
        border-top: 1px solid var(--wa-border-color);
        color: var(--wa-text-muted);
        text-align: center;
        padding: 10px 0;
        font-size: 0.85rem;
        z-index: 10;
    }
    .fade-section {
        animation: fadeInSlide 0.4s ease forwards;
    }
    @keyframes fadeInSlide {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>
</head>
<body>

<!-- Header con Redes Sociales -->
<header class="top-header">
    <a href="#" class="social-icon" title="Facebook"><i class="bi bi-facebook"></i></a>
    <a href="#" class="social-icon" title="Instagram"><i class="bi bi-instagram"></i></a>
    <a href="#" class="social-icon" title="Twitter / X"><i class="bi bi-twitter-x"></i></a>
    <a href="#" class="social-icon" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
</header>

<div class="container-fluid p-0 login-wrapper">
    <div class="row g-0 w-100">
        
        <!-- Left Side: Corporate Space -->
        <div class="col-md-6 col-lg-7 d-none d-md-flex login-left text-center">
            <h1 class="display-4 fw-bold mb-3" style="z-index: 1;">Bienvenido a <?= htmlspecialchars($inst_data->app_name) ?></h1>
            <p class="lead mb-5" style="z-index: 1;">La plataforma de comunicación corporativa más segura y eficiente para tu empresa.</p>
            <div style="z-index: 1;">
                <i class="bi bi-chat-left-dots" style="font-size: 8rem; opacity: 0.8;"></i>
            </div>
        </div>

        <!-- Right Side: Forms -->
        <div class="col-md-6 col-lg-5 login-right pb-5">
            <div class="login-form-box">
                
                <div class="text-center mb-4">
                    <?php if ($inst_data->logo): ?>
                        <img src="uploads/logos/<?= htmlspecialchars($inst_data->logo) ?>" alt="Logo" style="max-width: 180px;">
                    <?php else: ?>
                        <h2 class="text-primary"><i class="bi bi-chat-dots-fill"></i> <?= htmlspecialchars($inst_data->app_name) ?></h2>
                    <?php endif; ?>
                    <h5 class="mt-3 text-muted"><?= htmlspecialchars($inst_data->company_name) ?></h5>
                </div>

                <!-- LOGIN SECTION -->
                <div id="loginSection" class="fade-section">
                    <form id="loginForm">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="login_id" name="login_id" placeholder="Documento, Teléfono o Email" required>
                            <label for="login_id">Documento, Teléfono o Email</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                            <label for="password">Contraseña</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
                        <p class="mt-4 mb-0 text-center"><small class="text-muted">¿No tienes cuenta?</small> <a href="#" id="btnShowRegister" class="text-decoration-none fw-bold">Regístrate aquí</a></p>
                    </form>

                    <div class="divider d-flex align-items-center my-4">
                        <p class="text-center fw-bold mx-3 mb-0 text-muted">O</p>
                    </div>

                    <div class="d-flex justify-content-center">
                        <div id="g_id_onload"
                            data-client_id="634438355633-6kql982tujt2vpvt6qjub7mjr7icmuem.apps.googleusercontent.com"
                            data-context="signin"
                            data-ux_mode="popup"
                            data-callback="handleCredentialResponse"
                            data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin"
                            data-type="standard"
                            data-shape="rectangular"
                            data-theme="outline"
                            data-text="continue_with"
                            data-size="large"
                            data-logo_alignment="left">
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted" style="font-size:0.7rem;">(Requiere configurar tu Client ID de Google)</small>
                    </div>
                </div>

                <!-- REGISTER SECTION -->
                <div id="registerSection" class="fade-section d-none">
                    <h5 class="mb-3 text-center fw-bold" style="color: var(--wa-text-main);">Solicitar Cuenta</h5>
                    <form id="registerForm">
                        <div class="form-floating mb-3">
                            <input type="text" name="name" class="form-control" id="reg_name" placeholder="Nombre Completo" required>
                            <label for="reg_name">Nombre Completo</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" name="document_number" class="form-control" id="reg_doc" placeholder="N° Documento" required>
                            <label for="reg_doc">N° Documento</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" name="phone" class="form-control" id="reg_phone" placeholder="Teléfono">
                            <label for="reg_phone">Teléfono</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="reg_email" placeholder="Email" required>
                            <label for="reg_email">Email</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" name="password" class="form-control" id="reg_pass" placeholder="Contraseña" required>
                            <label for="reg_pass">Contraseña</label>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 mb-3">Crear Cuenta</button>
                        <div class="text-center">
                            <button type="button" id="btnShowLogin" class="btn btn-link text-decoration-none text-muted p-0"><i class="bi bi-arrow-left me-1"></i> Volver al Login</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="site-footer">
    <div class="container-fluid px-4">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($inst_data->company_name) ?>. Todos los derechos reservados.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
// Toggle forms
const loginSection = document.getElementById('loginSection');
const registerSection = document.getElementById('registerSection');

document.getElementById('btnShowRegister').addEventListener('click', (e) => {
    e.preventDefault();
    loginSection.classList.add('d-none');
    registerSection.classList.remove('d-none');
});

document.getElementById('btnShowLogin').addEventListener('click', () => {
    registerSection.classList.add('d-none');
    loginSection.classList.remove('d-none');
});

// Login submit
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('api/login.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'chat';
        } else {
            Swal.fire({ icon: 'error', title: 'Acceso Denegado', text: data.message });
        }
    });
});

// Register submit
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('api/register.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Registro Exitoso',
                text: data.message
            }).then(() => {
                // Volver al login de manera elegante
                this.reset();
                registerSection.classList.add('d-none');
                loginSection.classList.remove('d-none');
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
});

// Handler for Google Sign-in
function handleCredentialResponse(response) {
    fetch('api/google_auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: response.credential })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'chat';
        } else {
            Swal.fire({
                icon: data.is_pending ? 'info' : 'error',
                title: data.is_pending ? 'Cuenta Registrada' : 'Error',
                text: data.message
            });
        }
    });
}
</script>
</body>
</html>


