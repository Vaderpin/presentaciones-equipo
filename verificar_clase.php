<?php
require 'db.php'; // Asegúrate de tener un archivo para conectar a la BD

if (isset($_GET['codigo'])) {
    $codigo = $_GET['codigo'];
    $sql = "SELECT c.id, c.nombre, u.nombre AS maestro FROM clases c 
            JOIN usuarios u ON c.id_maestro = u.id WHERE c.codigo_clase = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $clase = $result->fetch_assoc();

    if ($clase) {
        echo json_encode(["success" => true, "nombre" => $clase['nombre'], "maestro" => $clase['maestro']]);
    } else {
        echo json_encode(["success" => false, "message" => "Código de clase no válido."]);
    }
}
?>
