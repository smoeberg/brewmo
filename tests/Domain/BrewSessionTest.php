<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BrewSessionTest extends TestCase
{
    public function testValidStateTransition(): void
    {
        $session = new BrewSession(1, 'BREW-2026-001', 'IPA Batch 1', 101, 1000.0, BrewSessionState::DRAFT);
        $this->assertEquals(BrewSessionState::DRAFT, $session->getState());

        $session->transitionTo(BrewSessionState::PLANNED);
        $this->assertEquals(BrewSessionState::PLANNED, $session->getState());

        $session->transitionTo(BrewSessionState::MASHING);
        $this->assertEquals(BrewSessionState::MASHING, $session->getState());

        $session->transitionTo(BrewSessionState::BOILING);
        $this->assertEquals(BrewSessionState::BOILING, $session->getState());

        $session->transitionTo(BrewSessionState::FERMENTING);
        $this->assertEquals(BrewSessionState::FERMENTING, $session->getState());

        $session->transitionTo(BrewSessionState::PACKAGING);
        $this->assertEquals(BrewSessionState::PACKAGING, $session->getState());

        $session->transitionTo(BrewSessionState::COMPLETED);
        $this->assertEquals(BrewSessionState::COMPLETED, $session->getState());
    }

    public function testInvalidStateTransitionThrowsException(): void
    {
        $session = new BrewSession(1, 'BREW-2026-001', 'IPA Batch 1', 101, 1000.0, BrewSessionState::DRAFT);
        
        $this->expectException(RuntimeException::class);
        // Direct transition from DRAFT to MASHING (skipping PLANNED) is invalid
        $session->transitionTo(BrewSessionState::MASHING);
    }

    public function testCannotTransitionFromCompleted(): void
    {
        $session = new BrewSession(1, 'BREW-2026-002', 'Completed Batch', 101, 1000.0, BrewSessionState::COMPLETED);
        
        $this->expectException(RuntimeException::class);
        $session->transitionTo(BrewSessionState::DRAFT);
    }

    public function testCannotTransitionFromCancelled(): void
    {
        $session = new BrewSession(1, 'BREW-2026-003', 'Cancelled Batch', 101, 1000.0, BrewSessionState::CANCELLED);
        
        $this->expectException(RuntimeException::class);
        $session->transitionTo(BrewSessionState::PLANNED);
    }

    public function testCancelFromAnyState(): void
    {
        // Test cancelling from DRAFT
        $session = new BrewSession(1, 'BREW-2026-004', 'Draft Batch', 101, 1000.0, BrewSessionState::DRAFT);
        $session->transitionTo(BrewSessionState::CANCELLED);
        $this->assertEquals(BrewSessionState::CANCELLED, $session->getState());

        // Test cancelling from PLANNED
        $session = new BrewSession(2, 'BREW-2026-005', 'Planned Batch', 101, 1000.0, BrewSessionState::PLANNED);
        $session->transitionTo(BrewSessionState::CANCELLED);
        $this->assertEquals(BrewSessionState::CANCELLED, $session->getState());

        // Test cancelling from MASHING
        $session = new BrewSession(3, 'BREW-2026-006', 'Mashing Batch', 101, 1000.0, BrewSessionState::MASHING);
        $session->transitionTo(BrewSessionState::CANCELLED);
        $this->assertEquals(BrewSessionState::CANCELLED, $session->getState());
    }

    public function testAssignVessel(): void
    {
        $session = new BrewSession(1, 'BREW-2026-007', 'Test Batch', 101, 1000.0, BrewSessionState::DRAFT);
        
        $this->assertNull($session->getVesselId());
        
        $session->assignVessel(5);
        $this->assertEquals(5, $session->getVesselId());
    }

    public function testSetLotNumber(): void
    {
        $session = new BrewSession(1, 'BREW-2026-008', 'Test Batch', 101, 1000.0, BrewSessionState::DRAFT);
        
        $this->assertNull($session->getLotNumber());
        
        $session->setLotNumber('LOT-2024-001');
        $this->assertEquals('LOT-2024-001', $session->getLotNumber());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $session = new BrewSession(
            123,
            'BREW-2026-009',
            'Test IPA',
            456,
            1500.5,
            BrewSessionState::FERMENTING,
            789,
            'LOT-2024-002',
            '2024-01-15 10:00:00',
            '2024-01-20 14:00:00'
        );

        $this->assertEquals(123, $session->getId());
        $this->assertEquals('BREW-2026-009', $session->getRef());
        $this->assertEquals('Test IPA', $session->getTitle());
        $this->assertEquals(456, $session->getRecipeId());
        $this->assertEquals(1500.5, $session->getPlannedVolumeLiters());
        $this->assertEquals(BrewSessionState::FERMENTING, $session->getState());
        $this->assertEquals(789, $session->getVesselId());
        $this->assertEquals('LOT-2024-002', $session->getLotNumber());
        $this->assertEquals('2024-01-15 10:00:00', $session->getStartDate());
        $this->assertEquals('2024-01-20 14:00:00', $session->getEndDate());
    }

    public function testStateMachineAllValidTransitions(): void
    {
        // Test all valid transitions
        $transitions = [
            [BrewSessionState::DRAFT, BrewSessionState::PLANNED],
            [BrewSessionState::DRAFT, BrewSessionState::CANCELLED],
            [BrewSessionState::PLANNED, BrewSessionState::MASHING],
            [BrewSessionState::PLANNED, BrewSessionState::CANCELLED],
            [BrewSessionState::MASHING, BrewSessionState::BOILING],
            [BrewSessionState::MASHING, BrewSessionState::CANCELLED],
            [BrewSessionState::BOILING, BrewSessionState::FERMENTING],
            [BrewSessionState::BOILING, BrewSessionState::CANCELLED],
            [BrewSessionState::FERMENTING, BrewSessionState::PACKAGING],
            [BrewSessionState::FERMENTING, BrewSessionState::CANCELLED],
            [BrewSessionState::PACKAGING, BrewSessionState::COMPLETED],
            [BrewSessionState::PACKAGING, BrewSessionState::CANCELLED],
        ];

        foreach ($transitions as [$from, $to]) {
            $session = new BrewSession(1, 'TEST', 'Test', 1, 1000.0, $from);
            $session->transitionTo($to);
            $this->assertEquals($to, $session->getState(), "Failed transition from {$from->value} to {$to->value}");
        }
    }

    public function testStateMachineAllInvalidTransitions(): void
    {
        // Test all invalid transitions
        $invalidTransitions = [
            [BrewSessionState::DRAFT, BrewSessionState::MASHING],
            [BrewSessionState::DRAFT, BrewSessionState::BOILING],
            [BrewSessionState::DRAFT, BrewSessionState::FERMENTING],
            [BrewSessionState::DRAFT, BrewSessionState::PACKAGING],
            [BrewSessionState::DRAFT, BrewSessionState::COMPLETED],
            [BrewSessionState::PLANNED, BrewSessionState::BOILING],
            [BrewSessionState::PLANNED, BrewSessionState::FERMENTING],
            [BrewSessionState::PLANNED, BrewSessionState::PACKAGING],
            [BrewSessionState::PLANNED, BrewSessionState::COMPLETED],
            [BrewSessionState::MASHING, BrewSessionState::FERMENTING],
            [BrewSessionState::MASHING, BrewSessionState::PACKAGING],
            [BrewSessionState::MASHING, BrewSessionState::COMPLETED],
            [BrewSessionState::BOILING, BrewSessionState::PACKAGING],
            [BrewSessionState::BOILING, BrewSessionState::COMPLETED],
            [BrewSessionState::FERMENTING, BrewSessionState::COMPLETED],
            [BrewSessionState::COMPLETED, BrewSessionState::DRAFT],
            [BrewSessionState::COMPLETED, BrewSessionState::PLANNED],
            [BrewSessionState::COMPLETED, BrewSessionState::MASHING],
            [BrewSessionState::CANCELLED, BrewSessionState::DRAFT],
            [BrewSessionState::CANCELLED, BrewSessionState::PLANNED],
        ];

        foreach ($invalidTransitions as [$from, $to]) {
            $session = new BrewSession(1, 'TEST', 'Test', 1, 1000.0, $from);
            
            $this->expectException(RuntimeException::class);
            $session->transitionTo($to);
        }
    }
}
