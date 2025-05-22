<?php
require 'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

// Iniciar cliente
putenv('GOOGLE_APPLICATION_CREDENTIALS=proyecto-para-almacenamiento-ff8cd1f8f073.json');
$storage = new StorageClient();
$bucket = $storage->bucket('mis-archivos-javier');

// Verificar que haya nombre de archivo
if (!isset($_GET['archivo'])) {
    die("Archivo no especificado.");
}

$nombreArchivo = $_GET['archivo'];

// Obtener objeto desde el bucket
$object = $bucket->object($nombreArchivo);

if (!$object->exists()) {
    die("Archivo no encontrado en el bucket.");
}

// Descargar contenido
$stream = $object->downloadAsStream();
$contenido = $stream->getContents();

// Obtener tipo MIME básico
$finfo = new finfo(FILEINFO_MIME_TYPE);
$tipo = $finfo->buffer($contenido);

// Preparar headers y mostrar archivo
header("Content-Type: $tipo");
header("Content-Disposition: inline; filename=\"" . basename($nombreArchivo) . "\"");
echo $contenido;
