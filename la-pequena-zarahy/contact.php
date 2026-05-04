<?php
/**
 * contact.php - Maneja el formulario de contacto principal
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// CONFIGURACIÓN
$admin_email = 'zarahygardenia@gmail.com';

// Capturar y limpiar datos
$nombre  = trim($_POST['nombre']  ?? '');
$correo  = trim($_POST['correo']  ?? '');
$asunto  = trim($_POST['asunto']  ?? '');
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

// Preparar el correo
$to      = $admin_email;
$subject = "Nuevo mensaje de contacto: $asunto";
$body    = "Has recibido un nuevo mensaje desde el sitio web La Pequeña Zarahy.\n\n";
$body   .= "Nombre: $nombre\n";
$body   .= "Correo: $correo\n";
$body   .= "Asunto: $asunto\n\n";
$body   .= "Mensaje:\n$mensaje\n";

$headers = "From: webmaster@lapequenazarahy.com\r\n"; // Cambiar por dominio real al subir
$headers .= "Reply-To: $correo\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Enviar
if (mail($to, $subject, $body, $headers)) {
    echo json_encode(['success' => true, 'message' => '¡Tu mensaje ha sido enviado con éxito! Nos pondremos en contacto pronto.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lo sentimos, hubo un error al enviar el correo. Por favor, intenta de nuevo más tarde.']);
}
