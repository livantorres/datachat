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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?= htmlspecialchars($inst_data->app_name) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>
<body class="d-flex vh-100 p-0 m-0 overflow-hidden">

<div class="container-fluid p-0 d-flex position-fixed top-0 start-0 w-100 h-100">
    
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column col-12 col-md-4 col-lg-3 border-end p-0 m-0" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="d-flex align-items-center">
                <img src="uploads/avatars/<?= htmlspecialchars($current_user->avatar) ?>" alt="Avatar" class="avatar-sm border border-secondary" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user->name) ?>&background=random'">
            </div>
            <div class="d-flex align-items-center">
                <button class="btn btn-light rounded-circle ms-1 p-2" title="Nuevo Chat" data-bs-toggle="modal" data-bs-target="#modalNewChat">
                    <i class="bi bi-chat-left-text-fill fs-5"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-light rounded-circle ms-1 p-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="profile">Perfil</a></li>
                        <?php if($current_user->role === 'superadmin'): ?>
                            <li><a class="dropdown-item" href="admin">Administración</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Tema visual</h6></li>
                        <li><a class="dropdown-item" href="#" onclick="setTheme('light'); return false;"><i class="bi bi-sun me-2"></i>Claro</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setTheme('dark'); return false;"><i class="bi bi-moon me-2"></i>Oscuro</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setTheme('system'); return false;"><i class="bi bi-display me-2"></i>Sistema</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="api/logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-box">
                <i class="bi bi-search me-2"></i>
                <input type="text" class="form-control py-1" id="searchChat" placeholder="Buscar un chat o iniciar uno nuevo">
            </div>
        </div>

        <!-- Chat List -->
        <div class="chat-list flex-grow-1 overflow-auto" id="chatList">
            <div class="text-center text-muted mt-5">Cargando chats...</div>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-area d-none d-md-flex flex-column col-12 col-md-8 col-lg-9 p-0 m-0 position-relative" id="chatArea">
        
        <!-- Blank State -->
        <div id="chatBlankState" class="h-100 d-flex flex-column align-items-center justify-content-center text-center">
            <?php if($inst_data->logo): ?>
                <img src="uploads/logos/<?= htmlspecialchars($inst_data->logo) ?>" alt="Logo" width="150" class="mb-4 opacity-50">
            <?php else: ?>
                <i class="bi bi-laptop text-muted opacity-50 mb-4" style="font-size: 8rem;"></i>
            <?php endif; ?>
            <h1 class="fw-light mb-3"><?= htmlspecialchars($inst_data->app_name) ?> Web</h1>
            <p class="text-muted">Envía y recibe mensajes en tiempo real.<br>Selecciona un chat en la barra lateral para comenzar.</p>
        </div>

        <!-- Active Chat Header -->
        <div id="chatHeader" class="d-none align-items-center justify-content-between flex-shrink-0">
            <div class="d-flex align-items-center">
                <button class="btn btn-light d-md-none me-2 p-1" id="btnBack"><i class="bi bi-arrow-left fs-4"></i></button>
                <img src="uploads/avatars/default.png" alt="" class="avatar-sm me-3" id="activeChatAvatar">
                <div class="d-flex flex-column justify-content-center">
                    <h6 class="m-0 fw-normal" id="activeChatName" style="font-size: 1.05rem;">Contacto</h6>
                    <small class="text-muted" id="activeChatStatus" style="font-size: 0.8rem;">Online</small>
                </div>
            </div>
            <div class="d-flex">
                <button class="btn btn-light rounded-circle p-2 ms-2"><i class="bi bi-search fs-5"></i></button>
                <button class="btn btn-light rounded-circle p-2 ms-2"><i class="bi bi-three-dots-vertical fs-5"></i></button>
            </div>
        </div>

        <!-- Messages Box -->
        <div id="chatLoading" class="d-none justify-content-center align-items-center flex-grow-1">
            <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Cargando...</span></div>
        </div>
        <div id="messagesBox" class="flex-grow-1 overflow-auto d-none">
            <!-- Messages injected here -->
        </div>

        <!-- Input Area -->
        <div id="inputArea" class="d-none align-items-center flex-shrink-0">
            <form id="formSendMessage" class="d-flex align-items-center w-100 m-0">
                <input type="hidden" id="activeConversationId" name="conversation_id">
                
                <div class="dropdown me-2">
                    <button class="btn btn-light rounded-circle p-2" type="button" id="btnAttachment" data-bs-toggle="dropdown" aria-expanded="false" title="Adjuntar">
                        <i class="bi bi-plus fs-4"></i>
                    </button>
                    <ul class="dropdown-menu shadow-lg mb-2" aria-labelledby="btnAttachment" style="bottom: 100%; top: auto;">
                        <li><a class="dropdown-item py-2" href="#" id="attachDocument"><i class="bi bi-file-earmark-text text-primary me-3 fs-5"></i> Documento</a></li>
                        <li><a class="dropdown-item py-2" href="#" id="attachImage"><i class="bi bi-image text-primary me-3 fs-5"></i> Fotos y videos</a></li>
                    </ul>
                    <input type="file" id="fileInput" name="file[]" class="d-none" multiple>
                </div>
                
                <div class="flex-grow-1 rounded-3 mx-2 d-flex align-items-center px-3 shadow-sm" style="background-color: #ffffff !important;">
                    <input type="text" class="form-control py-2 shadow-none" id="messageInput" name="message" placeholder="Escribe un mensaje" autocomplete="off" style="background-color: transparent !important; color: #111b21 !important;">
                </div>
                
                <button type="submit" class="btn btn-light rounded-circle p-2" id="btnSend" title="Enviar">
                    <i class="bi bi-send-fill fs-5 text-muted"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal New Chat -->
<div class="modal fade" id="modalNewChat" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content text-dark" style="background-color: #fff;">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Nuevo Chat</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <!-- Tabs -->
        <ul class="nav nav-tabs nav-fill" id="newChatTabs" role="tablist">
          <li class="nav-item" role="presentation"><button class="nav-link active text-success" data-bs-toggle="tab" data-bs-target="#contacts" type="button">Contactos</button></li>
          <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#groups" type="button">Crear Grupo</button></li>
        </ul>
        <div class="tab-content" id="newChatTabsContent">
          <!-- Contactos -->
          <div class="tab-pane fade show active p-3" id="contacts">
            <input type="text" class="form-control mb-3" id="searchNewContact" placeholder="Buscar contacto..." style="background-color: #f0f2f5 !important; color: #000;">
            <div id="contactsList" class="list-group list-group-flush"></div>
          </div>
          <!-- Grupos -->
          <div class="tab-pane fade p-3" id="groups">
            <form id="formCreateGroup">
                <div class="mb-3">
                    <label class="form-label text-dark">Nombre del Grupo</label>
                    <input type="text" class="form-control" name="group_name" required style="background-color: #f0f2f5 !important; color: #000;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Miembros</label>
                    <div id="groupMembersList" class="border p-2 rounded" style="max-height: 200px; overflow-y:auto; background: #f0f2f5;"></div>
                </div>
                <button type="submit" class="btn btn-success w-100">Crear Grupo</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<audio id="notificationSound" src="assets/sounds/notification.mp3" preload="auto"></audio>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<script>
    <?php
    $app_path = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', dirname(__DIR__)));
    $base_url = rtrim($app_path, '/') . '/';
    ?>
    const BASE_URL = "<?= $base_url ?>";
    const CURRENT_USER_ID = <?= $user_id ?>; 
    const APP_NAME = "<?= htmlspecialchars($inst_data->app_name) ?>";
    const OPEN_CHAT_USER = <?= isset($_GET['chat_user']) ? intval($_GET['chat_user']) : 'null' ?>;
</script>
<script src="assets/js/app.js"></script>
<script src="assets/js/chat.js"></script>
<script src="assets/js/sse.js"></script>
</body>
</html>





