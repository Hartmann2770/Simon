<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$DATA_FILE = __DIR__ . '/palace_data.json';
$PW_HASH_ADMIN    = '808927a4db0b89e1ca292ccfe2a9dcc77ebbc6a96be7c7115a0980f7c3e9e776';
$PW_HASH_PRACTICE = '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4';

function checkPw($pw, $adminHash, $practiceHash) {
    $h = hash('sha256', $pw);
    if ($h === $adminHash) return 'admin';
    if ($h === $practiceHash) return 'practice';
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo file_exists($DATA_FILE) ? file_get_contents($DATA_FILE) : 'null';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || !isset($payload['pw'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $level = checkPw($payload['pw'], $PW_HASH_ADMIN, $PW_HASH_PRACTICE);
    if (!$level) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $action = $payload['action'] ?? 'save';

    // ── SAVE_SRS: Kun SRS-feltet på ét palads (øvelse + admin) ──
    if ($action === 'save_srs') {
        if (!isset($payload['palaceId']) || !isset($payload['srs'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Mangler palaceId eller srs']);
            exit;
        }
        if (!file_exists($DATA_FILE)) {
            http_response_code(404);
            echo json_encode(['error' => 'Ingen data fundet']);
            exit;
        }
        $raw = file_get_contents($DATA_FILE);
        $data = json_decode($raw, true);
        if (!$data || !isset($data['palaces'])) {
            http_response_code(500);
            echo json_encode(['error' => 'Korrupt data']);
            exit;
        }

        $found = false;
        foreach ($data['palaces'] as &$palace) {
            if ($palace['id'] === $payload['palaceId']) {
                // Merge history: behold alle entries, dedup på dato
                $existingHist = $palace['srs']['history'] ?? [];
                $incomingHist = $payload['srs']['history'] ?? [];
                $byDate = [];
                foreach ($existingHist as $h) { $byDate[$h['date']] = $h; }
                foreach ($incomingHist as $h) { $byDate[$h['date']] = $h; }
                ksort($byDate);

                $palace['srs'] = $payload['srs'];
                $palace['srs']['history'] = array_values($byDate);
                $found = true;
                break;
            }
        }
        unset($palace);

        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Palads ikke fundet']);
            exit;
        }

        $data['_savedAt'] = round(microtime(true) * 1000);
        $data['_version'] = ($data['_version'] ?? 0) + 1;

        if (file_put_contents($DATA_FILE, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Skriv fejlede']);
            exit;
        }
        echo json_encode(['ok' => true, 'version' => $data['_version']]);
        exit;
    }

    // ── FULL SAVE: Kun admin ──
    if ($action === 'save') {
        if ($level !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Kræver admin-adgang']);
            exit;
        }
        if (!isset($payload['data'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Mangler data']);
            exit;
        }

        $incoming = $payload['data'];

        // Versionsbeskyttelse
        $serverData = file_exists($DATA_FILE) ? json_decode(file_get_contents($DATA_FILE), true) : null;
        $serverVersion = $serverData ? ($serverData['_version'] ?? 0) : 0;
        $clientBase = $incoming['_baseVersion'] ?? null;
        if ($clientBase !== null && $clientBase < $serverVersion) {
            http_response_code(409);
            echo json_encode(['error' => 'conflict', 'serverVersion' => $serverVersion]);
            exit;
        }

        $incoming['_version'] = $serverVersion + 1;
        unset($incoming['_baseVersion']);

        // Backup-rotation
        $max_bak = 5;
        if (file_exists($DATA_FILE . '.bak' . $max_bak)) unlink($DATA_FILE . '.bak' . $max_bak);
        for ($i = $max_bak - 1; $i >= 1; $i--) {
            $f = $DATA_FILE . '.bak' . $i;
            if (file_exists($f)) rename($f, $DATA_FILE . '.bak' . ($i + 1));
        }
        if (file_exists($DATA_FILE)) rename($DATA_FILE, $DATA_FILE . '.bak1');

        if (file_put_contents($DATA_FILE, json_encode($incoming, JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Skriv fejlede']);
            exit;
        }
        echo json_encode(['ok' => true, 'version' => $incoming['_version']]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Ukendt action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
