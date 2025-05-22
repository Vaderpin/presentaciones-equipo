<?php
session_start();
require 'db.php'; // Conexión a la base de datos

// Verificamos si se recibe un ID de clase
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Clase no especificada.";
    header("Location: maestro_dashboard.php");
    exit();
}

$id_clase = $_GET['id'];

// Obtener información de la clase
$stmt = $conn->prepare("SELECT nombre, codigo_clase FROM clases WHERE id = ?");
$stmt->bind_param("i", $id_clase);
$stmt->execute();
$result = $stmt->get_result();
$clase = $result->fetch_assoc();

// Si la clase no existe, redirigir
if (!$clase) {
    $_SESSION['error'] = "La clase no existe.";
    header("Location: maestro_dashboard.php");
    exit();
}

// Obtener alumnos inscritos en la clase
$stmt = $conn->prepare("SELECT u.id, u.nombre, u.correo 
                        FROM usuarios u 
                        JOIN alumnos_clases ac ON u.id = ac.id_alumno 
                        WHERE ac.id_clase = ?");
$stmt->bind_param("i", $id_clase);
$stmt->execute();
$alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener tareas de la clase
$stmt = $conn->prepare("SELECT id, titulo, descripcion, fecha_entrega 
                        FROM tareas WHERE id_clase = ?");
$stmt->bind_param("i", $id_clase);
$stmt->execute();
$tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Procesar formulario de agregar tarea
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['titulo'], $_POST['descripcion'], $_POST['fecha_entrega'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_entrega = $_POST['fecha_entrega'];

    if (!empty($titulo) && !empty($descripcion) && !empty($fecha_entrega)) {
        $stmt = $conn->prepare("INSERT INTO tareas (titulo, descripcion, fecha_entrega, id_clase) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $titulo, $descripcion, $fecha_entrega, $id_clase);
        
        if ($stmt->execute()) {
            // ✅ Enviar notificaciones a alumnos
            foreach ($alumnos as $alumno) {
                $correo = $alumno['correo'];
                $postData = [
                    'accion' => 'Nueva Tarea',
                    'nombreTarea' => $titulo,
                    'descripcion' => $descripcion,
                    'correo' => $correo
                ];

                $ch = curl_init("http://localhost/proyecto/enviarCorreo.php"); // Reemplaza con la URL real
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_exec($ch);
                curl_close($ch);
            }

            $_SESSION['success'] = "Tarea agregada con éxito.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id_clase);
            exit();
        } else {
            $_SESSION['error'] = "Error al agregar la tarea.";
        }
    } else {
        $_SESSION['error'] = "Por favor, complete todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($clase['nombre']); ?> - EducaMente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --error-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #e67e22;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        }
        
        /* Sidebar mejorada */
        .sidebar {
            width: 280px;
            background: var(--dark-color);
            padding: 30px 20px;
            color: white;
            transition: var(--transition);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            z-index: 10;
        }
        
        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .sidebar h2:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 10px;
        }
        
        .sidebar button {
            width: 100%;
            padding: 12px 20px;
            margin: 8px 0;
            background: rgba(255, 255, 255, 0.08);
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            text-align: left;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .sidebar button i {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .sidebar button:hover {
            background: var(--primary-color);
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }
        
        .sidebar button.active {
            background: var(--primary-color);
            color: white;
        }
        
        .sidebar .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .sidebar .logo h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .sidebar .logo span {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .spacer {
            flex-grow: 1;
        }
        
        /* Content area */
        .content {
            flex-grow: 1;
            padding: 0;
            display: flex;
            flex-direction: column;
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            color: var(--dark-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 5;
        }
        
        .header h3 {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .header .clase-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header .clase-codigo {
            background: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .header .clase-codigo i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        /* Contenido principal */
        .main-content {
            padding: 30px;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        /* Secciones */
        .section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease;
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .section h3 {
            color: var(--dark-color);
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .section h3 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        /* Lista de alumnos */
        .alumnos-lista {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .alumno-item {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .alumno-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        
        .alumno-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
            font-size: 16px;
        }
        
        .alumno-info {
            flex-grow: 1;
        }
        
        .alumno-nombre {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .alumno-correo {
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
        }
        
        .alumno-correo i {
            margin-right: 5px;
            color: var(--primary-color);
            font-size: 12px;
        }
        
        /* Tareas */
        .tareas-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .tarea-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid #eee;
        }
        
        .tarea-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .tarea-card:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .tarea-titulo {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .tarea-descripcion {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .tarea-fecha {
            font-size: 13px;
            color: #888;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .tarea-fecha i {
            margin-right: 8px;
            color: var(--warning-color);
        }
        
        .btn-tarea {
            display: flex;
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            margin-top: 15px;
            transition: var(--transition);
            justify-content: center;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-tarea i {
            margin-right: 8px;
        }
        
        .btn-tarea:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Botón de agregar tarea */
        .btn-agregar-tarea {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            background: var(--secondary-color);
            color: white;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .btn-agregar-tarea i {
            margin-right: 8px;
        }
        
        .btn-agregar-tarea:hover {
            background: var(--success-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        /* Formulario de nueva tarea */
        .form-tarea {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            border: 1px solid #eee;
            animation: slideDown 0.3s ease forwards;
            display: none;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-tarea h4 {
            color: var(--dark-color);
            margin-bottom: 15px;
            font-size: 18px;
            position: relative;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .form-tarea h4 i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        
        .form-tarea h4:after {
            content: '';
            position: absolute;
            width: 40px;
            height: 3px;
            background: var(--secondary-color);
            bottom: 0;
            left: 0;
            border-radius: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-tarea label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-tarea input, .form-tarea textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
        }
        
        .form-tarea input:focus, .form-tarea textarea:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(46, 204, 113, 0.2);
            outline: none;
        }
        
        .form-tarea textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-tarea button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }
        
        .form-tarea button[type="submit"] {
            background: var(--secondary-color);
            color: white;
        }
        
        .form-tarea button[type="submit"]:hover {
            background: var(--success-color);
        }
        
        .form-tarea button[type="button"] {
            background: #eee;
            color: #666;
        }
        
        .form-tarea button[type="button"]:hover {
            background: #ddd;
        }
        
        .form-tarea button i {
            margin-right: 8px;
        }
        
        /* Toast para notificaciones */
        #toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            background: var(--success-color);
            color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            min-width: 280px;
        }
        
        #toast.show {
            display: block;
            animation: fadeInRight 0.5s ease forwards;
        }
        
        #toast.error {
            background: var(--error-color);
        }
        
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Mensaje de no hay datos */
        .empty-state {
            text-align: center;
            padding: 30px;
            background: rgba(52, 152, 219, 0.05);
            border-radius: var(--radius);
            margin-top: 20px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 10px;
        }
        
        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px;
            }
            
            .content {
                width: 100%;
            }
            
            .tareas-container {
                grid-template-columns: 1fr;
            }
            
            .alumnos-lista {
                grid-template-columns: 1fr;
            }
        }
        
        /* Clases de utilidad */
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>EducaMente</h1>
            <span>Panel del Profesor</span>
        </div>
        <button onclick="mostrarSeccion('alumnosInscritos')" id="btnAlumnos"><i class="fas fa-user-graduate"></i> Alumnos Inscritos</button>
        <button onclick="mostrarSeccion('tareasAsignadas')" id="btnTareas"><i class="fas fa-tasks"></i> Tareas Asignadas</button>
        <div class="spacer"></div>
        <button onclick="window.location.href='maestro_dashboard.php'" id="btnVolver"><i class="fas fa-arrow-left"></i> Volver al Dashboard</button>
    </div>
    
    <div class="content">
        <div class="header">
            <div class="clase-info">
                <h3><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($clase['nombre']); ?></h3>
                <div class="clase-codigo">
                    <i class="fas fa-key"></i> Código: <?php echo htmlspecialchars($clase['codigo_clase']); ?>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <!-- Sección de Alumnos -->
            <div id="alumnosInscritos" class="section">
                <h3><i class="fas fa-user-graduate"></i> Alumnos Inscritos</h3>
                
                <?php if (count($alumnos) > 0): ?>
                    <p class="mb-20">A continuación se muestra la lista de alumnos inscritos en esta clase.</p>
                    <div class="alumnos-lista">
                        <?php foreach ($alumnos as $alumno): ?>
                            <div class="alumno-item">
                                <div class="alumno-avatar">
                                    <?php echo substr(htmlspecialchars($alumno['nombre']), 0, 1); ?>
                                </div>
                                <div class="alumno-info">
                                    <div class="alumno-nombre"><?php echo htmlspecialchars($alumno['nombre']); ?></div>
                                    <div class="alumno-correo">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($alumno['correo']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>No hay alumnos inscritos en esta clase.</p>
                        <p>Comparte el código de clase <strong><?php echo htmlspecialchars($clase['codigo_clase']); ?></strong> con tus alumnos para que puedan inscribirse.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sección de Tareas -->
            <div id="tareasAsignadas" class="section">
                <h3><i class="fas fa-tasks"></i> Tareas Asignadas</h3>
                
                <button class="btn-agregar-tarea" onclick="mostrarFormularioTarea()">
                    <i class="fas fa-plus-circle"></i> Agregar Nueva Tarea
                </button>
                
                <!-- Formulario para agregar tarea -->
                <div id="formAgregarTarea" class="form-tarea">
                    <h4><i class="fas fa-plus-circle"></i> Agregar Nueva Tarea</h4>
                    <form method="POST" id="nuevaTareaForm">
                        <div class="form-group">
                            <label for="titulo"><i class="fas fa-heading"></i> Título de la tarea:</label>
                            <input type="text" name="titulo" id="titulo" placeholder="Ej: Proyecto final de matemáticas" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion"><i class="fas fa-align-left"></i> Descripción:</label>
                            <textarea name="descripcion" id="descripcion" placeholder="Escribe aquí las instrucciones detalladas de la tarea..." required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_entrega"><i class="fas fa-calendar-alt"></i> Fecha de entrega:</label>
                            <input type="datetime-local" name="fecha_entrega" id="fecha_entrega" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit">
                                <i class="fas fa-save"></i> Guardar Tarea
                            </button>
                            <button type="button" onclick="ocultarFormularioTarea()">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>

                <?php if (count($tareas) > 0): ?>
                    <div class="tareas-container">
                        <?php foreach ($tareas as $tarea): ?>
                            <?php 
                                // Formatear la fecha
                                $fecha_entrega = new DateTime($tarea['fecha_entrega']);
                                $fecha_formateada = $fecha_entrega->format('d/m/Y h:i A');
                                
                                // Verificar si la fecha de entrega ya pasó
                                $hoy = new DateTime();
                                $es_pasada = $fecha_entrega < $hoy;
                            ?>
                            <div class="tarea-card">
                                <div class="tarea-titulo"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
                                <div class="tarea-descripcion"><?php echo htmlspecialchars($tarea['descripcion']); ?></div>
                                <div class="tarea-fecha" style="<?php echo $es_pasada ? 'color: var(--error-color);' : ''; ?>">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php if ($es_pasada): ?>
                                        Fecha vencida: <?php echo $fecha_formateada; ?>
                                    <?php else: ?>
                                        Entrega: <?php echo $fecha_formateada; ?>
                                    <?php endif; ?>
                                </div>
                                <a href="ver_tarea.php?id=<?php echo $tarea['id']; ?>" class="btn-tarea">
                                    <i class="fas fa-eye"></i> Ver Entregas
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No hay tareas asignadas en esta clase.</p>
                        <p>Utiliza el botón "Agregar Nueva Tarea" para crear la primera tarea.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div id="toast"></div>

    <script>
        // Referencias a elementos del DOM
        const btnAlumnos = document.getElementById('btnAlumnos');
        const btnTareas = document.getElementById('btnTareas');
        const btnVolver = document.getElementById('btnVolver');
        const formAgregarTarea = document.getElementById('formAgregarTarea');
        
        // Función para mostrar una sección
        function mostrarSeccion(id) {
            // Ocultar todas las secciones
            document.querySelectorAll('.section').forEach(seccion => {
                seccion.classList.remove('active');
            });
            
            // Mostrar la sección seleccionada
            document.getElementById(id).classList.add('active');
            
            // Actualizar botones activos
            btnAlumnos.classList.remove('active');
            btnTareas.classList.remove('active');
            
            if (id === 'alumnosInscritos') {
                btnAlumnos.classList.add('active');
            } else if (id === 'tareasAsignadas') {
                btnTareas.classList.add('active');
            }
        }
        
        // Función para mostrar el formulario de tarea
        function mostrarFormularioTarea() {
            formAgregarTarea.style.display = 'block';
            document.getElementById('titulo').focus();
            
            // Establecer fecha de entrega predeterminada (una semana después)
            const fecha = new Date();
            fecha.setDate(fecha.getDate() + 7);
            
            // Formatear fecha para el input datetime-local
            const fechaFormateada = fecha.toISOString().slice(0, 16);
            document.getElementById('fecha_entrega').value = fechaFormateada;
        }
        
        // Función para ocultar el formulario de tarea
        function ocultarFormularioTarea() {
            formAgregarTarea.style.display = 'none';
        }
        
        // Función para mostrar notificaciones toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
            toast.className = 'show ' + type;
            
            setTimeout(function() {
                toast.className = toast.className.replace('show', '');
            }, 5000);
        }
        
        // Validación del formulario de nueva tarea
        document.getElementById('nuevaTareaForm').addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const fechaEntrega = document.getElementById('fecha_entrega').value;
            
            if (titulo === '' || descripcion === '' || fechaEntrega === '') {
                e.preventDefault();
                showToast('Por favor, complete todos los campos.', 'error');
                return;
            }
            
            // Verificar que la fecha de entrega no sea anterior a la fecha actual
            const fechaSeleccionada = new Date(fechaEntrega);
            const ahora = new Date();
            
            if (fechaSeleccionada < ahora) {
                e.preventDefault();
                showToast('La fecha de entrega no puede ser anterior a la fecha actual.', 'error');
                return;
            }
        });
        
        // Cargar la sección de tareas por defecto al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            mostrarSeccion('tareasAsignadas');
            
            // Mostrar mensajes de éxito o error si existen
            <?php if (isset($_SESSION['success'])): ?>
                showToast("<?php echo htmlspecialchars($_SESSION['success']); ?>", "success");
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                showToast("<?php echo htmlspecialchars($_SESSION['error']); ?>", "error");
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>