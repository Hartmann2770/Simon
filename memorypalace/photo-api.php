<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$PW_HASH_ADMIN = '808927a4db0b89e1ca292ccfe2a9dcc77ebbc6a96be7c7115a0980f7c3e9e776';
$PHOTO_DIR = __DIR__ . '/photos';

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['pw']) || hash('sha256', $payload['pw']) !== $PW_HASH_ADMIN) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($payload['photo']) || !isset($payload['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Mangler photo eller name']);
    exit;
}

// Opret photos-mappe hvis den ikke findes
if (!is_dir($PHOTO_DIR)) {
    mkdir($PHOTO_DIR, 0755, true);
}

// Udtræk filnavn fra lokationsnavn: "1800 – Voltas batteri" → "1800"
$name = $payload['name'];
if (preg_match('/^(.+?)\s*[–\-]\s/', $name, $m)) {
    $name = $m[1];
}
$name = trim($name);
$name = preg_replace('/[^a-zA-Z0-9æøåÆØÅ_-]/u', '_', $name);
$name = preg_replace('/_+/', '_', $name);
$name = trim($name, '_');
if (!$name) $name = 'photo_' . time();

$filename = $name . '.jpg';
$filepath = $PHOTO_DIR . '/' . $filename;

// Dekod base64
$b64 = $payload['photo'];
if (strpos($b64, 'data:') === 0) {
    $b64 = preg_replace('/^data:[^;]+;base64,/', '', $b64);
}
$imageData = base64_decode($b64);
if (!$imageData) {
    http_response_code(400);
    echo json_encode(['error' => 'Ugyldig billeddata']);
    exit;
}

if (file_put_contents($filepath, $imageData) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Kunne ikke gemme foto']);
    exit;
}

echo json_encode(['ok' => true, 'url' => 'photos/' . $filename]);
