<?php
/**
 * BrewMo 2.0 REST API - IoT Quality Control Ingestion Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

// Load Dolibarr environment
$res = 0;
$paths = array(
    __DIR__ . '/../../../../main.inc.php',
    __DIR__ . '/../../../../../main.inc.php',
    __DIR__ . '/../../main.inc.php'
);
foreach ($paths as $p) {
    if (!$res && file_exists($p)) {
        $res = @include $p;
    }
}

if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => 'Dolibarr core environment initialization failed']);
    exit;
}

// Security Check: Authenticate & Authorize
if (empty($user->rights->brewmo->read) && empty($user->rights->brewmo->write) && empty($user->admin)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access Forbidden: Insufficient BrewMo API permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use POST.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

if (empty($data['brew_session_id']) || empty($data['measurement_type']) || !isset($data['value'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Missing required fields: brew_session_id, measurement_type, value']);
    exit;
}

$sessionId = (int) $data['brew_session_id'];
$type      = strtoupper(trim($data['measurement_type']));
$value     = (float) $data['value'];
$unit      = isset($data['unit']) ? trim($data['unit']) : '';
$sensor    = isset($data['sensor_id']) ? trim($data['sensor_id']) : 'IoT Sensor';

// Validate measurement type
$allowedTypes = ['GRAVITY', 'TEMPERATURE', 'PH', 'PRESSURE', 'DISSOLVED_OXYGEN'];
if (!in_array($type, $allowedTypes, true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid measurement_type. Allowed: ' . implode(', ', $allowedTypes)]);
    exit;
}

try {
    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_qc_log ";
    $sql .= "(fk_brew_session, measurement_type, val, unit, recorded_by, recorded_at) ";
    $sql .= "VALUES (" . $sessionId . ", '" . $db->escape($type) . "', " . $value . ", '" . $db->escape($unit) . "', '" . $db->escape($sensor) . "', NOW())";

    $resql = $db->query($sql);
    if (!$resql) {
        throw new RuntimeException("Database error during QC log insertion");
    }

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'QC measurement recorded successfully',
        'id' => $db->last_insert_id(MAIN_DB_PREFIX . "brew_qc_log")
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    // Mask raw database errors from API clients
    echo json_encode(['error' => 'Internal server error processing sensor payload']);
}
