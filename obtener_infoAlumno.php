<?php
session_start();
if (!isset($_POST['alumno_id']) || !isset($_POST['clase_id'])) {
    echo json_encode(['error' => 'ID de alumno o ID de clase no proporcionado']);
    exit();
}

$alumno_id = $_POST['alumno_id'];
$clase_id = $_POST['clase_id']; // Tomar el ID de la clase también

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "4588094_4588094";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener información del alumno (Nombre y Correo)
$sql = "SELECT u.nombre, u.correo, 
               (SELECT COUNT(*) FROM entregas e WHERE e.id_alumno = ? AND e.fecha_entrega IS NOT NULL AND e.id_tarea IN (SELECT id FROM tareas WHERE id_clase = ?)) AS tareas_entregadas,
               (SELECT COUNT(*) FROM tareas t WHERE t.id_clase = ? AND t.id NOT IN (SELECT e.id_tarea FROM entregas e WHERE e.id_alumno = ?)) AS tareas_pendientes
        FROM usuarios u WHERE u.id = ? AND u.tipo = 'alumno'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $alumno_id, $clase_id, $clase_id, $alumno_id, $alumno_id);
$stmt->execute();
$result = $stmt->get_result();
$alumno = $result->fetch_assoc();

// Obtener detalles de las tareas entregadas (Título, Calificación, Fecha, Comentarios, ID de la tarea)
$sqlTareas = "SELECT t.id AS id_tarea, t.titulo, e.calificacion, e.comentarios, e.fecha_entrega
              FROM entregas e
              JOIN tareas t ON e.id_tarea = t.id
              WHERE e.id_alumno = ? AND t.id_clase = ?";

$stmtTareas = $conn->prepare($sqlTareas);
$stmtTareas->bind_param("ii", $alumno_id, $clase_id);
$stmtTareas->execute();
$resultTareas = $stmtTareas->get_result();

$tareas_entregadas_detalle = [];
while ($tarea = $resultTareas->fetch_assoc()) {
    $tareas_entregadas_detalle[] = [
        'id_tarea' => $tarea['id_tarea'], // Incluimos el ID de la tarea
        'titulo' => $tarea['titulo'],
        'calificacion' => $tarea['calificacion'] ? $tarea['calificacion'] : 'No calificada',
        'comentarios' => $tarea['comentarios'] ? $tarea['comentarios'] : 'No comentarios',
        'fecha_entrega' => $tarea['fecha_entrega'] ? $tarea['fecha_entrega'] : 'No entregada',
    ];
}

// Obtener las tareas no entregadas
$sqlTareasNoEntregadas = "SELECT t.id AS id_tarea, t.titulo
                          FROM tareas t
                          LEFT JOIN entregas e ON t.id = e.id_tarea AND e.id_alumno = ?
                          WHERE t.id_clase = ? AND e.id_alumno IS NULL";

$stmtTareasNoEntregadas = $conn->prepare($sqlTareasNoEntregadas);
$stmtTareasNoEntregadas->bind_param("ii", $alumno_id, $clase_id);
$stmtTareasNoEntregadas->execute();
$resultTareasNoEntregadas = $stmtTareasNoEntregadas->get_result();

$tareas_no_entregadas = [];
while ($tareaNoEntregada = $resultTareasNoEntregadas->fetch_assoc()) {
    $tareas_no_entregadas[] = [
        'id_tarea' => $tareaNoEntregada['id_tarea'], // Incluimos el ID de la tarea
        'titulo' => $tareaNoEntregada['titulo']
    ];
}

$conn->close();

header('Content-Type: application/json');

// Retornar la información del alumno junto con las tareas entregadas y no entregadas
if ($alumno) {
    echo json_encode([
        'nombre' => $alumno['nombre'],
        'correo' => $alumno['correo'],
        'tareas_entregadas' => $alumno['tareas_entregadas'],
        'tareas_pendientes' => $alumno['tareas_pendientes'],
        'tareas_entregadas_detalle' => $tareas_entregadas_detalle,
        'tareas_no_entregadas' => $tareas_no_entregadas,
    ]);
} else {
    echo json_encode(['error' => 'No se encontró al alumno']);
}
?>
