<?php
$servername = "localhost"; // O la IP de tu servidor de base de datos
$username = "root"; // Tu usuario de la base de datos
$password = ""; // Tu contraseña de la base de datos (por defecto en XAMPP es vacío)
$dbname = "4588094_4588094"; // Nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
