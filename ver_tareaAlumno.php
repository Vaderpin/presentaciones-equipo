<?php
session_start();
require 'db.php'; // Asegúrate de que este archivo conecta correctamente a la base de datos

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'alumno') {
    $_SESSION['error'] = "Debes iniciar sesión como alumno para ver esta página.";
    header("Location: index.php");
    exit();
}

$id_alumno = $_SESSION['id'];

// Verificamos si se recibe un ID de tarea
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Tarea no especificada.";
    header("Location: alumno_dashboard.php");
    exit();
}

$id_tarea = intval($_GET['id']); // Convertimos el ID a número entero

// Obtener información de la tarea
$stmt = $conn->prepare("SELECT t.titulo, t.descripcion, t.fecha_entrega, c.nombre AS nombre_clase, c.id AS id_clase 
                        FROM tareas t 
                        JOIN clases c ON t.id_clase = c.id 
                        WHERE t.id = ?");
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$result = $stmt->get_result();
$tarea = $result->fetch_assoc();

if (!$tarea) {
    $_SESSION['error'] = "La tarea no existe.";
    header("Location: alumno_dashboard.php");
    exit();
}

// Verificar si el alumno ya entregó la tarea
$stmt_entrega = $conn->prepare("SELECT archivo_entrega, fecha_entrega, calificacion, comentarios 
                                FROM entregas 
                                WHERE id_tarea = ? AND id_alumno = ?");
$stmt_entrega->bind_param("ii", $id_tarea, $id_alumno);
$stmt_entrega->execute();
$res_entrega = $stmt_entrega->get_result();
$entrega = $res_entrega->fetch_assoc();

// Verificar si la fecha de entrega ya pasó
$fecha_entrega = new DateTime($tarea['fecha_entrega']);
$hoy = new DateTime();
$es_tardio = $fecha_entrega < $hoy;

// Formatear fecha de entrega de la tarea para mostrarla
$fecha_formateada = $fecha_entrega->format('d/m/Y h:i A');

// Formatear fecha de entrega del alumno (si existe)
$fecha_entrega_alumno = null;
if ($entrega && $entrega['fecha_entrega']) {
    $fecha_entrega_alumno = new DateTime($entrega['fecha_entrega']);
    $fecha_alumno_formateada = $fecha_entrega_alumno->format('d/m/Y h:i A');
    
    // Verificar si la entrega fue tardía
    $entrega_tardia = $fecha_entrega_alumno > $fecha_entrega;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tarea['titulo']); ?> - EducaMente</title>
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
            padding: 25px 30px;
            color: var(--dark-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 5;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .header .clase-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: #666;
        }
        
        .header .clase-info i {
            color: var(--primary-color);
        }
        
        .task-status {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .task-status i {
            margin-right: 8px;
        }
        
        .status-completed {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
        }
        
        .status-pending {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--primary-color);
        }
        
        .status-late {
            background-color: rgba(230, 126, 34, 0.15);
            color: var(--warning-color);
        }
        
        .status-overdue {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--error-color);
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
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            width: 150px;
            margin-right: 10px;
            display: flex;
            align-items: center;
        }
        
        .info-label i {
            margin-right: 8px;
            color: var(--primary-color);
            font-size: 16px;
        }
        
        .info-value {
            color: #555;
            flex-grow: 1;
            line-height: 1.5;
        }
        
        .deadline-overdue {
            color: var(--error-color);
            font-weight: 500;
        }
        
        .deadline-overdue i {
            color: var(--error-color);
        }
        
        /* Descripción de tarea */
        .task-description {
            background: rgba(52, 152, 219, 0.05);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        /* Estado de entrega */
        .submission-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .submission-status h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .submission-status h3 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        /* Archivo de entrega */
        .file-card {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
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
            color: #666;
        }
        
        .file-download {
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .file-download:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .file-download i {
            margin-right: 8px;
        }
        
        /* Calificación */
        .grade {
            font-size: 24px;
            font-weight: 700;
            color: var(--success-color);
            margin: 10px 0;
        }
        
        .grade-pending {
            font-size: 16px;
            color: #888;
            font-style: italic;
        }
        
        /* Comentarios */
        .comments {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .comments-empty {
            font-style: italic;
            color: #888;
        }
        
        /* Formulario de entrega */
        .submission-form {
            background: rgba(52, 152, 219, 0.05);
            border: 2px dashed var(--primary-color);
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            text-align: center;
            transition: var(--transition);
        }
        
        .submission-form:hover {
            box-shadow: var(--shadow);
            border-color: var(--primary-hover);
        }
        
        .form-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-title i {
            margin-right: 10px;
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .file-upload {
            margin-bottom: 25px;
        }
        
        .file-upload-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .file-upload-input {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .file-upload-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .file-optional {
            margin-top: 5px;
            font-size: 13px;
            color: #888;
        }
        
        .btn-submit {
            display: inline-flex;
            align-items: center;
            padding: 12px 25px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-submit:hover {
            background: var(--success-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-submit i {
            margin-right: 8px;
        }
        
        .late-submission-warning {
            margin-top: 15px;
            padding: 10px 15px;
            background: rgba(230, 126, 34, 0.1);
            border-left: 4px solid var(--warning-color);
            border-radius: 8px;
            text-align: left;
            color: var(--warning-color);
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .late-submission-warning i {
            margin-right: 10px;
            font-size: 18px;
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
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .submission-status {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .file-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .file-download {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h1>EducaMente</h1>
            <span>Panel del Alumno</span>
        </div>
        <button onclick="window.location.href='alumno_dashboard.php'"><i class="fas fa-home"></i> Inicio</button>
        <button onclick="window.location.href='ver_claseAlumno.php?id=<?php echo $tarea['id_clase']; ?>'"><i class="fas fa-arrow-left"></i> Volver a la Clase</button>
        <div class="spacer"></div>
        <button onclick="window.location.href='index.php'"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
    </div>

    <div class="content">
        <div class="header">
            <div class="header-content">
                <div>
                    <h2><?php echo htmlspecialchars($tarea['titulo']); ?></h2>
                    <div class="clase-info">
                        <i class="fas fa-book"></i> Clase: <?php echo htmlspecialchars($tarea['nombre_clase']); ?>
                    </div>
                </div>
                <div class="task-status 
                    <?php echo $entrega ? 'status-completed' : ($es_tardio ? 'status-overdue' : 'status-pending'); ?>">
                    <?php if ($entrega): ?>
                        <i class="fas fa-check-circle"></i> Entregada
                    <?php elseif ($es_tardio): ?>
                        <i class="fas fa-exclamation-circle"></i> Plazo vencido
                    <?php else: ?>
                        <i class="fas fa-clock"></i> Pendiente
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="main-content fade-in">
            <div class="navigation-buttons">
                <a href="ver_claseAlumno.php?id=<?php echo $tarea['id_clase']; ?>" class="btn-nav">
                    <i class="fas fa-arrow-left"></i> Volver a la clase
                </a>
                <a href="alumno_dashboard.php" class="btn-nav">
                    <i class="fas fa-home"></i> Ir al dashboard
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-alt"></i>
                    <h3>Detalles de la Tarea</h3>
                </div>
                
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-calendar-alt"></i> Fecha de entrega:</div>
                    <div class="info-value <?php echo $es_tardio ? 'deadline-overdue' : ''; ?>">
                        <?php if ($es_tardio): ?>
                            <i class="fas fa-exclamation-triangle"></i> 
                            ¡Plazo vencido! Fecha límite: <?php echo $fecha_formateada; ?>
                        <?php else: ?>
                            <?php echo $fecha_formateada; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="task-description">
                    <?php echo nl2br(htmlspecialchars($tarea['descripcion'])); ?>
                </div>
            </div>

            <?php if ($entrega): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-check-circle"></i>
                    <h3>Tu Entrega</h3>
                </div>
                
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-calendar-check"></i> Fecha de entrega:</div>
                    <div class="info-value">
                        <?php if (isset($entrega_tardia) && $entrega_tardia): ?>
                            <span style="color: var(--warning-color);"><i class="fas fa-exclamation-triangle"></i> Entrega tardía: </span>
                        <?php endif; ?>
                        <?php echo $fecha_alumno_formateada; ?>
                    </div>
                </div>
                
                <?php if (!empty($entrega['archivo_entrega'])): ?>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-file"></i> Archivo enviado:</div>
                    <div class="info-value">
                        <div class="file-card">
                            <div class="file-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="file-info">
                                <div class="file-name"><?php echo basename($entrega['archivo_entrega']); ?></div>
                                <div class="file-meta">Haz clic en "Descargar" para ver tu archivo entregado</div>
                            </div>
                            <a href="descargar.php?archivo=<?php echo urlencode($entrega['archivo_entrega']); ?>" class="file-download" target="_blank">
                                <i class="fas fa-download"></i> Descargar
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-file"></i> Archivo enviado:</div>
                    <div class="info-value">
                        <span class="comments-empty">No se adjuntó ningún archivo</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-star"></i> Calificación:</div>
                    <div class="info-value">
                        <?php if (is_null($entrega['calificacion'])): ?>
                            <span class="grade-pending">No calificada aún</span>
                        <?php else: ?>
                            <div class="grade"><?php echo htmlspecialchars($entrega['calificacion']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-comment"></i> Comentarios del profesor:</div>
                    <div class="info-value">
                        <div class="comments">
                            <?php echo empty($entrega['comentarios']) ? 
                                '<span class="comments-empty">Sin comentarios</span>' : 
                                nl2br(htmlspecialchars($entrega['comentarios'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-upload"></i>
                    <h3>Entregar Tarea</h3>
                </div>
                
                <div class="submission-form">
                    <h4 class="form-title">
                        <?php if ($es_tardio): ?>
                            <i class="fas fa-exclamation-circle"></i> Entregar Tarea (Fuera de plazo)
                        <?php else: ?>
                            <i class="fas fa-paper-plane"></i> Entregar Tarea
                        <?php endif; ?>
                    </h4>
                    
                    <form action="marcar_completada.php" method="POST" enctype="multipart/form-data" id="formEntrega">
                        <input type="hidden" name="id_tarea" value="<?php echo $id_tarea; ?>">
                        
                        <div class="file-upload">
                            <label for="archivo" class="file-upload-label">Archivo de la tarea (opcional):</label>
                            <input type="file" name="archivo" id="archivo" class="file-upload-input">
                            <div class="file-optional">El archivo es opcional. Puedes marcar la tarea como completada sin adjuntar un archivo.</div>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check-circle"></i> Marcar como Completada
                        </button>
                        
                        <?php if ($es_tardio): ?>
                        <div class="late-submission-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Advertencia de entrega tardía:</strong><br>
                                El plazo para esta tarea venció el <?php echo $fecha_formateada; ?>. Tu entrega será marcada como tardía.
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div id="toast"></div>

    <script>
        // Función para mostrar mensajes toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
            toast.className = 'show ' + type;
            
            setTimeout(function() {
                toast.className = toast.className.replace('show', '');
            }, 5000);
        }
        
        // Validación básica del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const formEntrega = document.getElementById('formEntrega');
            
            if (formEntrega) {
                formEntrega.addEventListener('submit', function(e) {
                    // Aquí podemos añadir validación si es necesario
                    // Por ejemplo, validar el tamaño del archivo, formato, etc.
                    
                    // Opcional: Confirmación de entrega tardía
                    <?php if ($es_tardio): ?>
                    if (!confirm('Estás realizando una entrega fuera de plazo. ¿Estás seguro de que deseas continuar?')) {
                        e.preventDefault();
                        return false;
                    }
                    <?php endif; ?>
                    
                    showToast('Enviando tarea...', 'success');
                });
            }
            
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