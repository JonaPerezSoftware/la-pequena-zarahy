<?php
// ============================================================
// admin/guardar_vacante.php — API para gestionar vacantes
// CONFIGURAR: cambiar $admin_password por una contraseña segura
// ============================================================

session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// ── CONFIGURACIÓN ──────────────────────────────────────────
$admin_password = 'JoseyGarde1328'; // ← CAMBIAR por contraseña segura
$data_file = __DIR__ . '/../vacantes.json';
// ───────────────────────────────────────────────────────────

function respond($success, $message = '', $data = null)
{
    $res = ['success' => $success, 'message' => $message];
    if ($data !== null)
        $res['data'] = $data;
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

function load_vacantes($file)
{
    if (!file_exists($file))
        return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

function save_vacantes($file, $data)
{
    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── LOGIN ──────────────────────────────────────────────────
if ($action === 'login') {
    $pwd = trim($_POST['password'] ?? '');
    if ($pwd === trim($admin_password)) {
        $_SESSION['admin_ok'] = true;
        respond(true, 'Bienvenida');
    } else {
        respond(false, 'Contraseña incorrecta. Verifica mayúsculas y puntos.');
    }
}

// ── CHECK SESSION ──────────────────────────────────────────
if ($action === 'check') {
    respond(isset($_SESSION['admin_ok']), '');
}

// ── LOGOUT ────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    respond(true, 'Sesión cerrada');
}

// ── REQUIERE AUTH para las siguientes acciones ─────────────
if (!isset($_SESSION['admin_ok'])) {
    http_response_code(401);
    respond(false, 'No autorizado. Por favor inicia sesión.');
}

// ── GET ────────────────────────────────────────────────────
if ($action === 'get') {
    respond(true, '', load_vacantes($data_file));
}

// ── ADD ────────────────────────────────────────────────────
if ($action === 'add') {
    $titulo = trim($_POST['titulo'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $req_raw = trim($_POST['requisitos'] ?? '');

    if (empty($titulo) || empty($descripcion)) {
        respond(false, 'El título y la descripción son obligatorios.');
    }

    $requisitos = array_values(array_filter(
        array_map('trim', explode("\n", $req_raw))
    ));

    $new = [
        'id' => uniqid('v_'),
        'titulo' => htmlspecialchars($titulo),
        'departamento' => htmlspecialchars($departamento),
        'tipo' => htmlspecialchars($tipo),
        'ubicacion' => htmlspecialchars($ubicacion),
        'descripcion' => htmlspecialchars($descripcion),
        'requisitos' => $requisitos,
        'fecha' => date('Y-m-d'),
        'activa' => true,
    ];

    $vacantes = load_vacantes($data_file);
    $vacantes[] = $new;
    save_vacantes($data_file, $vacantes);
    respond(true, 'Vacante publicada exitosamente.', $new);
}

// ── DELETE ─────────────────────────────────────────────────
if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (empty($id))
        respond(false, 'ID no válido.');
    $vacantes = load_vacantes($data_file);
    $vacantes = array_filter($vacantes, fn($v) => $v['id'] !== $id);
    save_vacantes($data_file, $vacantes);
    respond(true, 'Vacante eliminada.');
}

// ── TOGGLE ACTIVE ──────────────────────────────────────────
if ($action === 'toggle') {
    $id = $_POST['id'] ?? '';
    $vacantes = load_vacantes($data_file);
    foreach ($vacantes as &$v) {
        if ($v['id'] === $id) {
            $v['activa'] = !($v['activa'] ?? true);
            break;
        }
    }
    save_vacantes($data_file, $vacantes);
    respond(true, 'Estado actualizado.');
}

respond(false, 'Acción no reconocida.');
