<?php

namespace BrewMo\Application\Service;

use BrewMo\Domain\Container\ContainerStatus;

/**
 * Application Service for managing keg/cask container assignments, customer handovers, and returns.
 */
class ContainerService
{
    /**
     * Dispatch a list of container barcodes to a customer order/expedition.
     */
    public function dispatchContainersToCustomer(array $containerBarcodes, int $thirdpartyId): array
    {
        $dispatched = [];
        foreach ($containerBarcodes as $barcode) {
            $dispatched[] = [
                'barcode' => $barcode,
                'status' => ContainerStatus::AT_CUSTOMER->value,
                'thirdpartyId' => $thirdpartyId,
                'dispatchedAt' => date('Y-m-d H:i:s')
            ];
        }
        return $dispatched;
    }

    /**
     * Log return of empty containers back to the brewery.
     */
    public function receiveContainerReturns(array $containerBarcodes): array
    {
        $returned = [];
        foreach ($containerBarcodes as $barcode) {
            $returned[] = [
                'barcode' => $barcode,
                'status' => ContainerStatus::IN_BREWERY->value,
                'thirdpartyId' => null,
                'returnedAt' => date('Y-m-d H:i:s')
            ];
        }
        return $returned;
    }
}
