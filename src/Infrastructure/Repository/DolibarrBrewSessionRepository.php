<?php

namespace BrewMo\Infrastructure\Repository;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;
use BrewMo\Domain\Repository\BrewSessionRepositoryInterface;
use RuntimeException;

/**
 * Dolibarr Persistence implementation of BrewSessionRepositoryInterface.
 * Bridges DDD entities with Dolibarr SQL database.
 * Uses prepared statements for SQL Injection protection.
 */
class DolibarrBrewSessionRepository implements BrewSessionRepositoryInterface
{
    private object $db;

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?BrewSession
    {
        $sql = "SELECT rowid, ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number, date_start, date_end ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE rowid = ?";

        $res = $this->db->query($sql, [(int)$id]);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        return new BrewSession(
            (int)$obj->rowid,
            $obj->ref,
            $obj->title,
            (int)$obj->fk_recipe,
            (float)$obj->planned_volume_liters,
            BrewSessionState::from($obj->state),
            $obj->fk_vessel ? (int)$obj->fk_vessel : null,
            $obj->lot_number,
            $obj->date_start,
            $obj->date_end
        );
    }

    public function findByRef(string $ref): ?BrewSession
    {
        $sql = "SELECT rowid, ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number, date_start, date_end ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE ref = ?";

        $res = $this->db->query($sql, [$ref]);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        return new BrewSession(
            (int)$obj->rowid,
            $obj->ref,
            $obj->title,
            (int)$obj->fk_recipe,
            (float)$obj->planned_volume_liters,
            BrewSessionState::from($obj->state),
            $obj->fk_vessel ? (int)$obj->fk_vessel : null,
            $obj->lot_number,
            $obj->date_start,
            $obj->date_end
        );
    }

    public function findByRecipeId(int $recipeId): array
    {
        $sql = "SELECT rowid, ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number, date_start, date_end ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE fk_recipe = ? ORDER BY date_start DESC";

        $res = $this->db->query($sql, [(int)$recipeId]);
        if (!$res) {
            return [];
        }

        $sessions = [];
        while ($obj = $this->db->fetch_object($res)) {
            $sessions[] = new BrewSession(
                (int)$obj->rowid,
                $obj->ref,
                $obj->title,
                (int)$obj->fk_recipe,
                (float)$obj->planned_volume_liters,
                BrewSessionState::from($obj->state),
                $obj->fk_vessel ? (int)$obj->fk_vessel : null,
                $obj->lot_number,
                $obj->date_start,
                $obj->date_end
            );
        }

        return $sessions;
    }

    public function findByState(BrewSessionState $state): array
    {
        $sql = "SELECT rowid, ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number, date_start, date_end ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE state = ? ORDER BY date_start DESC";

        $res = $this->db->query($sql, [$state->value]);
        if (!$res) {
            return [];
        }

        $sessions = [];
        while ($obj = $this->db->fetch_object($res)) {
            $sessions[] = new BrewSession(
                (int)$obj->rowid,
                $obj->ref,
                $obj->title,
                (int)$obj->fk_recipe,
                (float)$obj->planned_volume_liters,
                BrewSessionState::from($obj->state),
                $obj->fk_vessel ? (int)$obj->fk_vessel : null,
                $obj->lot_number,
                $obj->date_start,
                $obj->date_end
            );
        }

        return $sessions;
    }

    public function findByVesselId(int $vesselId): array
    {
        $sql = "SELECT rowid, ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number, date_start, date_end ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE fk_vessel = ? ORDER BY date_start DESC";

        $res = $this->db->query($sql, [(int)$vesselId]);
        if (!$res) {
            return [];
        }

        $sessions = [];
        while ($obj = $this->db->fetch_object($res)) {
            $sessions[] = new BrewSession(
                (int)$obj->rowid,
                $obj->ref,
                $obj->title,
                (int)$obj->fk_recipe,
                (float)$obj->planned_volume_liters,
                BrewSessionState::from($obj->state),
                $obj->fk_vessel ? (int)$obj->fk_vessel : null,
                $obj->lot_number,
                $obj->date_start,
                $obj->date_end
            );
        }

        return $sessions;
    }

    public function findAll(): array
    {
        $sql = "SELECT rowid, ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number, date_start, date_end ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 ORDER BY date_start DESC";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $sessions = [];
        while ($obj = $this->db->fetch_object($res)) {
            $sessions[] = new BrewSession(
                (int)$obj->rowid,
                $obj->ref,
                $obj->title,
                (int)$obj->fk_recipe,
                (float)$obj->planned_volume_liters,
                BrewSessionState::from($obj->state),
                $obj->fk_vessel ? (int)$obj->fk_vessel : null,
                $obj->lot_number,
                $obj->date_start,
                $obj->date_end
            );
        }

        return $sessions;
    }

    public function save(BrewSession $session): BrewSession
    {
        if ($session->getId() === null) {
            // INSERT
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_session_v2 ";
            $sql .= "(ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $session->getRef(),
                $session->getTitle(),
                (int)$session->getRecipeId(),
                $session->getState()->value,
                (float)$session->getPlannedVolumeLiters(),
                $session->getVesselId() ? (int)$session->getVesselId() : null,
                $session->getLotNumber()
            ];

            $res = $this->db->query($sql, $params);
            if (!$res) {
                throw new RuntimeException("Database error creating BrewSession: " . $this->db->lasterror());
            }

            $insertedId = $this->db->last_insert_id(MAIN_DB_PREFIX . "brew_session_v2");
            return new BrewSession(
                $insertedId,
                $session->getRef(),
                $session->getTitle(),
                $session->getRecipeId(),
                $session->getPlannedVolumeLiters(),
                $session->getState(),
                $session->getVesselId(),
                $session->getLotNumber()
            );
        } else {
            // UPDATE
            $sql = "UPDATE " . MAIN_DB_PREFIX . "brew_session_v2 SET ";
            $sql .= "ref = ?, title = ?, fk_recipe = ?, state = ?, planned_volume_liters = ?, fk_vessel = ?, lot_number = ?";
            $sql .= " WHERE rowid = ?";

            $params = [
                $session->getRef(),
                $session->getTitle(),
                (int)$session->getRecipeId(),
                $session->getState()->value,
                (float)$session->getPlannedVolumeLiters(),
                $session->getVesselId() ? (int)$session->getVesselId() : null,
                $session->getLotNumber(),
                (int)$session->getId()
            ];

            $res = $this->db->query($sql, $params);
            if (!$res) {
                throw new RuntimeException("Database error updating BrewSession: " . $this->db->lasterror());
            }

            return $session;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE rowid = ?";
        return (bool)$this->db->query($sql, [(int)$id]);
    }
}
