<?php
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['s_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    ob_end_flush();
    exit();
}

require_once __DIR__ . '/../../logins/logouts/conexion.php';
$conexion = Conexion::Conectar();

$codigo = trim($_POST['codigo_pedido'] ?? '');
$metodo = $_POST['metodo_pago'] ?? '';
$monto_recibido = $_POST['monto_recibido'] ?? null;
$vuelto_usd = $_POST['vuelto_usd'] ?? null;
$banco = $_POST['banco'] ?? null;
$referencia = $_POST['referencia_pago'] ?? null;

// TIPO DE CAMBIO
$tipo_cambio = 50.00;
if (
    isset($_SESSION['tipo_cambio']) && 
    isset($_SESSION['tipo_cambio_timestamp']) &&
    (time() - $_SESSION['tipo_cambio_timestamp']) < 43200
) {
    $tipo_cambio = $_SESSION['tipo_cambio'];
}

if (empty($codigo) || empty($metodo)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    ob_end_flush();
    exit();
}

try {
    $conexion->beginTransaction();

    $sql = "SELECT v.id_venta, v.id_usuario, u.nombre AS cliente, SUM(dp.dp_cantidad * dp.monto_unico) AS total
            FROM ventas v
            INNER JOIN detalles_pedido dp ON v.id_venta = dp.id_venta
            INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
            WHERE v.codigo_unico = ? AND v.estado = 'apartado'
            GROUP BY v.id_venta, v.id_usuario, u.nombre LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$codigo]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) throw new Exception('Pedido no encontrado o ya pagado');

    $id_venta = $venta['id_venta'];
    $id_usuario = $venta['id_usuario'];
    $total = $venta['total'];
    $cliente = $venta['cliente'];

    $sql_productos = "SELECT p.nombre, dp.dp_cantidad, dp.monto_unico, (dp.dp_cantidad * dp.monto_unico) AS subtotal
                      FROM detalles_pedido dp
                      INNER JOIN productos p ON dp.id_producto = p.id_producto
                      WHERE dp.id_venta = ?";
    $stmt_productos = $conexion->prepare($sql_productos);
    $stmt_productos->execute([$id_venta]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    $sql_pago = "INSERT INTO pagos 
                 (id_venta, id_usuario, monto_unico, tipo_cambio_usd, metodo_pago, estado_pago, banco, referencia, fecha_pago) 
                 VALUES (?, ?, ?, ?, ?, 'pagado', ?, ?, NOW())";
    $stmt_pago = $conexion->prepare($sql_pago);
    $stmt_pago->execute([$id_venta, $id_usuario, $total, $tipo_cambio, $metodo, $banco, $referencia]);

    $tables = ['detalles_pedido', 'detalles_pago', 'ventas'];
    foreach ($tables as $table) {
        $sql_delete = "DELETE FROM $table WHERE id_venta = ?";
        $stmt_delete = $conexion->prepare($sql_delete);
        $stmt_delete->execute([$id_venta]);
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pago registrado correctamente',
        'recibo' => [
            'codigo' => $codigo,
            'cliente' => $cliente,
            'total' => $total,
            'total_usd' => round($total / $tipo_cambio, 2),
            'tipo_cambio' => $tipo_cambio,
            'metodo' => $metodo,
            'vuelto_usd' => $vuelto_usd,
            'banco' => $banco,
            'referencia' => $referencia,
            'fecha' => date('d/m/Y H:i'),
            'productos' => $productos
        ]
    ]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();
exit();
?>