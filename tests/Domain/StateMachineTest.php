<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\BrewSession\BrewSessionState;
use PHPUnit\Framework\TestCase;

class StateMachineTest extends TestCase
{
    public function testValidSequentialTransitions(): void
    {
        $state = BrewSessionState::DRAFT;
        
        $this->assertTrue($state->canTransitionTo(BrewSessionState::PLANNED));
        $state = BrewSessionState::PLANNED;

        $this->assertTrue($state->canTransitionTo(BrewSessionState::MASHING));
        $state = BrewSessionState::MASHING;

        $this->assertTrue($state->canTransitionTo(BrewSessionState::BOILING));
        $state = BrewSessionState::BOILING;

        $this->assertTrue($state->canTransitionTo(BrewSessionState::FERMENTING));
        $state = BrewSessionState::FERMENTING;

        $this->assertTrue($state->canTransitionTo(BrewSessionState::CONDITIONING));
        $state = BrewSessionState::CONDITIONING;

        $this->assertTrue($state->canTransitionTo(BrewSessionState::PACKAGING));
        $state = BrewSessionState::PACKAGING;

        $this->assertTrue($state->canTransitionTo(BrewSessionState::COMPLETED));
    }

    public function testInvalidTransitionsAreBlocked(): void
    {
        $state = BrewSessionState::DRAFT;

        // Cannot jump directly from DRAFT to FERMENTING or COMPLETED
        $this->assertFalse($state->canTransitionTo(BrewSessionState::FERMENTING));
        $this->assertFalse($state->canTransitionTo(BrewSessionState::COMPLETED));

        $completedState = BrewSessionState::COMPLETED;
        // COMPLETED is a terminal state
        $this->assertFalse($completedState->canTransitionTo(BrewSessionState::MASHING));
    }
}
