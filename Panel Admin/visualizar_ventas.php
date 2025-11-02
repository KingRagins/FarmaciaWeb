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
require_once "view/verventas.php";
?>

<!--INICIO DEL CONTENIDO PRINCIPAL-->
<div class="container">
    <h3 class="text-center text-secondary">Pedidos Disponibles</h3>

    <?php
    require_once "../logins/logouts/conexion.php"; 
    $conexion = Conexion::Conectar();
    $sql_conexion = $conexion->query("SELECT id_pago, id_venta, id_usuario, banco, metodo_pago, monto_unico, referencia, fecha_pago FROM pagos");
    ?>
         <table class="table table-bordered table-hover w-100 tabla-empleados" id="example">
        <thead>
            <tr>
                <th scope="col">id pago</th>
                <th scope="col">id venta</th>
                <th scope="col">id_usuario</th>
                <th scope="col">Banco</th>
                <th scope="col">Metodo de pago</th>
                <th scope="col">Monto Total</th>
                <th scope="col">N° de Referencia</th>
                <th scope="col">Fecha de pago</th>
            </tr>
        </thead>
        <tbody id="tabla_ventas" class="users_table_body">
            <?php while ($sql = $sql_conexion->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                    <td><?php echo $sql["id_pago"]?></td>
                    <td><?php echo $sql["id_venta"]; ?></td>
                    <td><?php echo $sql["id_usuario"]; ?></td>
                    <td><?php echo $sql["banco"]; ?></td>
                    <td><?php echo $sql["metodo_pago"]; ?></td>
                    <td><?php echo $sql["monto_unico"]; ?></td>
                    <td><?php echo $sql["referencia"]; ?></td>
                    <td><?php echo $sql["fecha_pago"]; ?></td>
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

