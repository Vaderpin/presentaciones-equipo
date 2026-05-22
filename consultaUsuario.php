<?php
ob_start(); // ← SOLUCIÓN: Guarda la salida en búfer para permitir redirecciones con header() incluso si hay echos
session_start();

// Credenciales de la base de datos
require_once 'db.php';

// Verificar si el formulario se envió
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $correo = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    // Mostrar los datos recibidos para depuración (Ya no romperán el header)
    echo "Correo recibido: $correo <br>";
    echo "Contraseña recibida: $contrasena <br>";

    // Consultar si existe el usuario con ese correo y contraseña
    $sql = "SELECT * FROM usuarios WHERE correo = ? AND contrasena = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $correo, $contrasena); 
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si el usuario existe
    if ($result->num_rows > 0) {
        // Usuario existe, iniciar sesión
        $user = $result->fetch_assoc();
        $_SESSION['id'] = $user['id']; 
        $_SESSION['usuario'] = $correo;
        $_SESSION['tipo'] = $user['tipo']; 

        // Redirigir dependiendo del tipo de usuario
        if ($_SESSION['tipo'] == 'alumno') {
            header("Location: alumno_dashboard.php"); 
        } else {
            header("Location: maestro_dashboard.php"); 
        }
        exit(); 
    } else {
        // Si no existe el usuario, redirigir al login con mensaje de error
        $_SESSION['error'] = "Usuario o contraseña incorrectos";
        header("Location: index.php");
        exit(); 
    }
}

$conn->close();
ob_end_flush(); // ← Libera el búfer al final del script
?>
