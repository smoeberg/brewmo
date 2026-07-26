<?php

namespace BrewMo\Infrastructure\Repository;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;
use BrewMo\Domain\BrewSession\BrewSessionRepositoryInterface;
use RuntimeException;
use InvalidArgumentException;

class DolibarrBrewSessionRepository implements BrewSessionRepositoryInterface
{
    private object $db;

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?BrewSession
    {
        $table = MAIN_DB_PREFIX . "brew_session_v2";
        $sql = "SELECT rowid, ref, title, fk_recipe, fk_vessel, state, planned_volume_liters, lot_number ";
        $sql .= "FROM " . $table . " WHERE rowid = " . $id;

        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            return new BrewSession(
                (int) $obj->rowid,
                $obj->ref,
                $obj->title,
                BrewSessionState::from($obj->state),
                (float) $obj->planned_volume_liters,
                $obj->lot_number,
                $obj->fk_recipe ? (int) $obj->fk_recipe : null,
                $obj->fk_vessel ? (int) $obj->fk_vessel : null
            );
        }

        return null;
    }

    public function save(BrewSession $session): void
    {
        $table = MAIN_DB_PREFIX . "brew_session_v2";
        $this->db->begin();

        try {
            if ($session->getId() === null) {
                // Insert
                $sql = "INSERT INTO " . $table . " (entity, ref, title, fk_recipe, fk_vessel, state, planned_volume_liters, lot_number, datec) ";
                $sql .= "VALUES (";
                $sql .= "1, ";
                $sql .= "'" . $this->db->escape($session->getRef()) . "', ";
                $sql .= "'" . $this->db->escape($session->getTitle()) . "', ";
                $sql .= ($session->getRecipeId() ? $session->getRecipeId() : "NULL") . ", ";
                $sql .= ($session->getVesselId() ? $session->getVesselId() : "NULL") . ", ";
                $sql .= "'" . $this->db->escape($session->getState()->value) . "', ";
                $sql .= $session->getPlannedVolumeLiters() . ", ";
                $sql .= ($session->getLotNumber() ? "'" . $this->db->escape($session->getLotNumber()) . "'" : "NULL") . ", ";
                $sql .= "NOW()";
                $sql .= ")";
            } else {
                // Update with atomic state transition
                $sql = "UPDATE " . $table . " SET ";
                $sql .= "title = '" . $this->db->escape($session->getTitle()) . "', ";
                $sql .= "state = '" . $this->db->escape($session->getState()->value) . "', ";
                $sql .= "planned_volume_liters = " . $session->getPlannedVolumeLiters() . ", ";
                $sql .= "fk_vessel = " . ($session->getVesselId() ? $session->getVesselId() : "NULL") . ", ";
                $sql .= "lot_number = " . ($session->getLotNumber() ? "'" . $this->db->escape($session->getLotNumber()) . "'" : "NULL") . " ";
                $sql .= "WHERE rowid = " . $session->getId();
            }

            $resql = $this->db->query($sql);
            if (!$resql) {
                throw new RuntimeException("Database error saving BrewSession: " . $this->db->lasterror());
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $table = MAIN_DB_PREFIX . "brew_session_v2";
        $this->db->begin();

        try {
            $sql = "DELETE FROM " . $table . " WHERE rowid = " . $id;
            $resql = $this->db->query($sql);

            if (!$resql) {
                throw new RuntimeException("Database error deleting BrewSession: " . $this->db->lasterror());
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
