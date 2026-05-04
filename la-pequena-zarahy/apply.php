<?php
// ============================================================
// apply.php — Maneja postulaciones: guarda el CV y envía email
// CONFIGURAR antes de subir a Hostinger:
//   $admin_email → correo donde llegarán las postulaciones
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ── CONFIGURACIÓN ──────────────────────────────────────────
$admin_email = 'japrezch10@gmail.com'; // ← Correo real para postulaciones
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

// ── Construir email con adjunto ────────────────────────────
$boundary = '==Boundary_' . md5(time());
$fecha = date('d/m/Y H:i');

$subject = "=?UTF-8?B?" . base64_encode("🆕 Nueva Postulación — {$vacante}") . "?=";

$headers = "From: noreply@{$_SERVER['HTTP_HOST']}\r\n";
$headers .= "Reply-To: {$correo}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

$html_body = "<html><body style='font-family:Arial,sans-serif;color:#1e1b4b;'>";
$html_body .= "<div style='max-width:600px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px;'>";
$html_body .= "<h2 style='color:#7c3aed;border-bottom:2px solid #7c3aed;padding-bottom:8px;'>📋 Nueva Postulación Recibida</h2>";
$html_body .= "<table style='width:100%;border-collapse:collapse;margin-top:16px;'>";
$html_body .= "<tr style='background:#f5f3ff;'><td style='padding:10px;font-weight:bold;width:35%;'>Vacante:</td><td style='padding:10px;'><strong>{$vacante}</strong></td></tr>";
$html_body .= "<tr><td style='padding:10px;font-weight:bold;'>Nombre:</td><td style='padding:10px;'>{$nombre}</td></tr>";
$html_body .= "<tr style='background:#f5f3ff;'><td style='padding:10px;font-weight:bold;'>Correo:</td><td style='padding:10px;'><a href='mailto:{$correo}'>{$correo}</a></td></tr>";
$html_body .= "<tr><td style='padding:10px;font-weight:bold;'>Teléfono:</td><td style='padding:10px;'>" . (!empty($telefono) ? $telefono : '—') . "</td></tr>";
$html_body .= "<tr style='background:#f5f3ff;'><td style='padding:10px;font-weight:bold;'>Fecha:</td><td style='padding:10px;'>{$fecha}</td></tr>";
if (!empty($mensaje)) {
    $html_body .= "<tr><td style='padding:10px;font-weight:bold;vertical-align:top;'>Mensaje:</td><td style='padding:10px;'>" . nl2br(htmlspecialchars($mensaje)) . "</td></tr>";
}
$html_body .= "</table>";
$html_body .= "<p style='margin-top:20px;padding:12px;background:#faf5ff;border-radius:8px;color:#6b7280;font-size:13px;'>📎 El CV se adjunta en este correo y también fue guardado en el servidor.</p>";
$html_body .= "</div></body></html>";

// Cuerpo del email
$body = "--{$boundary}\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $html_body . "\r\n";

// Adjunto PDF
$file_data = base64_encode(file_get_contents($filepath));
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: application/pdf; name=\"{$filename}\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
$body .= chunk_split($file_data) . "\r\n";
$body .= "--{$boundary}--";

$mail_sent = mail($admin_email, $subject, $body, $headers);

if ($mail_sent) {
    respond(true, '¡Postulación enviada con éxito! Nos comunicaremos contigo pronto.');
} else {
    // El CV se guardó aunque el correo falle
    respond(true, '¡CV recibido! Nos pondremos en contacto contigo pronto.');
}
