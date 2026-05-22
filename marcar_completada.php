<?php
session_start();
require 'db.php';

// Verificar sesión
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'alumno') {
    $_SESSION['error'] = "⚠️ Sesión no iniciada correctamente.";
    header("Location: index.php");
    exit();
}

// Obtener id_alumno desde la BD usando el correo de sesión
$correo = $_SESSION['usuario'];
$stmtUser = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND tipo = 'alumno'");
$stmtUser->bind_param("s", $correo);
$stmtUser->execute();
$resUser = $stmtUser->get_result()->fetch_assoc();

if (!$resUser) {
    $_SESSION['error'] = "⚠️ No se encontró el usuario.";
    header("Location: index.php");
    exit();
}

$id_alumno = $resUser['id'];

// Validar que venga el id_tarea
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_tarea'])) {
    $_SESSION['error'] = "Error: Datos incompletos.";
    header("Location: alumno_dashboard.php");
    exit();
}

$id_tarea = intval($_POST['id_tarea']);

// Verificar si ya entregó esta tarea
$stmtCheck = $conn->prepare("SELECT id FROM entregas WHERE id_tarea = ? AND id_alumno = ?");
$stmtCheck->bind_param("ii", $id_tarea, $id_alumno);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Ya entregaste esta tarea anteriormente.";
    header("Location: ver_tareaAlumno.php?id=$id_tarea");
    exit();
}

// Insertar entrega en la BD sin archivo
$stmt = $conn->prepare("INSERT INTO entregas (id_tarea, id_alumno, archivo_entrega) VALUES (?, ?, NULL)");
$stmt->bind_param("ii", $id_tarea, $id_alumno);

if ($stmt->execute()) {
    $_SESSION['exito'] = "✅ Tarea marcada como entregada exitosamente.";
} else {
    $_SESSION['error'] = "❌ Error al guardar en la base de datos: " . $conn->error;
}

header("Location: ver_tareaAlumno.php?id=$id_tarea");
exit();
?>
