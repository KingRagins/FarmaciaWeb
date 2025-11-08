<?php
// Inicia la sesión
session_start();

// 1. Lógica de validación de sesión
if (!isset($_SESSION['s_usuario']) || empty($_SESSION['s_usuario'])) {
    header("Location: /Farmacia/logins/login_admin.php");
    exit();
}

// 2. Cabeceras HTTP para prevenir el caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Incluye la parte superior
require_once "view/verventas.php";
?>

<!--INICIO DEL CONTENIDO PRINCIPAL-->
<div class="container">

<!-- BUSCADOR -->
    <div class="mb-3">
        <input type="search" 
               id="buscador_ventas" 
               class="form-control" 
               placeholder="Buscar ventas por ID, banco, referencia, fecha..."
               style="max-width: 400px;">
    </div>

    <h3 class="text-center text-secondary">Ventas Realizadas</h3>

    <?php
    require_once "../logins/logouts/conexion.php"; 
    $conexion = Conexion::Conectar();
    
    // ========== PAGINACIÓN ==========
    $ventas_por_pagina = 10;
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;

    $offset = ($pagina_actual - 1) * $ventas_por_pagina;

    // Total de ventas
    $sql_total = "SELECT COUNT(*) as total FROM pagos WHERE estado_pago = 'pagado'";
    $stmt_total = $conexion->prepare($sql_total);
    $stmt_total->execute();
    $total_ventas = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

    $total_paginas = ceil($total_ventas / $ventas_por_pagina);

    // Query con tipo_cambio_usd y cálculo de USD
    $sql_conexion = $conexion->prepare("
        SELECT 
            id_pago, id_venta, id_usuario, banco, metodo_pago, 
            monto_unico, tipo_cambio_usd, referencia, fecha_pago,
            ROUND(monto_unico / tipo_cambio_usd, 2) AS monto_usd
        FROM pagos 
        WHERE estado_pago = 'pagado'
        ORDER BY fecha_pago DESC 
        LIMIT :limit OFFSET :offset
    ");
    
    $sql_conexion->bindValue(':limit', $ventas_por_pagina, PDO::PARAM_INT);
    $sql_conexion->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql_conexion->execute();

    // Totales generales
    $sql_totales = "SELECT 
                        SUM(monto_unico) AS total_bs,
                        SUM(monto_unico / tipo_cambio_usd) AS total_usd
                    FROM pagos 
                    WHERE estado_pago = 'pagado'";
    $stmt_totales = $conexion->prepare($sql_totales);
    $stmt_totales->execute();
    $totales = $stmt_totales->fetch(PDO::FETCH_ASSOC);
    ?>

    <!-- ========== INFORMACIÓN DE PAGINACIÓN ========== -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted">
            Mostrando <?php echo ($offset + 1); ?> - <?php echo min($offset + $ventas_por_pagina, $total_ventas); ?> de <?php echo $total_ventas; ?> ventas
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover w-100 tabla-empleados" id="tabla_ventas">
            <thead class="thead-dark">
                <tr>
                    <th>id pago</th>
                    <th>id venta</th>
                    <th>id_usuario</th>
                    <th>Banco</th>
                    <th>Método</th>
                    <th>Monto BS</th>
                    <th>USD</th>
                    <th>Tipo Cambio</th>
                    <th>Referencia</th>
                    <th>Fecha</th>
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
                        <td>Bs. <?php echo number_format($sql["monto_unico"], 2); ?></td>
                        <td class="text-success font-weight-bold">$ <?php echo number_format($sql["monto_usd"], 2); ?></td>
                        <td>1 USD = <?php echo number_format($sql["tipo_cambio_usd"], 2); ?> BS</td>
                        <td><?php echo $sql["referencia"]; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($sql["fecha_pago"])); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot class="thead-light">
                <tr>
                    <th colspan="5" class="text-right">TOTALES:</th>
                    <th>Bs. <?php echo number_format($totales['total_bs'] ?? 0, 2); ?></th>
                    <th class="text-success">$ <?php echo number_format($totales['total_usd'] ?? 0, 2); ?></th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- ========== PAGINACIÓN ========== -->
    <?php if ($total_paginas > 1): ?>
    <nav aria-label="Paginación de ventas">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>">&laquo;</a>
            </li>
            <?php 
            $rango = 2;
            for ($i = max(1, $pagina_actual - $rango); $i <= min($total_paginas, $pagina_actual + $rango); $i++): 
            ?>
                <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

<script src="../logins/jquery/jquery-3.3.1.min.js"></script>
<script src="../logins/bootstrap/js/bootstrap.min.js"></script>
<script src="../logins/popper/popper.min.js"></script>
<script src="../logins/Plugins/sweetalert2/sweetalert2.all.min.js"></script>
<script src="controladores/buscador.js"></script>

<script>
  $(document).ready(function () {
    aplicarBuscador("tabla_ventas", "buscador_ventas");
  });
</script>

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