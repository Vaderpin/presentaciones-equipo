<?php
session_start();

// Verifica que el usuario sea un maestro
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'maestro') {
    header("Location: index.php");
    exit();
}

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "4588094_4588094";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar que se ha enviado el ID de la clase
if (isset($_POST['clase_id'])) {
    $claseId = $_POST['clase_id'];

    // Consulta para obtener los alumnos inscritos en la clase
    $sqlAlumnos = "SELECT a.id AS alumno_id, a.nombre AS alumno_nombre
                   FROM alumnos_clases ac
                   JOIN usuarios a ON ac.id_alumno = a.id
                   WHERE ac.id_clase = ?";
    $stmtAlumnos = $conn->prepare($sqlAlumnos);
    $stmtAlumnos->bind_param("i", $claseId);
    $stmtAlumnos->execute();
    $resultAlumnos = $stmtAlumnos->get_result();

    $alumnos = [];

    // Iterar sobre los alumnos para obtener las estadísticas
    while ($alumno = $resultAlumnos->fetch_assoc()) {
        $alumnoId = $alumno['alumno_id'];

        // Consulta para obtener el número de tareas entregadas
        $sqlEntregadas = "SELECT COUNT(*) AS entregadas
                          FROM entregas e
                          JOIN tareas t ON e.id_tarea = t.id
                          WHERE e.id_alumno = ? AND t.id_clase = ?";
        $stmtEntregadas = $conn->prepare($sqlEntregadas);
        $stmtEntregadas->bind_param("ii", $alumnoId, $claseId);
        $stmtEntregadas->execute();
        $resultEntregadas = $stmtEntregadas->get_result();
        $entregadas = $resultEntregadas->fetch_assoc()['entregadas'];

        // Consulta para obtener el número total de tareas
        $sqlTotalTareas = "SELECT COUNT(*) AS total
                           FROM tareas t
                           WHERE t.id_clase = ?";
        $stmtTotalTareas = $conn->prepare($sqlTotalTareas);
        $stmtTotalTareas->bind_param("i", $claseId);
        $stmtTotalTareas->execute();
        $resultTotalTareas = $stmtTotalTareas->get_result();
        $totalTareas = $resultTotalTareas->fetch_assoc()['total'];

        // Calcular tareas no entregadas
        $noEntregadas = $totalTareas - $entregadas;

        // Guardar la información del alumno
        $alumnos[] = [
            'id' => $alumno['alumno_id'],
            'nombre' => $alumno['alumno_nombre'],
            'entregadas' => $entregadas,
            'noEntregadas' => $noEntregadas
        ];
    }

    // Devolver la información en formato JSON
    echo json_encode(['alumnos' => $alumnos]);
}

$conn->close();
?>
