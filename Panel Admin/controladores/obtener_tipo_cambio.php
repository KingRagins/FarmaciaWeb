<?php
// controladores/obtener_tipo_cambio.php
session_start();
header('Content-Type: application/json');

$tipo_cambio = 50.00;
if (
    isset($_SESSION['tipo_cambio']) && 
    isset($_SESSION['tipo_cambio_timestamp']) &&
    (time() - $_SESSION['tipo_cambio_timestamp']) < 43200
) {
    $tipo_cambio = $_SESSION['tipo_cambio'];
}

echo json_encode([
    'success' => true,
    'tipo_cambio' => $tipo_cambio
]);
?>