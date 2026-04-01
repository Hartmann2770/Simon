<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$DATA_FILE = __DIR__ . '/peg_data.json';
$PW_HASH_ADMIN    = '808927a4db0b89e1ca292ccfe2a9dcc77ebbc6a96be7c7115a0980f7c3e9e776';
$PW_HASH_PRACTICE = '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo file_exists($DATA_FILE) ? file_get_contents($DATA_FILE) : 'null';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incoming = json_decode(file_get_contents('php://input'), true);
    if (!$incoming) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad request']);
        exit;
    }

    // Kræv password — afvis requests uden
    $pw = $incoming['pw'] ?? null;
    if (!$pw) {
        http_response_code(401);
        echo json_encode(['error' => 'Kræver login']);
        exit;
    }
    $h = hash('sha256', $pw);
    $isAdmin = ($h === $PW_HASH_ADMIN);
    $isPractice = ($h === $PW_HASH_PRACTICE);
    if (!$isAdmin && !$isPractice) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Indlæs eksisterende serverstatus
    $existing = file_exists($DATA_FILE)
        ? json_decode(file_get_contents($DATA_FILE), true)
        : ['leitner' => [], 'history' => []];
    if (!$existing) $existing = ['leitner' => [], 'history' => []];

    // Merge leitner: behold kortet med den længste nextReview (bedste fremgang)
    $merged_leitner = $existing['leitner'] ?? [];
    foreach (($incoming['leitner'] ?? []) as $num => $state) {
        if (!isset($merged_leitner[$num]) || ($state['nextReview'] ?? 0) > ($merged_leitner[$num]['nextReview'] ?? 0)) {
            $merged_leitner[$num] = $state;
        }
    }

    // Merge history: kombiner og dedupliker på dato
    $existing_dates = [];
    foreach ($existing['history'] ?? [] as $s) {
        $existing_dates[$s['date']] = true;
    }
    $merged_history = $existing['history'] ?? [];
    foreach ($incoming['history'] ?? [] as $session) {
        if (!isset($existing_dates[$session['date']])) {
            $merged_history[] = $session;
        }
    }
    // Behold de seneste 180 dages sessioner
    $cutoff = (time() - 180 * 86400) * 1000;
    $merged_history = array_values(array_filter($merged_history, function($s) use ($cutoff) {
        return ($s['date'] ?? 0) >= $cutoff;
    }));

    // pegList: KUN admin kan ændre peg-ordene
    if ($isAdmin && isset($incoming['pegList'])) {
        $peg_list = $incoming['pegList'];
    } else {
        $peg_list = $existing['pegList'] ?? null;
    }

    $result = ['leitner' => $merged_leitner, 'history' => $merged_history];
    if ($peg_list !== null) $result['pegList'] = $peg_list;

    if (file_put_contents($DATA_FILE, json_encode($result, JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Skriv fejlede']);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
