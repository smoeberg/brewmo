<?php

namespace BrewMo\Infrastructure\Repository;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;
use BrewMo\Domain\Repository\BrewSessionRepositoryInterface;
use RuntimeException;

/**
 * Dolibarr Persistence implementation of BrewSessionRepositoryInterface.
 * Bridges DDD entities with Dolibarr SQL database.
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
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE rowid = " . (int)$id;

        $res = $this->db->query($sql);
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
        $sql .= "FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE ref = '" . $this->db->escape($ref) . "'";

        $res = $this->db->query($sql);
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

    public function save(BrewSession $session): BrewSession
    {
        if ($session->getId() === null) {
            // INSERT
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_session_v2 (ref, title, fk_recipe, state, planned_volume_liters, fk_vessel, lot_number) VALUES (";
            $sql .= "'" . $this->db->escape($session->getRef()) . "', ";
            $sql .= "'" . $this->db->escape($session->getTitle()) . "', ";
            $sql .= (int)$session->getRecipeId() . ", ";
            $sql .= "'" . $this->db->escape($session->getState()->value) . "', ";
            $sql .= (float)$session->getPlannedVolumeLiters() . ", ";
            $sql .= ($session->getVesselId() ? (int)$session->getVesselId() : "NULL") . ", ";
            $sql .= ($session->getLotNumber() ? "'" . $this->db->escape($session->getLotNumber()) . "'" : "NULL");
            $sql .= ")";

            $res = $this->db->query($sql);
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
            $sql .= "ref = '" . $this->db->escape($session->getRef()) . "', ";
            $sql .= "title = '" . $this->db->escape($session->getTitle()) . "', ";
            $sql .= "fk_recipe = " . (int)$session->getRecipeId() . ", ";
            $sql .= "state = '" . $this->db->escape($session->getState()->value) . "', ";
            $sql .= "planned_volume_liters = " . (float)$session->getPlannedVolumeLiters() . ", ";
            $sql .= "fk_vessel = " . ($session->getVesselId() ? (int)$session->getVesselId() : "NULL") . ", ";
            $sql .= "lot_number = " . ($session->getLotNumber() ? "'" . $this->db->escape($session->getLotNumber()) . "'" : "NULL") . " ";
            $sql .= "WHERE rowid = " . (int)$session->getId();

            $res = $this->db->query($sql);
            if (!$res) {
                throw new RuntimeException("Database error updating BrewSession: " . $this->db->lasterror());
            }

            return $session;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_session_v2 WHERE rowid = " . (int)$id;
        return (bool)$this->db->query($sql);
    }
}
