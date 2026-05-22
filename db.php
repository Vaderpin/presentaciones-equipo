<?php
// ============================================================
//  db.php  —  Configuración de conexión a la base de datos
//  Cambia los 4 valores de abajo con los datos de tu hosting
// ============================================================

$servername = "fdb1027.biz.nf";   // Host del servidor MySQL remoto
$username   = "4588094_4588094";   // Usuario de la base de datos
$password   = "TU_CONTRASEÑA";     // Contraseña del usuario
$dbname     = "4588094_4588094";   // Nombre de la base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Configurar charset UTF-8
$conn->set_charset("utf8mb4");

// Verificar la conexión
if ($conn->connect_error) {
    // En producción evita mostrar el error directamente al usuario
    error_log("Conexión fallida: " . $conn->connect_error);
    die("Error de conexión. Por favor intenta más tarde.");
}
?>
