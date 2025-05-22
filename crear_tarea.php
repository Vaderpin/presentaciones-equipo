<?php
require 'db.php';  // Asegúrate de incluir la conexión a la base de datos

// Verifica si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtén los datos del formulario
    $id_clase = $_POST['id_clase'];
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];

    // Verifica si todos los campos están llenos
    if (!empty($id_clase) && !empty($titulo) && !empty($descripcion) && !empty($fecha_entrega)) {
        // Inserta los datos en la base de datos
        $sql = "INSERT INTO tareas (id_clase, titulo, descripcion, fecha_entrega) 
                VALUES ('$id_clase', '$titulo', '$descripcion', '$fecha_entrega')";

        if ($conn->query($sql) === TRUE) {
            // Obtener correos electrónicos de alumnos inscritos a la clase
            $stmt = $conn->prepare("
                SELECT u.correo 
                FROM usuarios u
                JOIN alumnos_clases ac ON u.id = ac.id_alumno
                WHERE ac.id_clase = ?
            ");
            $stmt->bind_param("i", $id_clase);
            $stmt->execute();
            $result = $stmt->get_result();
            $correos = [];

            while ($row = $result->fetch_assoc()) {
                $correos[] = $row['correo'];
            }

            // Enviar datos a enviarCorreo.php por POST
            foreach ($correos as $correo) {
                $postData = [
                    'accion' => 'Nueva Tarea',
                    'nombreTarea' => $titulo,
                    'descripcion' => $descripcion,
                    'correo' => $correo
                ];

                $ch = curl_init("enviarCorreo.php"); // Cambia la URL si es necesario
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_exec($ch);
                curl_close($ch);
            }

            // Redirige a la página de visualización de la clase después de agregar la tarea
            header("Location: ver_clase.php?id=" . $id_clase); 
            exit(); // Termina la ejecución del script para evitar múltiples redirecciones
        } else {
            $error = "Error al registrar la tarea: " . $conn->error;
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// Obtener las clases para mostrarlas en el formulario
$sql_clases = "SELECT id, nombre FROM clases WHERE id_maestro = 1";  // Cambiar "1" por el ID del maestro
$result_clases = $conn->query($sql_clases);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Tarea</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f4f6f9; }
        .sidebar { width: 250px; background: #2c3e50; padding: 20px; color: white; }
        .sidebar h2 { margin-bottom: 20px; text-align: center; }
        .sidebar button { width: 100%; padding: 10px; margin: 10px 0; background: #34495e; border: none; color: white; cursor: pointer; }
        .sidebar button:hover { background: #1abc9c; }
        .content { flex-grow: 1; padding: 20px; }
        .header { background: #2980b9; padding: 15px; color: white; text-align: center; font-size: 20px; }
        .form-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .form-container input, .form-container textarea, .form-container select { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc; }
        .form-container button { background: #34495e; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .form-container button:hover { background: #1abc9c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>EducaMente</h2>
        <button onclick="window.location.href='maestro_dashboard.php'">Volver al Dashboard</button>
        <button onclick="window.location.href='ver_clases.php'">Ver Mis Clases</button>
    </div>
    <div class="content">
        <div class="header">
            Crear Tarea
        </div>
        <div class="form-container">
            <h3>Registrar Nueva Tarea</h3>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>
            <form action="crear_tarea.php" method="POST">
                <label for="id_clase">Clase</label>
                <select name="id_clase" required>
                    <option value="">Seleccione una clase</option>
                    <?php while ($clase = $result_clases->fetch_assoc()): ?>
                        <option value="<?php echo $clase['id']; ?>"><?php echo $clase['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="titulo">Título</label>
                <input type="text" name="titulo" required>

                <label for="descripcion">Descripción</label>
                <textarea name="descripcion" rows="4" required></textarea>

                <label for="fecha_entrega">Fecha de Entrega</label>
                <input type="date" name="fecha_entrega" required>

                <button type="submit">Crear Tarea</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
