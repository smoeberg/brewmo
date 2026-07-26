<?php

/**
 * BrewMo 2.0 API - Recipe Calculations
 * Uses new Domain-Driven Design architecture
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\ValueObject\Gravity;
use BrewMo\Domain\ValueObject\Volume;

// Dolibarr environment
$res = 0;
$paths = array(
    __DIR__ . '/../../../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../main.inc.php'
);
foreach ($paths as $p) {
    if (!$res && file_exists($p)) { $res = @include $p; }
}

try {
    // Get parameters
    $og = isset($_GET['og_sg']) ? (float)$_GET['og_sg'] : null;
    $fg = isset($_GET['fg_sg']) ? (float)$_GET['fg_sg'] : null;
    $batchSize = isset($_GET['batch_size_l']) ? (float)$_GET['batch_size_l'] : null;
    $targetAbv = isset($_GET['target_abv']) ? (float)$_GET['target_abv'] : null;

    $response = [];

    // Calculate ABV if OG and FG are provided
    if ($og !== null && $fg !== null) {
        $abv = Recipe::calculateAbv($og, $fg);
        $response['abv'] = $abv;
        $response['og_sg'] = $og;
        $response['fg_sg'] = $fg;
    }

    // Calculate required FG for target ABV if OG and target ABV are provided
    if ($og !== null && $targetAbv !== null) {
        // ABV = (OG - FG) * 131.25
        // FG = OG - (ABV / 131.25)
        $requiredFg = $og - ($targetAbv / 131.25);
        $response['required_fg_for_target_abv'] = round($requiredFg, 4);
        $response['target_abv'] = $targetAbv;
    }

    // Validate gravity values if provided
    if ($og !== null) {
        try {
            $gravity = new Gravity($og);
            $response['og_plato'] = $gravity->toPlato();
        } catch (\InvalidArgumentException $e) {
            $response['error'] = 'Invalid OG value: ' . $e->getMessage();
        }
    }

    if ($fg !== null) {
        try {
            $gravity = new Gravity($fg);
            $response['fg_plato'] = $gravity->toPlato();
        } catch (\InvalidArgumentException $e) {
            $response['error'] = 'Invalid FG value: ' . $e->getMessage();
        }
    }

    // Calculate volume conversions if batch size is provided
    if ($batchSize !== null) {
        try {
            $volume = new Volume($batchSize);
            $response['batch_size_l'] = $batchSize;
            $response['batch_size_gallons'] = $volume->toGallons();
        } catch (\InvalidArgumentException $e) {
            $response['error'] = 'Invalid batch size: ' . $e->getMessage();
        }
    }

    if (empty($response)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters: og_sg and fg_sg']);
        exit;
    }

    echo json_encode($response);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
