<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

// Verificar sesión
if (!isset($_SESSION['id'])) {
    $_SESSION['error'] = "⚠️ Sesión no iniciada correctamente.";
    header("Location: index.php");
    exit();
}

$id_alumno = $_SESSION['id'];

// Validar envío de formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_tarea'], $_FILES['archivo'])) {
    $_SESSION['error'] = "Error: Datos incompletos.";
    header("Location: alumno_dashboard.php");
    exit();
}

$id_tarea = intval($_POST['id_tarea']);
$archivo = $_FILES['archivo'];

// Validar archivo
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "Error al subir el archivo.";
    header("Location: ver_tareaAlumno.php?id=$id_tarea");
    exit();
}

// Configurar Google Cloud
putenv('GOOGLE_APPLICATION_CREDENTIALS=proyecto-para-almacenamiento-ff8cd1f8f073.json');
$storage = new StorageClient();
$bucket = $storage->bucket('mis-archivos-javier');

// Crear nombre único
$nombreOriginal = basename($archivo['name']);
$nombreUnico = 'Tareas/' . uniqid('entrega_') . '_' . $nombreOriginal;

// Subir a Google Cloud
try {
    $bucket->upload(
        fopen($archivo['tmp_name'], 'r'),
        ['name' => $nombreUnico]
    );
} catch (Exception $e) {
    $_SESSION['error'] = "❌ Error al subir a Google Cloud: " . $e->getMessage();
    header("Location: ver_tareaAlumno.php?id=$id_tarea");
    exit();
}

// Insertar en la base de datos
$stmt = $conn->prepare("INSERT INTO entregas (id_tarea, id_alumno, archivo_entrega) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $id_tarea, $id_alumno, $nombreUnico);

if ($stmt->execute()) {
    $_SESSION['exito'] = "✅ Tarea entregada exitosamente.";
} else {
    $_SESSION['error'] = "❌ Error al guardar en la base de datos.";
}

// Redirigir a la vista de la tarea entregada
header("Location: ver_tareaAlumno.php?id=$id_tarea");
exit();
?>
