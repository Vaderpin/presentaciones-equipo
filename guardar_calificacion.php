<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'maestro') {
    $_SESSION['error'] = "Acceso no autorizado.";
    header("Location: index.php");
    exit();
}

// Verificar que se hayan enviado los datos
if (!isset($_POST['id_tarea'], $_POST['id_alumno'], $_POST['calificacion'])) {
    $_SESSION['error'] = "Datos incompletos.";
    header("Location: maestro_dashboard.php");
    exit();
}

$id_tarea = intval($_POST['id_tarea']);
$id_alumno = intval($_POST['id_alumno']);
$calificacion = floatval($_POST['calificacion']);
$comentarios = trim($_POST['comentarios'] ?? '');

// Validar calificación dentro de rango
if ($calificacion < 0 || $calificacion > 100) {
    $_SESSION['error'] = "La calificación debe estar entre 0 y 100.";
    header("Location: calificar_tarea.php?id_tarea=$id_tarea&id_alumno=$id_alumno");
    exit();
}

// Actualizar la entrega
$stmt = $conn->prepare("UPDATE entregas SET calificacion = ?, comentarios = ? WHERE id_tarea = ? AND id_alumno = ?");
$stmt->bind_param("dsii", $calificacion, $comentarios, $id_tarea, $id_alumno);

if ($stmt->execute()) {
    $_SESSION['exito'] = "✅ Calificación guardada correctamente.";
} else {
    $_SESSION['error'] = "❌ Error al guardar calificación.";
}

// Redirigir de nuevo al listado de entregas
header("Location: ver_tarea.php?id=$id_tarea");
exit();
