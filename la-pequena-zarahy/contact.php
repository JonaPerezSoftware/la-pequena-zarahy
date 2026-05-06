<?php
/**
 * contact.php - Maneja el formulario de contacto principal
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// CONFIGURACIÓN SMTP HOSTINGER
$smtp_user = 'info@xn--lapequeazarahy-wnb.es'; // info@lapequeñazarahy.es
$smtp_pass = 'Zarahy7.g';
$admin_emails = [
    'zarahygardenia@gmail.com',
    // 'otro_correo@ejemplo.com' // Descomenta y edita esta línea para añadir más correos
];

// Capturar y limpiar datos
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$asunto = trim($_POST['asunto'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// Validación básica
if (empty($nombre) || empty($correo) || empty($asunto) || empty($mensaje)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos obligatorios.']);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido.']);
    exit;
}

// COPIA DE SEGURIDAD: Guardar el mensaje en un archivo por si el correo falla
$log_dir = __DIR__ . '/admin/logs_contacto/';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log_file = $log_dir . date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-z0-9]/i', '_', $nombre) . '.txt';
$log_file = $log_dir . date('Y-m-d_H-i-s') . '.txt';
$log_content = "Nombre: $nombre\nCorreo: $correo\nAsunto: $asunto\nMensaje: $mensaje";
file_put_contents($log_file, $log_content);

// Cuerpo del correo HTML
$to = 'japrezch10@gmail.com';
$subject = "=?UTF-8?B?" . base64_encode("Nuevo Mensaje: $asunto") . "?=";
$body = "<h2>Nuevo contacto desde la web</h2>
         <p><strong>Nombre:</strong> $nombre</p>
         <p><strong>Correo:</strong> $correo</p>
         <p><strong>Asunto:</strong> $asunto</p>
         <p><strong>Mensaje:</strong><br>$mensaje</p>";

// IMPORTAR MOTOR SMTP DIRECTO
require_once 'class.smtp.php';

// ENVÍO DIRECTO A MÚLTIPLES DESTINATARIOS
$success = false;
foreach ($admin_emails as $email) {
    if (SimpleSMTP::send($email, $asunto, $body, $smtp_user, $smtp_pass, "Web La Pequeña Zarahy")) {
        $success = true;
    }
}

if ($success) {
    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado con éxito!']);
} else {
    echo json_encode(['success' => false, 'message' => 'El servidor de correo no respondió.']);
}
