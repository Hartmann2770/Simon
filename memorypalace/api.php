<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$DATA_FILE = __DIR__ . '/palace_data.json';
$PW_HASH   = '808927a4db0b89e1ca292ccfe2a9dcc77ebbc6a96be7c7115a0980f7c3e9e776';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo file_exists($DATA_FILE) ? file_get_contents($DATA_FILE) : 'null';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || !isset($payload['pw']) || hash('sha256', $payload['pw']) !== $PW_HASH) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Server-side versionsbeskyttelse: klienten SKAL sende _baseVersion
    // som matcher serverens _version. Ellers afvises gem (stale data).
    $incoming = $payload['data'];
    $serverData = file_exists($DATA_FILE) ? json_decode(file_get_contents($DATA_FILE), true) : null;
    $serverVersion = $serverData ? ($serverData['_version'] ?? 0) : 0;
    $clientBase = $incoming['_baseVersion'] ?? null;

    // Klient SKAL sende _baseVersion for at gemme. Gamle klienter uden dette
    // felt afvises — de skal genindlæse for at få ny kode.
    if ($clientBase === null) {
        http_response_code(409);
        echo json_encode(['error' => 'upgrade_required',
                          'message' => 'Genindlæs siden for at få ny version.']);
        exit;
    }
    if ($clientBase < $serverVersion) {
        http_response_code(409);
        echo json_encode(['error' => 'conflict', 'serverVersion' => $serverVersion,
                          'message' => 'Server har nyere version. Genindlæs siden.']);
        exit;
    }

    // Bump version
    $incoming['_version'] = $serverVersion + 1;
    unset($incoming['_baseVersion']); // Fjern hjælpefeltet fra gemt data

    $max_bak = 5;
    if (file_exists($DATA_FILE . '.bak' . $max_bak)) unlink($DATA_FILE . '.bak' . $max_bak);
    for ($i = $max_bak - 1; $i >= 1; $i--) {
        $f = $DATA_FILE . '.bak' . $i;
        if (file_exists($f)) rename($f, $DATA_FILE . '.bak' . ($i + 1));
    }
    if (file_exists($DATA_FILE)) rename($DATA_FILE, $DATA_FILE . '.bak1');
    if (file_put_contents($DATA_FILE, json_encode($incoming, JSON_UNESCAPED_UNICODE)) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Skriv fejlede']);
        exit;
    }
    echo json_encode(['ok' => true, 'version' => $incoming['_version']]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
