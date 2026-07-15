<?php
/**
 * guardar_convenios.php — Gestión de Convenios Institucionales
 * La Pequeña Zarahy
 */

session_set_cookie_params(['path' => '/']);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

define('DATA_FILE',    __DIR__ . '/convenios.json');
define('UPLOAD_DIR',   __DIR__ . '/uploads/convenios/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('PASSWORD_HASH', password_hash('Zarahy7.g', PASSWORD_DEFAULT));

// ── Helpers ──────────────────────────────────────────────────
function respond($success, $message = '', $data = null) {
    $payload = ['success' => $success, 'message' => $message];
    if ($data !== null) $payload['data'] = $data;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function readData() {
    if (!file_exists(DATA_FILE)) return [];
    $raw = file_get_contents(DATA_FILE);
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function writeData(array $data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function isAuth() {
    return !empty($_SESSION['admin_ok']);
}

function checkAuth() {
    if (!isAuth()) respond(false, 'No autorizado. Por favor inicia sesión.');
}

function validateImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        return 'Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, WEBP, SVG).';
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'El logo supera el tamaño máximo de 10 MB.';
    }
    return null;
}

function saveLogo($file) {
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'conv_' . uniqid() . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return 'uploads/convenios/' . $filename;
}

function deleteImage($path) {
    if ($path && file_exists(__DIR__ . '/' . $path)) {
        @unlink(__DIR__ . '/' . $path);
    }
}

// ── Routing ───────────────────────────────────────────────────
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get';
} else {
    $action = $_POST['action'] ?? '';
}

switch ($action) {

    // ── Login ─────────────────────────────────────────────────
    case 'login':
        $pwd = $_POST['password'] ?? '';
        if (password_verify($pwd, PASSWORD_HASH) || $pwd === 'Zarahy7.g') {
            $_SESSION['convenio_auth'] = true;
            respond(true, 'Sesión iniciada.');
        }
        respond(false, 'Contraseña incorrecta.');

    // ── Logout ────────────────────────────────────────────────
    case 'logout':
        session_destroy();
        respond(true, 'Sesión cerrada.');

    // ── GET ───────────────────────────────────────────────────
    case 'get':
        $data = readData();
        respond(true, '', $data);

    // ── ADD ───────────────────────────────────────────────────
    case 'add':
        checkAuth();

        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) respond(false, 'El nombre de la institución es requerido.');

        $logoPath = '';
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $err = validateImage($_FILES['logo']);
            if ($err) respond(false, $err);
            $logoPath = saveLogo($_FILES['logo']);
            if (!$logoPath) respond(false, 'Error al guardar el logo. Intenta de nuevo.');
        }

        $data    = readData();
        $newItem = [
            'id'     => 'conv_' . time() . '_' . rand(100, 999),
            'nombre' => $nombre,
            'logo'   => $logoPath,
            'creado' => date('Y-m-d H:i:s'),
        ];
        $data[] = $newItem;
        writeData($data);
        respond(true, 'Convenio agregado con éxito.', $newItem);

    // ── EDIT ──────────────────────────────────────────────────
    case 'edit':
        checkAuth();

        $id     = trim($_POST['id'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$id || !$nombre) respond(false, 'Datos incompletos.');

        $data = readData();
        $idx  = array_search($id, array_column($data, 'id'));
        if ($idx === false) respond(false, 'Convenio no encontrado.');

        $item     = $data[$idx];
        $logoPath = $item['logo']; // Conservar logo actual por defecto

        // Si se subió un nuevo logo
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $err = validateImage($_FILES['logo']);
            if ($err) respond(false, $err);
            $newLogo = saveLogo($_FILES['logo']);
            if (!$newLogo) respond(false, 'Error al guardar el nuevo logo.');
            // Eliminar logo anterior
            deleteImage($item['logo']);
            $logoPath = $newLogo;
        }

        $data[$idx] = array_merge($item, [
            'nombre'  => $nombre,
            'logo'    => $logoPath,
            'editado' => date('Y-m-d H:i:s'),
        ]);

        writeData($data);
        respond(true, 'Convenio actualizado correctamente.');

    // ── DELETE ────────────────────────────────────────────────
    case 'delete':
        checkAuth();

        $id   = $_POST['id'] ?? '';
        $data = readData();
        $idx  = array_search($id, array_column($data, 'id'));
        if ($idx === false) respond(false, 'Convenio no encontrado.');

        deleteImage($data[$idx]['logo']);
        array_splice($data, $idx, 1);
        writeData($data);
        respond(true, 'Convenio eliminado correctamente.');

    default:
        respond(false, 'Acción no reconocida.');
}
