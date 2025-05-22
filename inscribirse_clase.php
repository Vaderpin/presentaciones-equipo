<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Credenciales de la base de datos
$servername = "localhost"; // O la IP de tu servidor de base de datos
$username = "root"; // Tu usuario de la base de datos
$password = ""; // Tu contraseña de la base de datos (por defecto en XAMPP es vacío)
$dbname = "4588094_4588094"; // Nombre de tu base de datos

// Crear conexión
$conexion = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['codigo'], $_POST['id_alumno'])) {
    $codigo_clase = trim($_POST['codigo']);  // Corregido el nombre de la columna
    $id_alumno = trim($_POST['id_alumno']);

    if (empty($codigo_clase) || empty($id_alumno)) {
        echo json_encode(["success" => false, "message" => "Faltan datos."]);
        exit();
    }

    // Buscar el ID de la clase a partir del código
    $stmt = $conexion->prepare("SELECT id FROM clases WHERE codigo_clase = ?");
    $stmt->bind_param("s", $codigo_clase);
    $stmt->execute();
    $stmt->bind_result($id_clase);
    $stmt->fetch();
    $stmt->close();

    if (!$id_clase) {
        echo json_encode(["success" => false, "message" => "Clase no encontrada."]);
        exit();
    }

    // Verificar si el alumno ya está inscrito en la clase
    $stmt = $conexion->prepare("SELECT 1 FROM alumnos_clases WHERE id_alumno = ? AND id_clase = ?");
    $stmt->bind_param("ii", $id_alumno, $id_clase);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // El alumno ya está inscrito en la clase
        echo json_encode(["success" => false, "message" => "Ya estás inscrito en esta clase."]);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Insertar en la tabla alumnos_clases
    $stmt = $conexion->prepare("INSERT INTO alumnos_clases (id_alumno, id_clase) VALUES (?, ?)");
    $stmt->bind_param("ii", $id_alumno, $id_clase);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Inscripción exitosa."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al inscribirse."]);
    }
    $stmt->close();

    // Cerrar la conexión
    $conexion->close();
}
?>
