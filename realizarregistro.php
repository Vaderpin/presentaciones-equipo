<?php
session_start();

// Credenciales de la base de datos
require_once 'db.php';

// Obtener los datos del formulario
$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];
$tipo = $_POST['tipo'];

// Validar si el correo ya está registrado
$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Si ya existe, redirigir con mensaje de error
    $_SESSION['error'] = "El correo ya está registrado. Por favor, utiliza otro.";
    header("Location: registro.php");
    exit();  // Asegúrate de usar exit() para evitar que el script continúe
} else {
    // Si no existe, insertar el nuevo usuario
    $sql = "INSERT INTO usuarios (nombre, correo, contrasena, tipo) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $correo, $contrasena, $tipo);

    if ($stmt->execute()) {
        // Redirigir a la página de inicio de sesión con mensaje de éxito
        $_SESSION['success'] = "¡Registro exitoso! Ahora puedes iniciar sesión.";
        header("Location: registro.php");
        exit();  // Asegúrate de usar exit() para evitar que el script continúe
    } else {
        // Si hay un error en la inserción
        $_SESSION['error'] = "Hubo un error al registrar el usuario. Inténtalo nuevamente.";
        header("Location: registro.php");
        exit();  // Asegúrate de usar exit() para evitar que el script continúe
    }
}

$conn->close();
?>
