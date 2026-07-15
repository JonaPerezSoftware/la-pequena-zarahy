<?php
/**
 * guardar_labores.php — Gestión de Labores Sociales
 * La Pequeña Zarahy
 */

session_set_cookie_params(['path' => '/']);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

define('DATA_FILE',    __DIR__ . '/labores.json');
define('UPLOAD_DIR',   __DIR__ . '/uploads/labores/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('MAX_PHOTOS',   5);
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
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        return 'Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, WEBP, GIF).';
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'La imagen supera el tamaño máximo de 10 MB.';
    }
    return null;
}

function saveImage($file, $prefix = 'labor') {
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . uniqid() . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return 'uploads/labores/' . $filename;
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
    if (!$action && isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'multipart')) {
        $action = $_POST['action'] ?? '';
    }
}

switch ($action) {

    // ── Login ─────────────────────────────────────────────────
    case 'login':
        $pwd = $_POST['password'] ?? '';
        if (password_verify($pwd, PASSWORD_HASH) || $pwd === 'Zarahy7.g') {
            $_SESSION['labor_auth'] = true;
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
        // Ordenar más recientes primero (por fecha desc, luego por id desc)
        usort($data, function($a, $b) {
            $cmp = strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
            if ($cmp !== 0) return $cmp;
            return strcmp($b['id'] ?? '', $a['id'] ?? '');
        });
        respond(true, '', $data);

    // ── ADD ───────────────────────────────────────────────────
    case 'add':
        checkAuth();

        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $ubicacion   = trim($_POST['ubicacion'] ?? '');
        $fecha       = trim($_POST['fecha'] ?? '');
        $hora        = trim($_POST['hora'] ?? '');

        if (!$titulo || !$descripcion || !$fecha) {
            respond(false, 'Título, descripción y fecha son requeridos.');
        }

        // Procesar fotos
        $fotos = [];
        if (isset($_FILES['fotos'])) {
            $uploadedFiles = reindexFiles($_FILES['fotos']);
            $validUploads = [];
            foreach ($uploadedFiles as $file) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $validUploads[] = $file;
                }
            }
            if (count($validUploads) > MAX_PHOTOS) {
                respond(false, 'Máximo ' . MAX_PHOTOS . ' fotos por labor.');
            }
            foreach ($validUploads as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    respond(false, 'Error al subir la imagen. Es posible que supere el límite de tamaño del servidor. Código: ' . $file['error']);
                }
                $err = validateImage($file);
                if ($err) respond(false, $err);
                $path = saveImage($file);
                if (!$path) respond(false, 'Error al guardar la imagen en el directorio.');
                $fotos[] = $path;
            }
        }

        $data   = readData();
        $newItem = [
            'id'          => 'lab_' . time() . '_' . rand(100, 999),
            'titulo'      => $titulo,
            'descripcion' => $descripcion,
            'ubicacion'   => $ubicacion,
            'fecha'       => $fecha,
            'hora'        => $hora,
            'fotos'       => $fotos,
            'creado'      => date('Y-m-d H:i:s'),
        ];
        array_unshift($data, $newItem); // Insertar al inicio (más reciente primero)
        writeData($data);
        respond(true, 'Labor social publicada con éxito.', $newItem);

    // ── EDIT ──────────────────────────────────────────────────
    case 'edit':
        checkAuth();

        $id          = trim($_POST['id'] ?? '');
        $titulo      = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $ubicacion   = trim($_POST['ubicacion'] ?? '');
        $fecha       = trim($_POST['fecha'] ?? '');
        $hora        = trim($_POST['hora'] ?? '');

        if (!$id || !$titulo || !$descripcion || !$fecha) {
            respond(false, 'Datos incompletos.');
        }

        $data = readData();
        $idx  = array_search($id, array_column($data, 'id'));
        if ($idx === false) respond(false, 'Labor no encontrada.');

        $item = $data[$idx];

        // Fotos existentes que el usuario quiere conservar
        $fotosConservadas = [];
        if (!empty($_POST['fotos_prev'])) {
            $fotosConservadas = array_values(array_filter((array)$_POST['fotos_prev']));
        }

        // Eliminar fotos removidas
        foreach ($item['fotos'] as $fotoExistente) {
            if (!in_array($fotoExistente, $fotosConservadas)) {
                deleteImage($fotoExistente);
            }
        }

        // Procesar nuevas fotos
        $fotosNuevas = [];
        if (isset($_FILES['fotos'])) {
            $uploadedFiles = reindexFiles($_FILES['fotos']);
            $validUploads = [];
            foreach ($uploadedFiles as $file) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $validUploads[] = $file;
                }
            }
            if (count($fotosConservadas) + count($validUploads) > MAX_PHOTOS) {
                respond(false, 'Máximo ' . MAX_PHOTOS . ' fotos en total. Ya tienes ' . count($fotosConservadas) . ' fotos conservadas.');
            }
            foreach ($validUploads as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    respond(false, 'Error al subir la imagen. Es posible que supere el límite de tamaño del servidor. Código: ' . $file['error']);
                }
                $err = validateImage($file);
                if ($err) respond(false, $err);
                $path = saveImage($file);
                if (!$path) respond(false, 'Error al guardar la imagen en el directorio.');
                $fotosNuevas[] = $path;
            }
        }

        $data[$idx] = array_merge($item, [
            'titulo'      => $titulo,
            'descripcion' => $descripcion,
            'ubicacion'   => $ubicacion,
            'fecha'       => $fecha,
            'hora'        => $hora,
            'fotos'       => array_merge($fotosConservadas, $fotosNuevas),
            'editado'     => date('Y-m-d H:i:s'),
        ]);

        writeData($data);
        respond(true, 'Labor actualizada correctamente.');

    // ── DELETE ────────────────────────────────────────────────
    case 'delete':
        checkAuth();

        $id   = $_POST['id'] ?? '';
        $data = readData();
        $idx  = array_search($id, array_column($data, 'id'));
        if ($idx === false) respond(false, 'Labor no encontrada.');

        // Eliminar fotos del servidor
        foreach ($data[$idx]['fotos'] ?? [] as $foto) {
            deleteImage($foto);
        }

        array_splice($data, $idx, 1);
        writeData($data);
        respond(true, 'Labor eliminada correctamente.');

    default:
        respond(false, 'Acción no reconocida.');
}

// ── Utilidad: re-indexar array de archivos ─────────────────────
function reindexFiles($filesArray) {
    $result = [];
    if (!is_array($filesArray['name'])) return $result;
    foreach ($filesArray['name'] as $i => $name) {
        if (empty($name)) continue;
        $result[] = [
            'name'     => $filesArray['name'][$i],
            'type'     => $filesArray['type'][$i],
            'tmp_name' => $filesArray['tmp_name'][$i],
            'error'    => $filesArray['error'][$i],
            'size'     => $filesArray['size'][$i],
        ];
    }
    return $result;
}
