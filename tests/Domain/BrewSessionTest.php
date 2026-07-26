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
    }

    public function testInvalidStateTransitionThrowsException(): void
    {
        $session = new BrewSession(1, 'BREW-2026-001', 'IPA Batch 1', 101, 1000.0, BrewSessionState::DRAFT);
        
        $this->expectException(RuntimeException::class);
        // Direct transition from DRAFT to MASHING (skipping PLANNED) is invalid
        $session->transitionTo(BrewSessionState::MASHING);
    }
}
