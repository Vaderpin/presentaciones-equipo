<?php
// ============================================================
//  db.php  —  Configuración de conexión a la base de datos
//  Cambia los 4 valores de abajo con los datos de tu hosting
// ============================================================

// Configuración de conexión a MySQL
$servername = "fdb1031.biz.nf";
$username = "4416457_wpressc04c61dc";
$password = "Javier993";
$dbname = "4416457_wpressc04c61dc";


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
