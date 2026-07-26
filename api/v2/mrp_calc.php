<?php

/**
 * BrewMo 2.0 API - Material Requirements Planning (MRP) Calculations
 * Uses new Domain-Driven Design architecture
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use BrewMo\Application\Service\MRPService;
use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\Recipe\RecipeIngredient;
use BrewMo\Domain\Recipe\IngredientType;

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
    $recipeId = isset($_GET['recipe_id']) ? (int)$_GET['recipe_id'] : null;
    $batchSize = isset($_GET['batch_size_l']) ? (float)$_GET['batch_size_l'] : null;
    $batchCount = isset($_GET['batches']) ? (float)$_GET['batches'] : null;

    if ($recipeId === null || $recipeId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid recipe_id']);
        exit;
    }

    // If batch_count is provided, calculate based on that
    // Otherwise use batch_size_l
    if ($batchCount !== null && $batchCount > 0) {
        $targetVolume = $batchCount;
    } elseif ($batchSize !== null && $batchSize > 0) {
        $targetVolume = $batchSize;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing batches or batch_size_l parameter']);
        exit;
    }

    // Load recipe from database
    global $db;
    
    $sql = "SELECT rowid, ref, label, batch_volume_l, og_sg, fg_sg, ibu, color_ebc";
    $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipes WHERE rowid = ?";
    
    $res = $db->query($sql, [(int)$recipeId]);
    if (!$res || $db->num_rows($res) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Recipe not found']);
        exit;
    }

    $recipeData = $db->fetch_object($res);
    
    // Load ingredients
    $ingredients = [];
    
    // Load malts
    $sql = "SELECT fk_product, qty_kg FROM " . MAIN_DB_PREFIX . "brew_recipe_malt WHERE fk_recipe = ?";
    $res = $db->query($sql, [(int)$recipeId]);
    while ($obj = $db->fetch_object($res)) {
        $ingredients[] = new RecipeIngredient(
            (int)$obj->fk_product,
            IngredientType::MALT,
            (float)$obj->qty_kg,
            'kg'
        );
    }

    // Load hops
    $sql = "SELECT fk_product, qty_g FROM " . MAIN_DB_PREFIX . "brew_recipe_hop WHERE fk_recipe = ?";
    $res = $db->query($sql, [(int)$recipeId]);
    while ($obj = $db->fetch_object($res)) {
        $ingredients[] = new RecipeIngredient(
            (int)$obj->fk_product,
            IngredientType::HOPS,
            (float)$obj->qty_g,
            'g'
        );
    }

    // Load yeast
    $sql = "SELECT fk_product, qty_g FROM " . MAIN_DB_PREFIX . "brew_recipe_yeast WHERE fk_recipe = ?";
    $res = $db->query($sql, [(int)$recipeId]);
    while ($obj = $db->fetch_object($res)) {
        $ingredients[] = new RecipeIngredient(
            (int)$obj->fk_product,
            IngredientType::YEAST,
            (float)$obj->qty_g,
            'g'
        );
    }

    // Create Recipe object
    $recipe = new Recipe(
        (int)$recipeData->rowid,
        $recipeData->ref,
        $recipeData->label,
        (float)$recipeData->batch_volume_l,
        (float)$recipeData->og_sg,
        (float)$recipeData->fg_sg,
        (float)$recipeData->ibu,
        (float)$recipeData->color_ebc,
        $ingredients
    );

    // Calculate MRP
    $mrpService = new MRPService();
    $materialRequirements = $mrpService->calculateMaterialRequirements($recipe, $targetVolume);

    // Get stock levels for each ingredient
    $result = [
        'recipe_id' => $recipeId,
        'recipe_ref' => $recipe->getRef(),
        'target_volume_l' => $targetVolume,
        'base_volume_l' => $recipe->getTargetBatchSizeLiters(),
        'scaling_factor' => $targetVolume / $recipe->getTargetBatchSizeLiters(),
        'ingredients' => []
    ];

    foreach ($materialRequirements as $productId => $requirement) {
        // Get stock level
        $sql = "SELECT SUM(reel) as stock FROM " . MAIN_DB_PREFIX . "product_stock WHERE fk_product = ?";
        $res = $db->query($sql, [(int)$productId]);
        $stock = 0.0;
        if ($res && ($obj = $db->fetch_object($res))) {
            $stock = (float)$obj->stock;
        }

        // Get product info
        $sql = "SELECT ref, label FROM " . MAIN_DB_PREFIX . "product WHERE rowid = ?";
        $res = $db->query($sql, [(int)$productId]);
        $productRef = 'UNKNOWN';
        $productLabel = 'Unknown';
        if ($res && ($obj = $db->fetch_object($res))) {
            $productRef = $obj->ref;
            $productLabel = $obj->label;
        }

        $result['ingredients'][] = [
            'product_id' => $productId,
            'product_ref' => $productRef,
            'product_label' => $productLabel,
            'type' => $requirement['type'],
            'required_amount' => $requirement['requiredAmount'],
            'unit' => $requirement['unit'],
            'stock' => $stock,
            'to_order' => max(0, $requirement['requiredAmount'] - $stock)
        ];
    }

    echo json_encode($result);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
