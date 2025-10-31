<?php
session_start();

// === PROTECCIÓN DE ACCESO ===
if (!isset($_SESSION['s_usuario']) || empty($_SESSION['s_usuario'])) {
    header("Location: /Farmacia/logins/login_admin.php");
    exit();
}


// 2. Cabeceras HTTP para prevenir el caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Incluye la parte superior de tu página
require_once "view/verpedidos.php";
?>

<!--INICIO DEL CONTENIDO PRINCIPAL-->

<div class="container">

<!-- Mensaje de bienvenida al usuario que logeo-->
 <h1>Pedidos Disponibles</h1>
<p>asasadasdasd</p>


<script src="../logins/jquery/jquery-3.3.1.min.js"></script>
<script src="../logins/bootstrap/js/bootstrap.min.js"></script>
<script src="../logins/popper/popper.min.js"></script>
<script src="../logins/Plugins/sweetalert2/sweetalert2.all.min.js"></script>
<!-- FIN DEL CONTENIDO PRINCIPAL-->
<?php require_once "view/parte_inferior.php"?>
    <script>
        window.history.pushState(null, null, location.href);
        window.onpopstate = function() {
            window.history.go(1);
        };
    </script>
    <script src="offline_service/boostrap/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="offline_service/fontawesome-free/css/all.min.css">
</div>
<!--la carpeta offline_service tiene como proposito que los modales y los logitos
    de modificar o eliminar o desactivar usuarios, productos etc, funcione con o sin internet-->
