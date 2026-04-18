<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$dataFile   = __DIR__ . '/data.json';
$uploadDir  = __DIR__ . '/uploads/';
$configFile = __DIR__ . '/config.php';

// Ensure directories exist
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Initialize data file
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([
        'about'    => ['text' => '', 'image' => ''],
        'albums'   => [],
        'art'      => [],
        'settings' => ['layout' => 'masonry', 'theme' => 'glad']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Password hash — generated once, stored in config.php (not downloadable)
if (!file_exists($configFile)) {
    $hash = password_hash('Hartmann', PASSWORD_DEFAULT);
    file_put_contents($configFile, "<?php\nreturn " . var_export(['passwordHash' => $hash], true) . ";\n");
}
$config = include $configFile;

function isLoggedIn() {
    return !empty($_SESSION['admin']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Ikke logget ind']);
        exit;
    }
}

function readData() {
    global $dataFile;
    $data = json_decode(file_get_contents($dataFile), true);

    if (!isset($data['albums'])) {
        $data['albums'] = [
            [
                'id'    => bin2hex(random_bytes(8)),
                'name'  => 'Tegneskole',
                'order' => 0,
            ],
        ];
        foreach ($data['art'] as &$a) {
            unset($a['category']);
            $a['albumId'] = null;
        }
        unset($a);
        writeData($data);
    }

    return $data;
}

function writeData($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function validateImage($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return 'Kun jpg, png, webp, gif';
    if ($file['size'] > 10 * 1024 * 1024) return 'Maks 10 MB';
    if ($file['error'] !== UPLOAD_ERR_OK) return 'Upload fejlede';
    return null;
}

function sanitizeFilename($title) {
    $name = mb_strtolower($title, 'UTF-8');
    $name = preg_replace('/[^a-z0-9æøåé -]/u', '', $name);
    $name = preg_replace('/\s+/', '-', trim($name));
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    if (!$name) $name = 'kunst';
    return $name;
}

function uniqueFilename($dir, $base, $ext) {
    $filename = $base . '.' . $ext;
    $counter = 1;
    while (file_exists($dir . $filename)) {
        $filename = $base . '-' . $counter . '.' . $ext;
        $counter++;
    }
    return $filename;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'login':
        $pw = $_POST['password'] ?? '';
        if (password_verify($pw, $config['passwordHash'])) {
            $_SESSION['admin'] = true;
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Forkert kodeord']);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['ok' => true]);
        break;

    case 'checkAuth':
        echo json_encode(['loggedIn' => isLoggedIn()]);
        break;

    case 'getData':
        $data = readData();
        // Strip invisible art for public requests
        if (!isLoggedIn()) {
            $data['art'] = array_values(array_filter($data['art'], fn($a) => $a['visible']));
        }
        echo json_encode($data);
        break;

    case 'upload':
        requireAuth();
        if (empty($_FILES['image'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ingen fil']);
            exit;
        }
        $err = validateImage($_FILES['image']);
        if ($err) { http_response_code(400); echo json_encode(['error' => $err]); exit; }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $id  = bin2hex(random_bytes(8));
        $title = $_POST['title'] ?? 'Uden titel';
        $filename = uniqueFilename($uploadDir, sanitizeFilename($title), $ext);
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);

        $data = readData();
        // Ryk alle eksisterende items én op så nyt kunst havner først
        foreach ($data['art'] as &$a) {
            $a['order'] = ($a['order'] ?? 0) + 1;
        }
        unset($a);
        $data['art'][] = [
            'id'          => $id,
            'title'       => $title,
            'description' => $_POST['description'] ?? '',
            'albumId'     => (isset($_POST['albumId']) && $_POST['albumId'] !== '') ? $_POST['albumId'] : null,
            'image'       => 'uploads/' . $filename,
            'visible'     => true,
            'featured'    => false,
            'order'       => 0,
            'createdAt'   => date('Y-m-d')
        ];
        writeData($data);
        echo json_encode(['ok' => true, 'id' => $id]);
        break;

    case 'updateArt':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = $body['id'] ?? '';
        $data = readData();

        foreach ($data['art'] as &$item) {
            if ($item['id'] === $id) {
                // Omdøb fil hvis titel ændres
                if (isset($body['title']) && $body['title'] !== $item['title'] && !empty($item['image'])) {
                    $oldPath = __DIR__ . '/' . $item['image'];
                    $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
                    $newFilename = uniqueFilename($uploadDir, sanitizeFilename($body['title']), $ext);
                    if (file_exists($oldPath)) {
                        rename($oldPath, $uploadDir . $newFilename);
                        $item['image'] = 'uploads/' . $newFilename;
                    }
                }
                foreach (['title','description'] as $k) {
                    if (isset($body[$k])) $item[$k] = $body[$k];
                }
                if (array_key_exists('albumId', $body)) {
                    $item['albumId'] = ($body['albumId'] === '' || $body['albumId'] === null) ? null : $body['albumId'];
                }
                if (isset($body['visible']))  $item['visible']  = (bool)$body['visible'];
                if (isset($body['featured']))  $item['featured'] = (bool)$body['featured'];
                break;
            }
        }
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'deleteArt':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = $body['id'] ?? '';
        $data = readData();

        foreach ($data['art'] as $i => $item) {
            if ($item['id'] === $id) {
                $path = __DIR__ . '/' . $item['image'];
                if (file_exists($path)) unlink($path);
                array_splice($data['art'], $i, 1);
                break;
            }
        }
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'reorderArt':
        requireAuth();
        $body  = json_decode(file_get_contents('php://input'), true);
        $order = $body['order'] ?? [];
        $data  = readData();

        $byId = [];
        foreach ($data['art'] as $a) $byId[$a['id']] = $a;

        $sorted = [];
        foreach ($order as $i => $id) {
            if (isset($byId[$id])) {
                $byId[$id]['order'] = $i;
                $sorted[] = $byId[$id];
                unset($byId[$id]);
            }
        }
        foreach ($byId as $a) $sorted[] = $a;

        $data['art'] = $sorted;
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'saveAbout':
        requireAuth();
        $data = readData();
        $data['about']['text'] = $_POST['text'] ?? $data['about']['text'];

        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $err = validateImage($_FILES['image']);
            if ($err) { http_response_code(400); echo json_encode(['error' => $err]); exit; }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = 'about-' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);

            // Remove old
            if ($data['about']['image'] && file_exists(__DIR__ . '/' . $data['about']['image'])) {
                unlink(__DIR__ . '/' . $data['about']['image']);
            }
            $data['about']['image'] = 'uploads/' . $filename;
        }

        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'saveSettings':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $data = readData();
        if (isset($body['layout'])) $data['settings']['layout'] = $body['layout'];
        if (isset($body['theme']))  $data['settings']['theme']  = $body['theme'];
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'createAlbum':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $name = trim($body['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 60) {
            http_response_code(400);
            echo json_encode(['error' => 'Navn skal være 1–60 tegn']);
            exit;
        }
        $data = readData();
        $maxOrder = -1;
        foreach ($data['albums'] as $al) $maxOrder = max($maxOrder, $al['order'] ?? 0);
        $id = bin2hex(random_bytes(8));
        $data['albums'][] = ['id' => $id, 'name' => $name, 'order' => $maxOrder + 1];
        writeData($data);
        echo json_encode(['ok' => true, 'id' => $id]);
        break;

    case 'updateAlbum':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = $body['id'] ?? '';
        $name = trim($body['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 60) {
            http_response_code(400);
            echo json_encode(['error' => 'Navn skal være 1–60 tegn']);
            exit;
        }
        $data = readData();
        foreach ($data['albums'] as &$al) {
            if ($al['id'] === $id) { $al['name'] = $name; break; }
        }
        unset($al);
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'deleteAlbum':
        requireAuth();
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = $body['id'] ?? '';
        $data = readData();
        $data['albums'] = array_values(array_filter(
            $data['albums'],
            fn($al) => $al['id'] !== $id
        ));
        foreach ($data['art'] as &$a) {
            if (($a['albumId'] ?? null) === $id) $a['albumId'] = null;
        }
        unset($a);
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    case 'reorderAlbums':
        requireAuth();
        $body  = json_decode(file_get_contents('php://input'), true);
        $order = $body['order'] ?? [];
        $data  = readData();
        $byId = [];
        foreach ($data['albums'] as $al) $byId[$al['id']] = $al;
        $sorted = [];
        foreach ($order as $i => $id) {
            if (isset($byId[$id])) {
                $byId[$id]['order'] = $i;
                $sorted[] = $byId[$id];
                unset($byId[$id]);
            }
        }
        foreach ($byId as $al) $sorted[] = $al;
        $data['albums'] = $sorted;
        writeData($data);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ukendt handling']);
}
