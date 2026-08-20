<?php

/**
 * BrewMo 2.0 REST Controller for Flavor-to-Recipe Configuration & Orders.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use BrewMo\Domain\Recipe\FlavorProfile;
use BrewMo\Domain\Recipe\FlavorMapper;
use Exception;

function sendConfigError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'code' => $code, 'message' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $mapper = new FlavorMapper();

    switch ($method) {
        case 'GET':
        case 'POST':
            $input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : $_GET;

            $bitterness   = isset($input['bitterness']) ? (int)$input['bitterness'] : 5;
            $sweetness    = isset($input['sweetness']) ? (int)$input['sweetness'] : 5;
            $roastiness   = isset($input['roastiness']) ? (int)$input['roastiness'] : 1;
            $fruitiness   = isset($input['fruitiness']) ? (int)$input['fruitiness'] : 5;
            $body         = isset($input['body']) ? (int)$input['body'] : 5;
            $alcohol      = isset($input['alcohol']) ? (string)$input['alcohol'] : 'STANDARD';
            $volumeLiters = isset($input['volume_liters']) ? (float)$input['volume_liters'] : 12.0;
            $ref          = isset($input['ref']) ? (string)$input['ref'] : 'FLV-' . strtoupper(substr(uniqid(), -6));
            $title        = isset($input['title']) ? (string)$input['title'] : 'Custom Test Brew';

            $profile = new FlavorProfile($bitterness, $sweetness, $roastiness, $fruitiness, $body, $alcohol);
            $result = $mapper->generateRecipeFromFlavor($profile, $ref, $title, $volumeLiters);

            echo json_encode([
                'status' => 'success',
                'action' => 'preview',
                'flavor_profile' => $profile->toArray(),
                'style_name' => $result['style'],
                'metrics' => $result['metrics'],
                'ingredients' => $result['ingredients']
            ], JSON_PRETTY_PRINT);
            break;

        default:
            sendConfigError(405, 'Method Not Allowed. Use GET or POST.');
            break;
    }

} catch (Exception $e) {
    sendConfigError(400, $e->getMessage());
}
