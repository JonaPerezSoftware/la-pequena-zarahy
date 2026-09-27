<?php
// ============================================================
// apply.php — Maneja postulaciones: guarda el CV y envía email
// CONFIGURAR antes de subir a Hostinger:
//   $admin_email → correo donde llegarán las postulaciones
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ── CONFIGURACIÓN ──────────────────────────────────────────
$admin_emails = [
    'zarahygardenia@gmail.com',
    // 'otro_correo@ejemplo.com' // Añade más correos aquí separados por coma
]; // ← Correos reales para postulaciones
$max_size_mb = 5;
$max_size = $max_size_mb * 1024 * 1024;
$uploads_dir = __DIR__ . '/uploads/cvs/';
// ───────────────────────────────────────────────────────────

function respond($success, $message)
{
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Método no permitido.');
}

// Validar campos
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$vacante = trim($_POST['vacante'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if (empty($nombre) || empty($correo) || empty($vacante)) {
    respond(false, 'Por favor completa todos los campos obligatorios.');
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'El correo electrónico no es válido.');
}

// Validar archivo
if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'Error al subir el archivo. Verifica que sea un PDF menor a 5 MB.');
}

$file = $_FILES['cv'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($file_ext !== 'pdf' || $mime !== 'application/pdf') {
    respond(false, 'Solo se aceptan archivos en formato PDF.');
}
if ($file['size'] > $max_size) {
    respond(false, "El archivo no debe superar {$max_size_mb} MB.");
}

// Crear directorio si no existe
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Nombre único para el archivo
$safe_name = preg_replace('/[^a-z0-9]/i', '_', $nombre);
$filename = date('YmdHis') . '_' . $safe_name . '.pdf';
$filepath = $uploads_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    respond(false, 'No se pudo guardar el archivo. Intenta de nuevo.');
}

// COPIA DE SEGURIDAD: Guardar datos en un archivo de texto por si el correo falla
$log_dir = __DIR__ . '/admin/logs_postulaciones/';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log_file = $log_dir . date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-z0-9]/i', '_', $nombre) . '.txt';
$log_content = "NUEVA POSTULACIÓN\n" .
               "-----------------\n" .
               "Fecha: " . date('Y-m-d H:i:s') . "\n" .
               "Vacante: $vacante\n" .
               "Nombre: $nombre\n" .
               "Correo: $correo\n" .
               "Teléfono: $telefono\n" .
               "Archivo CV: $filename\n" .
               "Mensaje:\n$mensaje";
file_put_contents($log_file, $log_content);

// IMPORTAR MOTOR SMTP DIRECTO
require_once 'class.smtp.php';

require_once __DIR__ . '/config.php';
// Configuración SMTP
$smtp_user = 'info@xn--lapequeazarahy-wnb.es';
$smtp_pass = defined('SMTP_PASS') ? SMTP_PASS : '';

// Enlace al CV (Dominio oficial forzado)
$cv_url = "https://xn--lapequeazarahy-wnb.es/uploads/cvs/" . $filename;

$subject = "Nueva Postulacion: $vacante";
$body = "<h2>Nueva Postulación Recibida</h2>
         <p><strong>Vacante:</strong> $vacante</p>
         <p><strong>Nombre:</strong> $nombre</p>
         <p><strong>Correo:</strong> $correo</p>
         <p><strong>Teléfono:</strong> $telefono</p>
         <p><strong>CV Adjunto:</strong> <a href='$cv_url'>Descargar / Ver PDF</a></p>
         <p><strong>Mensaje:</strong><br>" . nl2br(htmlspecialchars($mensaje)) . "</p>";

// ENVÍO DIRECTO A MÚLTIPLES DESTINATARIOS
$success = false;
foreach ($admin_emails as $email) {
    if (SimpleSMTP::send($email, $subject, $body, $smtp_user, $smtp_pass, "Postulaciones Zarahy")) {
        $success = true;
    }
}

if ($success) {
    respond(true, '¡Postulación enviada con éxito!');
} else {
    respond(false, 'Error del servidor de correo. Tu CV fue guardado pero la notificación falló.');
}
