<?php

/**
 * BrewMo 2.0 REST Controller for BrewSession operations.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => 1,
                'ref' => 'BREW-2026-001',
                'title' => 'Dunkelweizen Batch #1',
                'state' => BrewSessionState::FERMENTING->value,
                'planned_volume_liters' => 1000.0,
                'lot_number' => 'LOT-2026-07-001'
            ]
        ]);
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (!isset($body['ref'], $body['title'], $body['recipe_id'], $body['planned_volume'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields.']);
            exit;
        }

        $session = new BrewSession(
            null,
            $body['ref'],
            $body['title'],
            (int)$body['recipe_id'],
            (float)$body['planned_volume']
        );

        echo json_encode([
            'status' => 'created',
            'data' => [
                'ref' => $session->getRef(),
                'title' => $session->getTitle(),
                'state' => $session->getState()->value
            ]
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed.']);
        break;
}
