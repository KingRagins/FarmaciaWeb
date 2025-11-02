<?php
// Inicia la sesión
session_start();

// 1. Lógica de validación de sesión
// Si no hay una sesión de usuario, redirige inmediatamente al login.
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
    <h3 class="text-center text-secondary">Pedidos Disponibles</h3>

    <?php
    require_once "../logins/logouts/conexion.php"; 
    $conexion = Conexion::Conectar();
    $sql_conexion = $conexion->query("SELECT id_venta, id_usuario, codigo_unico, estado, fecha_apartado FROM ventas");
    ?>
         <table class="table table-bordered table-hover w-100 tabla-empleados" id="example">
        <thead>
            <tr>
                <th scope="col">id_venta</th>
                <th scope="col">id_usuario</th>
                <th scope="col">Codigo Del Pedido</th>
                <th scope="col">Estado Del Pedido</th>
                <th scope="col">Fecha de apartado</th>
                
            </tr>
        </thead>
        <tbody id="tabla_pedidos" class="users_table_body">
            <?php while ($sql = $sql_conexion->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                    <td><?php echo $sql["id_venta"]?></td>
                    <td><?php echo $sql["id_usuario"]; ?></td>
                    <td><?php echo $sql["codigo_unico"]; ?></td>
                    <td><?php echo $sql["estado"]; ?></td>
                    <td><?php echo $sql["fecha_apartado"]; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>



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
