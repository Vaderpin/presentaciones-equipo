<?php
// ============================================================
//  db.php  —  Configuración de conexión a la base de datos
//  Cambia los 4 valores de abajo con los datos de tu hosting
// ============================================================

// Configuración de conexión a MySQL (Clever Cloud)
$servername = "bcaccxkcxwvo7htc3zil-mysql.services.clever-cloud.com";
$username = "u7riqhzj2o05vqwi";
$password = "0pKje6NOkuxdzIJ6Dv3r";
$dbname = "bcaccxkcxwvo7htc3zil";
$port = 3306; // Puerto requerido para conexiones externas


// Crear conexión especificando el puerto
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Configurar charset UTF-8
$conn->set_charset("utf8mb4");

// Verificar la conexión
if ($conn->connect_error) {
    // En producción evita mostrar el error directamente al usuario
    error_log("Conexión fallida: " . $conn->connect_error);
    die("Error de conexión. Por favor intenta más tarde.");
}
?>
