<?php
session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config.php';
$admin_password = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : '';
$data_file = __DIR__ . '/../cobertura.json';
$upload_dir = __DIR__ . '/../uploads/cobertura/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

function respond($success, $message = '', $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function load_cobertura($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// GET no requiere auth para la web pública
if ($action === 'get') {
    respond(true, '', load_cobertura($data_file));
}

// ── PROTECCIÓN DE RUTA ─────────────────────────────────────
if (!isset($_SESSION['admin_ok']) || $_SESSION['admin_ok'] !== true) {
    respond(false, 'No autorizado');
}

$data = load_cobertura($data_file);

// ── ELIMINAR ───────────────────────────────────────────────
if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    $newData = [];
    foreach ($data as $item) {
        if ($item['id'] == $id) {
            // Borrar fotos físicas
            if (isset($item['coordinadores'])) {
                foreach ($item['coordinadores'] as $c) {
                    if ($c['foto'] && file_exists(__DIR__ . '/../' . $c['foto'])) {
                        @unlink(__DIR__ . '/../' . $c['foto']);
                    }
                }
            } else if ($item['foto'] && file_exists(__DIR__ . '/../' . $item['foto'])) {
                @unlink(__DIR__ . '/../' . $item['foto']);
            }
        } else {
            $newData[] = $item;
        }
    }
    file_put_contents($data_file, json_encode($newData, JSON_PRETTY_PRINT));
    respond(true, 'Localidad eliminada');
}

// ── AÑADIR / EDITAR ────────────────────────────────────────
if ($action === 'add' || $action === 'edit') {
    $id = $_POST['id'] ?: uniqid();
    $localidad = $_POST['localidad'] ?? '';
    $coord_nombres = $_POST['coord_nombres'] ?? [];
    $coord_fotos_prev = $_POST['coord_fotos_prev'] ?? [];
    
    $nuevos_coordinadores = [];

    foreach ($coord_nombres as $index => $nombre) {
        $foto_path = $coord_fotos_prev[$index] ?? '';
        
        // ¿Hay nueva foto para esta posición?
        if (isset($_FILES['coord_fotos']['name'][$index]) && $_FILES['coord_fotos']['error'][$index] === UPLOAD_ERR_OK) {
            $file = [
                'name'     => $_FILES['coord_fotos']['name'][$index],
                'type'     => $_FILES['coord_fotos']['type'][$index],
                'tmp_name' => $_FILES['coord_fotos']['tmp_name'][$index],
                'error'    => $_FILES['coord_fotos']['error'][$index],
                'size'     => $_FILES['coord_fotos']['size'][$index]
            ];

            // Validar peso (5MB)
            if ($file['size'] > 5 * 1024 * 1024) continue;

            // Validar extensión
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;

            // Borrar foto anterior si existía para esta posición
            if ($foto_path && file_exists(__DIR__ . '/../' . $foto_path)) {
                @unlink(__DIR__ . '/../' . $foto_path);
            }

            $new_name = 'coord_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $foto_path = 'uploads/cobertura/' . $new_name;
            }
        }
        
        $nuevos_coordinadores[] = [
            'nombre' => $nombre,
            'foto'   => $foto_path
        ];
    }

    $found = false;
    foreach ($data as &$item) {
        if ($item['id'] == $id) {
            $item['localidad'] = $localidad;
            $item['coordinadores'] = $nuevos_coordinadores;
            // Limpiar campos antiguos si existían
            unset($item['foto']);
            unset($item['nombres']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        $data[] = [
            'id' => $id,
            'localidad' => $localidad,
            'coordinadores' => $nuevos_coordinadores
        ];
    }

    file_put_contents($data_file, json_encode($data, JSON_PRETTY_PRINT));
    respond(true, 'Cobertura guardada con éxito');
}
