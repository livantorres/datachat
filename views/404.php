<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - <?= htmlspecialchars($inst_data->app_name) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
    <style>
        body {
            background-color: var(--wa-panel-color);
            color: var(--wa-text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 900;
            background: linear-gradient(135deg, #009640 0%, #1a73e8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .error-icon {
            font-size: 5rem;
            color: var(--wa-text-muted);
            margin-bottom: 0.5rem;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        .btn-home {
            background: linear-gradient(90deg, #118a66 0%, #1369a8 100%);
            border: none;
            color: white;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 500;
            transition: opacity 0.3s, transform 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-home:hover {
            opacity: 0.9;
            transform: scale(1.05);
            color: white;
        }
        
        /* Decoraciones de fondo */
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.2;
            z-index: 1;
        }
        .blob-1 {
            width: 400px;
            height: 400px;
            background-color: #009640;
            top: -100px;
            left: -100px;
        }
        .blob-2 {
            width: 300px;
            height: 300px;
            background-color: #1a73e8;
            bottom: -50px;
            right: -50px;
        }
    </style>
</head>
<body>

<!-- Elementos decorativos de fondo -->
<div class="bg-blob blob-1"></div>
<div class="bg-blob blob-2"></div>

<div class="error-container">
    <div class="error-icon">
        <i class="bi bi-compass"></i>
    </div>
    <div class="error-code">404</div>
    <h2 class="mb-3 fw-bold">¡Vaya! Te has perdido</h2>
    <p class="text-muted mb-4 lead">
        La página que estás buscando no existe, ha sido movida o temporalmente no está disponible.
    </p>
    <a href="./" class="btn-home shadow-sm">
        <i class="bi bi-house-door me-2"></i> Volver al Inicio
    </a>
</div>

</body>
</html>
