# DataChat 🚀

Un sistema de chat empresarial en tiempo real inspirado en la interfaz moderna de WhatsApp Web (Dark/Light theme). Está desarrollado completamente en **PHP 7.2.34 (Vanilla)** sin frameworks para una máxima compatibilidad y ligereza en servidores Linux/Ubuntu. 

## ✨ Características Principales
- **Tiempo Real:** Construido utilizando *Server-Sent Events (SSE)* y Fetch API, eliminando la necesidad de NodeJS, WebSockets o librerías pesadas, y funcionando eficientemente a través de la API en PHP.
- **Doble Check de Lectura:** Sistema de "ticks" idéntico a WhatsApp (gris para enviado, azul ✅✅ para leído en tiempo real).
- **Temas Visuales:** Selector de modo Oscuro, Claro y Sistema, persistente mediante LocalStorage y manejado por CSS variables con diseño moderno.
- **Notificaciones Push y Sonoras:** Reproducción de sonidos reales de notificación y alertas in-app usando SweetAlert2, junto a un sistema de contador de mensajes no leídos por chat.
- **Gestión de Archivos:** Comparte imágenes y documentos (Word, Excel, PDF) con visualización previa y botones de descarga directos en línea.
- **Rutas Amigables:** Soporte para acortador de URLs en Apache/Nginx (p.ej `/chat` en vez de `/chat.php`).

## 🛠 Stack Tecnológico
- **Backend:** PHP 7.2+ puro (Procedural con MVC simplificado)
- **Base de Datos:** MySQL 8 / MariaDB (Driver PDO)
- **Frontend:** Vanilla JS (Fetch API + EventSource)
- **UI/UX:** HTML5, CSS3, Bootstrap 5 y SweetAlert2.

## 🚀 Instalación y Despliegue (VPS Ubuntu/DigitalOcean)
1. Clona el repositorio en el `DocumentRoot` (ej. `/var/www/html/datachat`).
2. Sube y ejecuta el esquema `database.sql` en tu MySQL/MariaDB.
3. Configura tus accesos en `includes/db.php`.
4. Asegúrate que PHP tenga permisos de escritura en las carpetas `uploads/avatars`, `uploads/attachments` y `uploads/logos`.
5. Si usas **Nginx**, agrega las rutas amigables en tu archivo de configuración de bloque de servidor (`location / { try_files $uri $uri.php$is_args$query_string; }`). Si usas **Apache**, el archivo `.htaccess` ya está incluido.

## 🔐 Acceso de Administrador
El usuario *Superadmin* puede acceder al panel general para resetear contraseñas de otros usuarios.
- **Credenciales por defecto:** Creado desde BD según necesidad del negocio (se resetean a su número de documento).

Desarrollado para optimizar la comunicación corporativa interna 💼.
