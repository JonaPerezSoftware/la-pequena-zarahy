<?php
/**
 * guardar_labores.php — Gestión de Labores Sociales
 * La Pequeña Zarahy
 */

session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

@ini_set('max_execution_time', '600');
@ini_set('max_input_time', '600');
@ini_set('memory_limit', '512M');

define('DATA_FILE',    __DIR__ . '/labores.json');
define('UPLOAD_DIR',   __DIR__ . '/uploads/labores/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB para imágenes
define('MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100 MB para videos
define('MAX_PHOTOS',   10);
define('MAX_VIDEOS',   5);
define('PASSWORD_HASH', password_hash('Zarahy7.g', PASSWORD_DEFAULT));

// ── Helpers ──────────────────────────────────────────────────
function respond($success, $message = '', $data = null) {
    $payload = ['success' => $success, 'message' => $message];
    if ($data !== null) $payload['data'] = $data;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El video o archivo supera el tamaño máximo permitido por la configuración PHP del servidor (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo supera el tamaño máximo especificado en el formulario.';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo solo se subió parcialmente debido a una interrupción en la conexión.';
        case UPLOAD_ERR_NO_FILE:
            return 'No se subió ningún archivo.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal en el servidor para almacenar la subida.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en el disco del servidor.';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida del archivo.';
        default:
            return 'Error al subir el archivo (Código: ' . $errorCode . ').';
    }
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
    return !empty($_SESSION['admin_ok']) || !empty($_SESSION['labor_auth']);
}

function checkAuth() {
    if (!isAuth()) respond(false, 'No autorizado. Por favor inicia sesión.');
}

function getMimeType($tmpName) {
    if (!empty($tmpName) && file_exists($tmpName)) {
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = @finfo_file($finfo, $tmpName);
                @finfo_close($finfo);
                if ($mime) return $mime;
            }
        }
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmpName);
            if ($mime) return $mime;
        }
    }
    return '';
}

function validateImage($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return 'No se recibió la imagen correctamente en el servidor.';
    }

    $mime = getMimeType($file['tmp_name']);
    $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if ($mime && !in_array($mime, $allowedTypes) && !in_array($ext, $allowedExts)) {
        return 'Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, WEBP, GIF).';
    }
    if (!in_array($ext, $allowedExts)) {
        return 'Extensión de imagen no permitida. Solo se aceptan imágenes (JPG, PNG, WEBP, GIF).';
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'La imagen supera el tamaño máximo de 10 MB.';
    }
    return null;
}

function validateVideo($file) {
    $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo', 'video/3gpp', 'video/avi', 'video/mpeg'];
    $allowedExts  = ['mp4', 'webm', 'ogg', 'mov', 'avi', '3gp', 'm4v'];

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return 'No se recibió el video correctamente en el servidor.';
    }

    $mime = getMimeType($file['tmp_name']);
    $ext  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if ($mime && !in_array($mime, $allowedTypes) && !in_array($ext, $allowedExts)) {
        return 'Tipo de video no permitido. Solo se aceptan videos (MP4, WEBM, OGG, MOV, AVI, 3GP, M4V).';
    }
    if (!in_array($ext, $allowedExts)) {
        return 'Extensión de video no permitida. Solo se aceptan videos (MP4, WEBM, OGG, MOV, AVI, 3GP, M4V).';
    }
    if ($file['size'] > MAX_VIDEO_SIZE) {
        return 'El video supera el tamaño máximo de 100 MB.';
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

function saveVideo($file, $prefix = 'labor_vid') {
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

// Check for post_max_size overflow
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? $_SERVER['HTTP_CONTENT_LENGTH'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && $contentLength > 0) {
    $postMax = ini_get('post_max_size');
    respond(false, "El peso total de los videos y fotos enviados supera el límite del servidor (post_max_size = {$postMax}). Intenta subir los videos de uno en uno o comprimirlos.");
}

// ── Routing ───────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
                    respond(false, getUploadErrorMessage($file['error']));
                }
                $err = validateImage($file);
                if ($err) respond(false, $err);
                $path = saveImage($file);
                if (!$path) respond(false, 'Error al guardar la imagen en el directorio.');
                $fotos[] = $path;
            }
        }

        // Procesar videos
        $videos = [];
        if (isset($_FILES['videos'])) {
            $uploadedVideos = reindexFiles($_FILES['videos']);
            $validVideos = [];
            foreach ($uploadedVideos as $file) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $validVideos[] = $file;
                }
            }
            if (count($validVideos) > MAX_VIDEOS) {
                respond(false, 'Máximo ' . MAX_VIDEOS . ' videos por labor.');
            }
            foreach ($validVideos as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    respond(false, getUploadErrorMessage($file['error']));
                }
                $err = validateVideo($file);
                if ($err) respond(false, $err);
                $path = saveVideo($file);
                if (!$path) respond(false, 'Error al guardar el video en el directorio.');
                $videos[] = $path;
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
            'videos'      => $videos,
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
                    respond(false, getUploadErrorMessage($file['error']));
                }
                $err = validateImage($file);
                if ($err) respond(false, $err);
                $path = saveImage($file);
                if (!$path) respond(false, 'Error al guardar la imagen en el directorio.');
                $fotosNuevas[] = $path;
            }
        }

        // Videos existentes que el usuario quiere conservar
        $videosConservados = [];
        if (!empty($_POST['videos_prev'])) {
            $videosConservados = array_values(array_filter((array)$_POST['videos_prev']));
        }

        // Eliminar videos removidos
        foreach ($item['videos'] ?? [] as $videoExistente) {
            if (!in_array($videoExistente, $videosConservados)) {
                deleteImage($videoExistente);
            }
        }

        // Procesar nuevos videos
        $videosNuevos = [];
        if (isset($_FILES['videos'])) {
            $uploadedVideos = reindexFiles($_FILES['videos']);
            $validVideos = [];
            foreach ($uploadedVideos as $file) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $validVideos[] = $file;
                }
            }
            if (count($videosConservados) + count($validVideos) > MAX_VIDEOS) {
                respond(false, 'Máximo ' . MAX_VIDEOS . ' videos en total. Ya tienes ' . count($videosConservados) . ' videos conservados.');
            }
            foreach ($validVideos as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    respond(false, getUploadErrorMessage($file['error']));
                }
                $err = validateVideo($file);
                if ($err) respond(false, $err);
                $path = saveVideo($file);
                if (!$path) respond(false, 'Error al guardar el video en el directorio.');
                $videosNuevos[] = $path;
            }
        }

        $data[$idx] = array_merge($item, [
            'titulo'      => $titulo,
            'descripcion' => $descripcion,
            'ubicacion'   => $ubicacion,
            'fecha'       => $fecha,
            'hora'        => $hora,
            'fotos'       => array_merge($fotosConservadas, $fotosNuevas),
            'videos'      => array_merge($videosConservados, $videosNuevos),
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

        // Eliminar fotos y videos del servidor
        foreach ($data[$idx]['fotos'] ?? [] as $foto) {
            deleteImage($foto);
        }
        foreach ($data[$idx]['videos'] ?? [] as $video) {
            deleteImage($video);
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
    if (!isset($filesArray['name'])) return $result;

    if (!is_array($filesArray['name'])) {
        if (!empty($filesArray['name']) || ($filesArray['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $result[] = $filesArray;
        }
        return $result;
    }

    foreach ($filesArray['name'] as $i => $name) {
        $error = $filesArray['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if (empty($name) && $error === UPLOAD_ERR_NO_FILE) continue;

        $result[] = [
            'name'     => $filesArray['name'][$i] ?? '',
            'type'     => $filesArray['type'][$i] ?? '',
            'tmp_name' => $filesArray['tmp_name'][$i] ?? '',
            'error'    => $error,
            'size'     => $filesArray['size'][$i] ?? 0,
        ];
    }
    return $result;
}
