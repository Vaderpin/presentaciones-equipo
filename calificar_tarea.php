<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'maestro') {
    $_SESSION['error'] = "Acceso no autorizado.";
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id_tarea']) || !isset($_GET['id_alumno'])) {
    $_SESSION['error'] = "Faltan parámetros.";
    header("Location: maestro_dashboard.php");
    exit();
}

$id_tarea = intval($_GET['id_tarea']);
$id_alumno = intval($_GET['id_alumno']);

// Obtener tarea
$stmt = $conn->prepare("SELECT t.titulo, t.descripcion, t.fecha_entrega, c.nombre AS nombre_clase, c.id AS id_clase 
                        FROM tareas t 
                        JOIN clases c ON t.id_clase = c.id 
                        WHERE t.id = ?");
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$tarea = $stmt->get_result()->fetch_assoc();

// Obtener alumno
$stmt = $conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_alumno);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();

// Obtener entrega
$stmt = $conn->prepare("SELECT archivo_entrega, calificacion, comentarios FROM entregas 
                        WHERE id_tarea = ? AND id_alumno = ?");
$stmt->bind_param("ii", $id_tarea, $id_alumno);
$stmt->execute();
$entrega = $stmt->get_result()->fetch_assoc();

if (!$tarea || !$alumno || !$entrega) {
    $_SESSION['error'] = "No se encontró información suficiente.";
    header("Location: ver_tarea.php?id=$id_tarea");
    exit();
}

// Formatear fecha de entrega
$fecha_entrega = new DateTime($tarea['fecha_entrega']);
$fecha_formateada = $fecha_entrega->format('d/m/Y h:i A');

// Verificar si la fecha de entrega ya pasó
$hoy = new DateTime();
$es_pasada = $fecha_entrega < $hoy;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Tarea - EducaMente</title>
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
            margin-left: 0;
            position: relative;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            color: var(--dark-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 5;
        }
        
        .header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .header h2 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        /* Contenido principal */
        .main-content {
            padding: 30px;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        /* Navegación */
        .navigation-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn-nav {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            background: white;
            color: var(--dark-color);
            border: 1px solid #eee;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .btn-nav:hover {
            background: var(--light-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-nav i {
            margin-right: 8px;
        }
        
        /* Tarjetas de contenido */
        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
            animation: fadeIn 0.5s ease;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header i {
            font-size: 24px;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .card-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            width: 120px;
            margin-right: 10px;
        }
        
        .info-value {
            color: #555;
            flex-grow: 1;
            display: flex;
            align-items: center;
        }
        
        .info-value i {
            margin-right: 8px;
            color: var(--primary-color);
            font-size: 14px;
        }
        
        /* Formulario de calificación */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
        }
        
        .form-group input:focus, 
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
        }
        
        .form-guide {
            background: rgba(52, 152, 219, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .form-guide p {
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-guide ul {
            margin-left: 20px;
            margin-bottom: 0;
        }
        
        .form-guide li {
            color: #555;
            margin-bottom: 5px;
        }
        
        .calificacion-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .calificacion-slider {
            flex-grow: 1;
        }
        
        .calificacion-valor {
            width: 70px;
            text-align: center;
            font-weight: 700;
            font-size: 24px;
            color: var(--dark-color);
        }
        
        .range-container {
            position: relative;
            width: 100%;
        }
        
        .range-markers {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            padding: 0 10px;
        }
        
        .range-marker {
            font-size: 12px;
            color: #888;
            position: relative;
        }
        
        .range-marker:before {
            content: '';
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 1px;
            height: 5px;
            background: #ddd;
        }
        
        /* Botones de submit y cancelar */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-submit {
            padding: 12px 25px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-submit:hover {
            background: var(--success-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-submit i {
            margin-right: 8px;
        }
        
        .btn-cancel {
            padding: 12px 25px;
            background: #eee;
            color: #666;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-cancel:hover {
            background: #ddd;
            transform: translateY(-2px);
        }
        
        .btn-cancel i {
            margin-right: 8px;
        }
        
        /* Link de descarga */
        .file-download {
            display: flex;
            align-items: center;
            padding: 15px;
            background: rgba(52, 152, 219, 0.05);
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .file-icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 15px;
        }
        
        .file-info {
            flex-grow: 1;
        }
        
        .file-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .file-meta {
            font-size: 13px;
            color: #888;
        }
        
        .btn-download {
            display: flex;
            align-items: center;
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }
        
        .btn-download:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-download i {
            margin-right: 8px;
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
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .calificacion-container {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>EducaMente</h1>
            <span>Panel del Profesor</span>
        </div>
        <button onclick="window.location.href='maestro_dashboard.php'"><i class="fas fa-home"></i> Inicio</button>
        <button onclick="window.location.href='ver_tarea.php?id=<?php echo $id_tarea; ?>'"><i class="fas fa-arrow-left"></i> Volver a la Tarea</button>
        <div class="spacer"></div>
        <button onclick="window.location.href='index.php'"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
    </div>

    <div class="content">
        <div class="header">
            <h2><i class="fas fa-star"></i> Calificar Tarea</h2>
        </div>

        <div class="main-content fade-in">
            <div class="navigation-buttons">
                <a href="ver_tarea.php?id=<?php echo $id_tarea; ?>" class="btn-nav">
                    <i class="fas fa-arrow-left"></i> Volver a la tarea
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-alt"></i>
                    <h3>Información de la Tarea</h3>
                </div>
                <div class="info-row">
                    <div class="info-label">Título:</div>
                    <div class="info-value"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Clase:</div>
                    <div class="info-value"><i class="fas fa-book"></i> <?php echo htmlspecialchars($tarea['nombre_clase']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha límite:</div>
                    <div class="info-value" style="<?php echo $es_pasada ? 'color: var(--error-color);' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> <?php echo $fecha_formateada; ?>
                        <?php if ($es_pasada): ?>
                            <span style="margin-left: 5px; font-size: 12px; color: var(--error-color);">(Vencida)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Descripción:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($tarea['descripcion'])); ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Información del Alumno</h3>
                </div>
                <div class="info-row">
                    <div class="info-label">Nombre:</div>
                    <div class="info-value"><?php echo htmlspecialchars($alumno['nombre']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Correo:</div>
                    <div class="info-value"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($alumno['correo']); ?></div>
                </div>
                <?php if (!empty($entrega['archivo_entrega'])): ?>
                <div class="file-download">
                    <div class="file-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="file-info">
                        <div class="file-name">Archivo entregado</div>
                        <div class="file-meta">Haz clic en "Descargar" para revisar la tarea entregada</div>
                    </div>
                    <a href="descargar.php?archivo=<?php echo urlencode($entrega['archivo_entrega']); ?>" class="btn-download" target="_blank">
                        <i class="fas fa-download"></i> Descargar
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>Formulario de Calificación</h3>
                </div>
                
                <div class="form-guide">
                    <p><strong>Guía para calificar:</strong></p>
                    <ul>
                        <li>Asigna una calificación del 0 al 100.</li>
                        <li>Proporciona comentarios constructivos para que el alumno comprenda la evaluación.</li>
                        <li>Enfócate en áreas de mejora y aspectos destacados del trabajo.</li>
                    </ul>
                </div>
                
                <form action="guardar_calificacion.php" method="POST" id="formCalificacion">
                    <input type="hidden" name="id_tarea" value="<?php echo $id_tarea; ?>">
                    <input type="hidden" name="id_alumno" value="<?php echo $id_alumno; ?>">

                    <div class="form-group">
                        <label for="calificacion">Calificación (0.0 a 100.0):</label>
                        <div class="calificacion-container">
                            <div class="calificacion-slider">
                                <input type="range" id="calificacion_slider" min="0" max="100" step="0.1" value="<?php echo htmlspecialchars($entrega['calificacion'] ?? 70); ?>" oninput="updateCalificacion()">
                                <div class="range-markers">
                                    <span class="range-marker">0</span>
                                    <span class="range-marker">25</span>
                                    <span class="range-marker">50</span>
                                    <span class="range-marker">75</span>
                                    <span class="range-marker">100</span>
                                </div>
                            </div>
                            <div class="calificacion-valor" id="calificacion_valor">
                                <?php echo htmlspecialchars($entrega['calificacion'] ?? 70); ?>
                            </div>
                        </div>
                        <input type="hidden" name="calificacion" id="calificacion" value="<?php echo htmlspecialchars($entrega['calificacion'] ?? 70); ?>">
                    </div>

                    <div class="form-group">
                        <label for="comentarios">Comentarios para el alumno:</label>
                        <textarea name="comentarios" id="comentarios" placeholder="Escribe aquí tus comentarios y retroalimentación para el alumno..."><?php echo htmlspecialchars($entrega['comentarios'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Guardar calificación
                        </button>
                        <a href="ver_tarea.php?id=<?php echo $id_tarea; ?>" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Función para actualizar el valor de la calificación
        function updateCalificacion() {
            const slider = document.getElementById('calificacion_slider');
            const valor = document.getElementById('calificacion_valor');
            const input = document.getElementById('calificacion');
            
            const calificacion = parseFloat(slider.value).toFixed(1);
            valor.textContent = calificacion;
            input.value = calificacion;
            
            // Cambiar color según el valor
            if (calificacion < 60) {
                valor.style.color = '#e74c3c'; // Rojo para reprobado
            } else if (calificacion < 80) {
                valor.style.color = '#f39c12'; // Naranja para regular
            } else {
                valor.style.color = '#27ae60'; // Verde para bueno
            }
        }
        
        // Inicializar la visualización de la calificación
        document.addEventListener('DOMContentLoaded', function() {
            updateCalificacion();
        });
        
        // Validación del formulario
        document.getElementById('formCalificacion').addEventListener('submit', function(e) {
            const calificacion = parseFloat(document.getElementById('calificacion').value);
            const comentarios = document.getElementById('comentarios').value.trim();
            
            if (isNaN(calificacion) || calificacion < 0 || calificacion > 100) {
                e.preventDefault();
                alert('Por favor, ingresa una calificación válida entre 0 y 100.');
                return;
            }
            
            if (comentarios.length < 10) {
                if (!confirm('Has proporcionado comentarios muy breves o vacíos. La retroalimentación es importante para el aprendizaje del alumno. ¿Deseas continuar de todos modos?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>