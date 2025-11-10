<?php
// Inicia la sesión
session_start();

date_default_timezone_set('America/Caracas');
$fecha_hoy = date('d/m/Y');

// 1. Lógica de validación de sesión
if (!isset($_SESSION['s_usuario']) || empty($_SESSION['s_usuario'])) {
    header("Location: /Farmacia/logins/login_admin.php");
    exit();
}

// 2. Cabeceras HTTP para prevenir el caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Incluir conexión a DB
include_once "../logins/conexion.php";
$objeto = new Conexion();
$conexion = $objeto->conectar();

// 4. Obtener parámetros de fecha (hoy por defecto)
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$fecha_anterior = date('Y-m-d', strtotime($fecha_seleccionada . ' -1 day'));
$fecha_siguiente = date('Y-m-d', strtotime($fecha_seleccionada . ' +1 day'));

// 5. GESTIÓN DEL TIPO DE CAMBIO CON SESIÓN Y EXPIRACIÓN (12 HORAS)
$valor_defecto = 50.00;
$expiracion_segundos = 43200;

// Verificar si el usuario está enviando un nuevo tipo de cambio
if (isset($_GET['tipo_cambio']) && is_numeric($_GET['tipo_cambio'])) {
    $nuevo_tipo = floatval($_GET['tipo_cambio']);
    if ($nuevo_tipo > 0) {
        $_SESSION['tipo_cambio'] = $nuevo_tipo;
        $_SESSION['tipo_cambio_timestamp'] = time();

        // === GUARDAR EN BD ===
        try {
            $sql_guardar = "INSERT INTO pagos (monto_unico, tipo_cambio_usd, metodo_pago, estado_pago, fecha_pago) 
                            VALUES (0, ?, 'config_tc', 'config', NOW())";
            $stmt_guardar = $conexion->prepare($sql_guardar);
            $stmt_guardar->execute([$nuevo_tipo]);
        } catch (Exception $e) {
            // Silenciar error (no romper flujo)
        }
    }
}
// Verificar si existe en sesión y no ha expirado
// === NUEVA LÓGICA: CARGAR DE BD SI NO HAY SESIÓN VÁLIDA ===
$tipo_cambio = $valor_defecto;

try {
    // 1. Intentar cargar desde sesión (válida)
    if (
        isset($_SESSION['tipo_cambio']) && 
        isset($_SESSION['tipo_cambio_timestamp']) &&
        (time() - $_SESSION['tipo_cambio_timestamp']) < $expiracion_segundos
    ) {
        $tipo_cambio = $_SESSION['tipo_cambio'];
    } else {
        // 2. Si no hay sesión válida → cargar último valor de la BD
        $sql_ultimo = "SELECT tipo_cambio_usd FROM pagos WHERE tipo_cambio_usd IS NOT NULL ORDER BY id_pago DESC LIMIT 1";
        $stmt_ultimo = $conexion->prepare($sql_ultimo);
        $stmt_ultimo->execute();
        $ultimo = $stmt_ultimo->fetch(PDO::FETCH_ASSOC);

        if ($ultimo && $ultimo['tipo_cambio_usd'] !== null) {
            $tipo_cambio = floatval($ultimo['tipo_cambio_usd']);
            // Restaurar en sesión para evitar consultas repetidas
            $_SESSION['tipo_cambio'] = $tipo_cambio;
            $_SESSION['tipo_cambio_timestamp'] = time();
        }
    }
} catch (Exception $e) {
    // En caso de error, usar valor por defecto
    $tipo_cambio = $valor_defecto;
}

// Calcular tiempo restante
// === TIEMPO RESTANTE CON SEGUNDOS ===
$timestamp_inicio = $_SESSION['tipo_cambio_timestamp'] ?? time();
$tiempo_restante = $expiracion_segundos - (time() - $timestamp_inicio);

// Evitar negativos
$tiempo_restante = max(0, $tiempo_restante);

$horas = floor($tiempo_restante / 3600);
$minutos = floor(($tiempo_restante % 3600) / 60);
$segundos = $tiempo_restante % 60;

$tiempo_texto = sprintf(
    "%dh %02dmin %02dseg",
    $horas,
    $minutos,
    $segundos
);

// Pasar al JS
$tiempo_restante_inicial = $tiempo_restante;




// 6. CONSULTAS PRINCIPALES
$sql_ventas_pagadas = "
    SELECT COUNT(*) as total_ventas, COALESCE(SUM(monto_unico), 0) as total_ingresos
    FROM pagos 
    WHERE DATE(fecha_pago) = :fecha 
    AND estado_pago = 'pagado'
";
$stmt_ventas = $conexion->prepare($sql_ventas_pagadas);
$stmt_ventas->bindParam(':fecha', $fecha_seleccionada);
$stmt_ventas->execute();
$ventas_pagadas = $stmt_ventas->fetch(PDO::FETCH_ASSOC);

$sql_pedidos_apartados = "
    SELECT COUNT(*) as total_apartados
    FROM ventas 
    WHERE DATE(fecha_apartado) = :fecha 
    AND estado = 'apartado'
";
$stmt_apartados = $conexion->prepare($sql_pedidos_apartados);
$stmt_apartados->bindParam(':fecha', $fecha_seleccionada);
$stmt_apartados->execute();
$pedidos_apartados = $stmt_apartados->fetch(PDO::FETCH_ASSOC);

$sql_pagos_confirmados = "
    SELECT COUNT(*) as total_pagos
    FROM pagos 
    WHERE DATE(fecha_pago) = :fecha 
    AND estado_pago = 'pagado'
";
$stmt_pagos = $conexion->prepare($sql_pagos_confirmados);
$stmt_pagos->bindParam(':fecha', $fecha_seleccionada);
$stmt_pagos->execute();
$pagos_confirmados = $stmt_pagos->fetch(PDO::FETCH_ASSOC);

$sql_stock_bajo = "
    SELECT nombre, cantidad, precio
    FROM productos 
    WHERE cantidad <= 5
    ORDER BY cantidad ASC
    LIMIT 10
";
$stmt_stock = $conexion->prepare($sql_stock_bajo);
$stmt_stock->execute();
$stock_bajo = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);

// 7. FUNCIÓN PARA URLs CON TIPO DE CAMBIO
function url_con_tipo($url) {
    global $tipo_cambio;
    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . 'tipo_cambio=' . $tipo_cambio;
}

// 8. Incluir parte superior
require_once "view/resumen_dia.php";
?>

<!--INICIO DEL CONTENIDO PRINCIPAL-->
<!-- CONTENIDO PARA TODOS LOS ROLES -->
<div class="container-fluid">
    <?php include 'resumen_content.php'; ?>
</div>

<style>
.disabled-full-section {
    position: relative;
    pointer-events: none;
    filter: blur(6px);
    opacity: 0.5;
    user-select: none;
    min-height: 500px;
}
.restriction-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(220, 53, 69, 0.95);
    color: white;
    padding: 15px 30px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 1.3rem;
    z-index: 1000;
    pointer-events: none;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    white-space: nowrap;
}

#contador-tiempo {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #e74c3c;
}
.text-danger { color: #dc3545 !important; }

</style>

<script src="../logins/jquery/jquery-3.3.1.min.js"></script>
<script src="../logins/bootstrap/js/bootstrap.min.js"></script>
<script src="../logins/popper/popper.min.js"></script>
<script src="../logins/Plugins/sweetalert2/sweetalert2.all.min.js"></script>

<?php require_once "view/parte_inferior.php"; ?>

<script>
    window.history.pushState(null, null, location.href);
    window.onpopstate = function() {
        window.history.go(1);
    };
</script>
<script src="offline_service/boostrap/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="offline_service/fontawesome-free/css/all.min.css">
</div>