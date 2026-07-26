<?php

namespace BrewMo\Tests\Application;

use BrewMo\Application\Service\ContainerService;
use BrewMo\Domain\Container\ContainerStatus;
use PHPUnit\Framework\TestCase;

class ContainerServiceTest extends TestCase
{
    public function testDispatchAndReturnContainers(): void
    {
        $service = new ContainerService();
        $barcodes = ['KEG-30L-0001', 'KEG-30L-0002'];

        // Dispatch
        $dispatched = $service->dispatchContainersToCustomer($barcodes, 88);
        $this->assertCount(2, $dispatched);
        $this->assertEquals(ContainerStatus::AT_CUSTOMER->value, $dispatched[0]['status']);
        $this->assertEquals(88, $dispatched[0]['thirdpartyId']);

        // Return
        $returned = $service->receiveContainerReturns($barcodes);
        $this->assertCount(2, $returned);
        $this->assertEquals(ContainerStatus::IN_BREWERY->value, $returned[0]['status']);
        $this->assertNull($returned[0]['thirdpartyId']);
    }
}
