<?php

namespace BrewMo\Tests\Infrastructure;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;
use BrewMo\Infrastructure\Repository\DolibarrBrewSessionRepository;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for DolibarrBrewSessionRepository
 * Note: These tests require a Dolibarr database connection
 */
class DolibarrBrewSessionRepositoryTest extends TestCase
{
    private static ?object $db = null;
    private static ?DolibarrBrewSessionRepository $repository = null;

    public static function setUpBeforeClass(): void
    {
        // Skip if Dolibarr is not available
        if (!defined('MAIN_DB_PREFIX')) {
            self::markTestSkipped('Dolibarr database not available');
        }

        global $db;
        self::$db = $db;
        self::$repository = new DolibarrBrewSessionRepository($db);
    }

    public function testFindByIdNotFound(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        $result = self::$repository->findById(999999);
        $this->assertNull($result);
    }

    public function testSaveAndFindById(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        // Create a new brew session
        $session = new BrewSession(
            null,
            'TEST-BREW-' . time(),
            'Test Brew Session',
            1, // fk_recipe
            1000.0,
            BrewSessionState::DRAFT,
            null,
            null,
            null,
            null
        );

        // Save it
        $savedSession = self::$repository->save($session);
        $this->assertNotNull($savedSession->getId());
        $this->assertGreaterThan(0, $savedSession->getId());

        // Find it by ID
        $foundSession = self::$repository->findById($savedSession->getId());
        $this->assertNotNull($foundSession);
        $this->assertEquals($savedSession->getRef(), $foundSession->getRef());
        $this->assertEquals($savedSession->getTitle(), $foundSession->getTitle());
        $this->assertEquals($savedSession->getRecipeId(), $foundSession->getRecipeId());
        $this->assertEquals($savedSession->getPlannedVolumeLiters(), $foundSession->getPlannedVolumeLiters());
        $this->assertEquals(BrewSessionState::DRAFT, $foundSession->getState());

        // Clean up
        self::$repository->delete($savedSession->getId());
    }

    public function testSaveAndFindByRef(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        $ref = 'TEST-BREW-REF-' . time();
        $session = new BrewSession(
            null,
            $ref,
            'Test Brew Session by Ref',
            1,
            500.0,
            BrewSessionState::PLANNED
        );

        $savedSession = self::$repository->save($session);
        $this->assertNotNull($savedSession->getId());

        // Find it by reference
        $foundSession = self::$repository->findByRef($ref);
        $this->assertNotNull($foundSession);
        $this->assertEquals($ref, $foundSession->getRef());
        $this->assertEquals(BrewSessionState::PLANNED, $foundSession->getState());

        // Clean up
        self::$repository->delete($foundSession->getId());
    }

    public function testUpdateBrewSession(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        // Create initial session
        $session = new BrewSession(
            null,
            'TEST-BREW-UPDATE-' . time(),
            'Initial Title',
            1,
            1000.0,
            BrewSessionState::DRAFT
        );

        $savedSession = self::$repository->save($session);
        $id = $savedSession->getId();

        // Update the session
        $updatedSession = new BrewSession(
            $id,
            $savedSession->getRef(),
            'Updated Title',
            2, // Different recipe
            2000.0, // Different volume
            BrewSessionState::PLANNED,
            5, // vessel_id
            'LOT-001'
        );

        $result = self::$repository->save($updatedSession);
        $this->assertEquals($id, $result->getId());
        $this->assertEquals('Updated Title', $result->getTitle());
        $this->assertEquals(2, $result->getRecipeId());
        $this->assertEquals(2000.0, $result->getPlannedVolumeLiters());
        $this->assertEquals(BrewSessionState::PLANNED, $result->getState());
        $this->assertEquals(5, $result->getVesselId());
        $this->assertEquals('LOT-001', $result->getLotNumber());

        // Clean up
        self::$repository->delete($id);
    }

    public function testFindAll(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        // Create a few test sessions
        $sessions = [];
        for ($i = 0; $i < 3; $i++) {
            $session = new BrewSession(
                null,
                'TEST-BREW-ALL-' . time() . '-' . $i,
                'Test Session ' . $i,
                1,
                1000.0,
                BrewSessionState::DRAFT
            );
            $saved = self::$repository->save($session);
            $sessions[] = $saved->getId();
        }

        // Find all sessions
        $allSessions = self::$repository->findAll();
        $this->assertIsArray($allSessions);
        $this->assertGreaterThanOrEqual(3, count($allSessions));

        // Clean up
        foreach ($sessions as $id) {
            self::$repository->delete($id);
        }
    }

    public function testFindByRecipeId(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        $recipeId = 1;

        // Create sessions with this recipe
        $session1 = new BrewSession(
            null,
            'TEST-BREW-RECIPE-' . time() . '-1',
            'Test Session 1',
            $recipeId,
            1000.0,
            BrewSessionState::DRAFT
        );
        $saved1 = self::$repository->save($session1);

        $session2 = new BrewSession(
            null,
            'TEST-BREW-RECIPE-' . time() . '-2',
            'Test Session 2',
            $recipeId,
            1500.0,
            BrewSessionState::PLANNED
        );
        $saved2 = self::$repository->save($session2);

        // Find by recipe ID
        $foundSessions = self::$repository->findByRecipeId($recipeId);
        $this->assertIsArray($foundSessions);
        $this->assertGreaterThanOrEqual(2, count($foundSessions));

        // Verify all found sessions have the correct recipe ID
        foreach ($foundSessions as $session) {
            $this->assertEquals($recipeId, $session->getRecipeId());
        }

        // Clean up
        self::$repository->delete($saved1->getId());
        self::$repository->delete($saved2->getId());
    }

    public function testFindByState(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        // Create sessions with different states
        $draftSession = new BrewSession(
            null,
            'TEST-BREW-DRAFT-' . time(),
            'Draft Session',
            1,
            1000.0,
            BrewSessionState::DRAFT
        );
        $savedDraft = self::$repository->save($draftSession);

        $plannedSession = new BrewSession(
            null,
            'TEST-BREW-PLANNED-' . time(),
            'Planned Session',
            1,
            1000.0,
            BrewSessionState::PLANNED
        );
        $savedPlanned = self::$repository->save($plannedSession);

        // Find by state
        $draftSessions = self::$repository->findByState(BrewSessionState::DRAFT);
        $this->assertIsArray($draftSessions);
        $this->assertGreaterThanOrEqual(1, count($draftSessions));

        foreach ($draftSessions as $session) {
            $this->assertEquals(BrewSessionState::DRAFT, $session->getState());
        }

        $plannedSessions = self::$repository->findByState(BrewSessionState::PLANNED);
        $this->assertIsArray($plannedSessions);
        $this->assertGreaterThanOrEqual(1, count($plannedSessions));

        foreach ($plannedSessions as $session) {
            $this->assertEquals(BrewSessionState::PLANNED, $session->getState());
        }

        // Clean up
        self::$repository->delete($savedDraft->getId());
        self::$repository->delete($savedPlanned->getId());
    }

    public function testDelete(): void
    {
        if (self::$repository === null) {
            $this->markTestSkipped('Repository not initialized');
        }

        // Create a session
        $session = new BrewSession(
            null,
            'TEST-BREW-DELETE-' . time(),
            'Session to Delete',
            1,
            1000.0,
            BrewSessionState::DRAFT
        );

        $savedSession = self::$repository->save($session);
        $id = $savedSession->getId();

        // Verify it exists
        $found = self::$repository->findById($id);
        $this->assertNotNull($found);

        // Delete it
        $result = self::$repository->delete($id);
        $this->assertTrue($result);

        // Verify it's gone
        $found = self::$repository->findById($id);
        $this->assertNull($found);
    }
}
