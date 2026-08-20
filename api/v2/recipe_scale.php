<?php

/**
 * BrewMo 2.0 REST Controller for Scaling Recipes (e.g. 12L pilot -> 1000L commercial).
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\Recipe\RecipeIngredient;
use BrewMo\Domain\Recipe\IngredientType;
use BrewMo\Domain\Recipe\FlavorMapper;
use Exception;

function sendScaleError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'code' => $code, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendScaleError(405, 'Method Not Allowed. Use POST.');
}

try {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!$body || !isset($body['target_volume_liters'])) {
        sendScaleError(400, 'Missing required parameter: target_volume_liters');
    }

    $targetVolume = (float)$body['target_volume_liters'];
    $sourceVolume = (float)($body['source_volume_liters'] ?? 12.0);
    $og = (float)($body['og'] ?? 1.052);
    $fg = (float)($body['fg'] ?? 1.012);
    $ibu = (float)($body['ibu'] ?? 35.0);
    $ebc = (float)($body['ebc'] ?? 15.0);
    $ref = (string)($body['ref'] ?? 'REC-CUSTOM');
    $title = (string)($body['title'] ?? 'Custom Beer');

    // Build source recipe ingredients if passed, or mock base sample
    $sourceIngredients = [];
    if (!empty($body['ingredients']) && is_array($body['ingredients'])) {
        foreach ($body['ingredients'] as $ing) {
            $sourceIngredients[] = new RecipeIngredient(
                (int)$ing['product_id'],
                IngredientType::from($ing['type']),
                (float)$ing['amount'],
                (string)$ing['unit'],
                $ing['stage'] ?? null
            );
        }
    } else {
        // Default sample bill for 12L
        $sourceIngredients[] = new RecipeIngredient(101, IngredientType::MALT, 2.5, 'kg', 'Mash');
        $sourceIngredients[] = new RecipeIngredient(201, IngredientType::HOPS, 15.0, 'g', 'Boil 60m');
    }

    $sourceRecipe = new Recipe(null, $ref, $title, $sourceVolume, $og, $fg, $ibu, $ebc, $sourceIngredients);
    
    $mapper = new FlavorMapper();
    $scaled = $mapper->scaleRecipe($sourceRecipe, $targetVolume);

    echo json_encode([
        'status' => 'success',
        'action' => 'scale',
        'source' => [
            'ref' => $sourceRecipe->getRef(),
            'batch_volume_liters' => $sourceRecipe->getTargetBatchSizeLiters()
        ],
        'scaled' => [
            'ref' => $scaled->getRef(),
            'title' => $scaled->getTitle(),
            'batch_volume_liters' => $scaled->getTargetBatchSizeLiters(),
            'abv' => $scaled->getEstimatedAbv(),
            'ibu' => $scaled->getEstimatedIbu(),
            'ebc' => $scaled->getEstimatedEbc(),
            'ingredients' => array_map(function(RecipeIngredient $i) {
                return [
                    'product_id' => $i->getProductId(),
                    'type' => $i->getType()->value,
                    'amount' => $i->getAmount(),
                    'unit' => $i->getUnit(),
                    'stage' => $i->getAdditionStage()
                ];
            }, $scaled->getIngredients())
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    sendScaleError(500, 'Scale calculation error: ' . $e->getMessage());
}
