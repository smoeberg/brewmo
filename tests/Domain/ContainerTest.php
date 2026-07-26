<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\Container\Container;
use BrewMo\Domain\Container\ContainerStatus;
use BrewMo\Domain\Container\ContainerType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ContainerTest extends TestCase
{
    public function testContainerCreation(): void
    {
        $container = new Container(
            null,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::AVAILABLE
        );

        $this->assertNull($container->getId());
        $this->assertEquals('KEG-001', $container->getRef());
        $this->assertEquals('Stainless Steel Keg', $container->getLabel());
        $this->assertEquals(50.0, $container->getCapacityLiters());
        $this->assertEquals(ContainerType::KEG, $container->getType());
        $this->assertEquals(ContainerStatus::AVAILABLE, $container->getStatus());
        $this->assertTrue($container->isActive());
    }

    public function testAssignToBrewSession(): void
    {
        $container = new Container(
            1,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::AVAILABLE
        );

        $container->assignToBrewSession(100, 'BATCH-2024-001');

        $this->assertEquals(100, $container->getCurrentBrewSessionId());
        $this->assertEquals('BATCH-2024-001', $container->getCurrentBatchNumber());
        $this->assertEquals(ContainerStatus::IN_USE, $container->getStatus());
    }

    public function testReleaseContainer(): void
    {
        $container = new Container(
            1,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::IN_USE,
            100,
            'BATCH-2024-001'
        );

        $container->release();

        $this->assertNull($container->getCurrentBrewSessionId());
        $this->assertNull($container->getCurrentBatchNumber());
        $this->assertEquals(ContainerStatus::AVAILABLE, $container->getStatus());
    }

    public function testMarkForMaintenance(): void
    {
        $container = new Container(
            1,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::AVAILABLE
        );

        $container->markForMaintenance();

        $this->assertEquals(ContainerStatus::MAINTENANCE, $container->getStatus());
    }

    public function testMarkAsLost(): void
    {
        $container = new Container(
            1,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::AVAILABLE
        );

        $container->markAsLost();

        $this->assertEquals(ContainerStatus::LOST, $container->getStatus());
        $this->assertFalse($container->isActive());
    }

    public function testUpdateLocation(): void
    {
        $container = new Container(
            1,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::AVAILABLE,
            null,
            null,
            'Warehouse A'
        );

        $container->updateLocation('Warehouse B');

        $this->assertEquals('Warehouse B', $container->getLocation());
    }

    public function testDeactivateContainer(): void
    {
        $container = new Container(
            1,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::AVAILABLE
        );

        $container->deactivate();

        $this->assertFalse($container->isActive());
    }

    public function testActivateContainer(): void
    {
        $container = new Container(
            1,
            'KEG-001',
            'Stainless Steel Keg',
            50.0,
            ContainerType::KEG,
            ContainerStatus::AVAILABLE,
            null,
            null,
            null,
            false
        );

        $container->activate();

        $this->assertTrue($container->isActive());
    }

    public function testContainerTypes(): void
    {
        $this->assertEquals('KEG', ContainerType::KEG->value);
        $this->assertEquals('KEYKEG', ContainerType::KEYKEG->value);
        $this->assertEquals('CASK', ContainerType::CASK->value);
        $this->assertEquals('TANK', ContainerType::TANK->value);
        $this->assertEquals('BOTTLE', ContainerType::BOTTLE->value);
        $this->assertEquals('CAN', ContainerType::CAN->value);
        $this->assertEquals('OTHER', ContainerType::OTHER->value);
    }

    public function testContainerStatuses(): void
    {
        $this->assertEquals('AVAILABLE', ContainerStatus::AVAILABLE->value);
        $this->assertEquals('IN_USE', ContainerStatus::IN_USE->value);
        $this->assertEquals('FULL', ContainerStatus::FULL->value);
        $this->assertEquals('EMPTY', ContainerStatus::EMPTY->value);
        $this->assertEquals('MAINTENANCE', ContainerStatus::MAINTENANCE->value);
        $this->assertEquals('LOST', ContainerStatus::LOST->value);
        $this->assertEquals('CLEANING', ContainerStatus::CLEANING->value);
        $this->assertEquals('IN_BREWERY', ContainerStatus::IN_BREWERY->value);
        $this->assertEquals('AT_CUSTOMER', ContainerStatus::AT_CUSTOMER->value);
        $this->assertEquals('IN_TRANSIT', ContainerStatus::IN_TRANSIT->value);
        $this->assertEquals('RETIRED', ContainerStatus::RETIRED->value);
    }
}
