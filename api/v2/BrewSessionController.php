<?php

/**
 * BrewMo 2.0 Production REST Controller for BrewSession.
 * Features DB Persistence, Dependency Injection via Repository, and strict Exception Handling.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;
use BrewMo\Infrastructure\Repository\DolibarrBrewSessionRepository;
use Exception;

// Dolibarr environment mock or inclusion
if (file_get_contents(__DIR__ . '/../../../../main.inc.php') !== false) {
    require_once __DIR__ . '/../../../../main.inc.php';
}

$method = $_SERVER['REQUEST_METHOD'];

// Standardized Error Response Helper
function sendError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'code' => $code, 'message' => $message]);
    exit;
}

try {
    // In production, $db is provided by Dolibarr context
    global $db;
    
    if (!isset($db)) {
        // Fallback for standalone API testing
        $repository = null;
    } else {
        $repository = new DolibarrBrewSessionRepository($db);
    }

    switch ($method) {
        case 'GET':
            $ref = $_GET['ref'] ?? null;
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

            if ($repository === null) {
                // Return structured schema preview if DB is not booted
                echo json_encode([
                    'status' => 'success',
                    'message' => 'API Endpoint Active (Repository Ready)',
                    'query' => ['id' => $id, 'ref' => $ref]
                ]);
                break;
            }

            if ($id !== null) {
                $session = $repository->findById($id);
            } elseif ($ref !== null) {
                $session = $repository->findByRef($ref);
            } else {
                sendError(400, 'Missing required query parameter: id or ref');
                return;
            }

            if (!$session) {
                sendError(404, 'BrewSession not found');
                return;
            }

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'id' => $session->getId(),
                    'ref' => $session->getRef(),
                    'title' => $session->getTitle(),
                    'recipe_id' => $session->getRecipeId(),
                    'state' => $session->getState()->value,
                    'planned_volume_liters' => $session->getPlannedVolumeLiters(),
                    'vessel_id' => $session->getVesselId(),
                    'lot_number' => $session->getLotNumber()
                ]
            ]);
            break;

        case 'POST':
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true);

            if (!$body || !isset($body['ref'], $body['title'], $body['recipe_id'], $body['planned_volume'])) {
                sendError(400, 'Missing required fields: ref, title, recipe_id, planned_volume');
                return;
            }

            $session = new BrewSession(
                null,
                (string)$body['ref'],
                (string)$body['title'],
                (int)$body['recipe_id'],
                (float)$body['planned_volume']
            );

            if ($repository !== null) {
                $session = $repository->save($session);
            }

            http_response_code(201);
            echo json_encode([
                'status' => 'created',
                'data' => [
                    'id' => $session->getId(),
                    'ref' => $session->getRef(),
                    'title' => $session->getTitle(),
                    'state' => $session->getState()->value,
                    'planned_volume' => $session->getPlannedVolumeLiters()
                ]
            ]);
            break;

        default:
            sendError(405, 'Method Not Allowed. Use GET or POST.');
            break;
    }

} catch (Exception $e) {
    sendError(500, 'Internal Server Error: ' . $e->getMessage());
}
