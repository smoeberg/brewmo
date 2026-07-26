<?php

/**
 * BrewMo 2.0 REST API Endpoint: Ingest IoT sensor readings and manual QC measurements.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use BrewMo\Domain\QualityControl\MeasurementType;
use BrewMo\Domain\QualityControl\QCLog;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use POST.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || !isset($data['brew_session_id'], $data['type'], $data['value'], $data['unit'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: brew_session_id, type, value, unit']);
    exit;
}

try {
    $measurementType = MeasurementType::from(strtoupper($data['type']));
} catch (\ValueError $e) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid measurement type. Allowed: GRAVITY, TEMPERATURE, PH, DISSOLVED_OXYGEN, PRESSURE, SENSORY_RATING']);
    exit;
}

$qcLog = new QCLog(
    null,
    (int)$data['brew_session_id'],
    $measurementType,
    (float)$data['value'],
    (string)$data['unit'],
    $data['recorded_at'] ?? date('Y-m-d H:i:s'),
    $data['notes'] ?? null,
    $data['sensor_id'] ?? null
);

// Response confirmation
echo json_encode([
    'status' => 'success',
    'message' => 'Measurement ingested successfully',
    'data' => [
        'brew_session_id' => $qcLog->getBrewSessionId(),
        'type' => $qcLog->getType()->value,
        'value' => $qcLog->getValue(),
        'unit' => $qcLog->getUnit(),
        'recorded_at' => $qcLog->getRecordedAt(),
        'sensor_id' => $qcLog->getSensorId()
    ]
]);
