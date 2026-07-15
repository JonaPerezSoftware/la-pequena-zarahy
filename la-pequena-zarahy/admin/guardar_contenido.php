<?php
// ── CONFIGURACIÓN ──────────────────────────────────────────
$admin_password = 'JoseyGarde1328';
$data_file = __DIR__ . '/../contenido_pagina.json';
$upload_dir = __DIR__ . '/../uploads/contenido/';

session_name('PHPSESSID');
session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

function respond($success, $message = '', $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function load_contenido($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// GET no requiere auth para la web pública
if ($action === 'get') {
    respond(true, '', load_contenido($data_file));
}

// ── LOGIN (permite autenticarse directamente desde este endpoint) ────────
if ($action === 'login') {
    $pwd = trim($_POST['password'] ?? '');
    if ($pwd === trim($admin_password)) {
        $_SESSION['admin_ok'] = true;
        respond(true, 'OK');
    }
    respond(false, 'No autorizado');
}

// ── PROTECCIÓN DE RUTA ─────────────────────────────────────
// Acepta: sesión activa O contraseña enviada en la petición
$pwd_header = trim($_POST['_pwd'] ?? '');
$session_ok = isset($_SESSION['admin_ok']) && $_SESSION['admin_ok'] === true;
$pwd_ok     = ($pwd_header !== '' && $pwd_header === trim($admin_password));

if (!$session_ok && !$pwd_ok) {
    respond(false, 'No autorizado');
}

// Si autenticó por contraseña directa, propagar la sesión
if ($pwd_ok && !$session_ok) {
    $_SESSION['admin_ok'] = true;
}

// ── SUBIR IMAGEN ───────────────────────────────────────────
if ($action === 'upload_img') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'No se ha subido ninguna imagen o hubo un error en la subida.');
    }

    $file = $_FILES['image'];

    // Validar peso (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        respond(false, 'La imagen supera el tamaño máximo permitido de 5 MB.');
    }

    // Validar extensión
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
        respond(false, 'Extensión de archivo no permitida. Solo JPG, JPEG, PNG, WEBP y SVG.');
    }

    // Borrar foto anterior si se especificó
    $old_path = $_POST['old_path'] ?? '';
    if ($old_path) {
        $old_full_path = __DIR__ . '/../' . $old_path;
        // Solo borrar si es una imagen subida en uploads (no de la carpeta varios)
        if (str_contains($old_path, 'uploads/') && file_exists($old_full_path)) {
            @unlink($old_full_path);
        }
    }

    $new_name = 'img_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        $path = 'uploads/contenido/' . $new_name;
        respond(true, 'Imagen subida con éxito', ['path' => $path]);
    } else {
        respond(false, 'Error al guardar la imagen en el servidor.');
    }
}

// ── GUARDAR TODO EL CONTENIDO ──────────────────────────────
if ($action === 'save') {
    $raw_data = $_POST['data'] ?? '';
    if (!$raw_data) {
        respond(false, 'No se recibieron datos para guardar.');
    }

    $decoded_data = json_decode($raw_data, true);
    if ($decoded_data === null) {
        respond(false, 'Formato de datos no válido.');
    }

    // Guardar en el archivo JSON
    if (file_put_contents($data_file, json_encode($decoded_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        respond(true, 'Contenido guardado con éxito');
    } else {
        respond(false, 'Error al escribir el archivo de datos.');
    }
}

respond(false, 'Acción no válida');
