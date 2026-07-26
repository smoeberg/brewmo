<?php

namespace BrewMo\Infrastructure\Repository;

use BrewMo\Domain\Container\Container;
use BrewMo\Domain\Container\ContainerType;
use BrewMo\Domain\Container\ContainerStatus;
use BrewMo\Domain\Repository\ContainerRepositoryInterface;
use RuntimeException;

/**
 * Dolibarr implementation of ContainerRepositoryInterface
 * Manages containers (kegs, casks, tanks) in the brewmo_brew_container table
 */
class DolibarrContainerRepository implements ContainerRepositoryInterface
{
    private object $db;

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Container
    {
        $sql = "SELECT rowid, ref, label, capacity_l, container_type, status, ";
        $sql .= "fk_brewsession, batch_number, location, is_active, notes";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_container WHERE rowid = ?";

        $res = $this->db->query($sql, [(int)$id]);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        
        return new Container(
            (int)$obj->rowid,
            $obj->ref,
            $obj->label,
            (float)$obj->capacity_l,
            ContainerType::from($obj->container_type),
            ContainerStatus::from($obj->status),
            $obj->fk_brewsession ? (int)$obj->fk_brewsession : null,
            $obj->batch_number,
            $obj->location,
            (bool)$obj->is_active,
            $obj->notes
        );
    }

    public function findByRef(string $ref): ?Container
    {
        $sql = "SELECT rowid, ref, label, capacity_l, container_type, status, ";
        $sql .= "fk_brewsession, batch_number, location, is_active, notes";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_container WHERE ref = ?";

        $res = $this->db->query($sql, [$ref]);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        
        return new Container(
            (int)$obj->rowid,
            $obj->ref,
            $obj->label,
            (float)$obj->capacity_l,
            ContainerType::from($obj->container_type),
            ContainerStatus::from($obj->status),
            $obj->fk_brewsession ? (int)$obj->fk_brewsession : null,
            $obj->batch_number,
            $obj->location,
            (bool)$obj->is_active,
            $obj->notes
        );
    }

    public function findByStatus(string $status): array
    {
        $sql = "SELECT rowid, ref, label, capacity_l, container_type, status, ";
        $sql .= "fk_brewsession, batch_number, location, is_active, notes";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_container WHERE status = ?";

        $res = $this->db->query($sql, [$status]);
        if (!$res) {
            return [];
        }

        $containers = [];
        while ($obj = $this->db->fetch_object($res)) {
            $containers[] = new Container(
                (int)$obj->rowid,
                $obj->ref,
                $obj->label,
                (float)$obj->capacity_l,
                ContainerType::from($obj->container_type),
                ContainerStatus::from($obj->status),
                $obj->fk_brewsession ? (int)$obj->fk_brewsession : null,
                $obj->batch_number,
                $obj->location,
                (bool)$obj->is_active,
                $obj->notes
            );
        }

        return $containers;
    }

    public function findByBrewSession(int $brewSessionId): array
    {
        $sql = "SELECT rowid, ref, label, capacity_l, container_type, status, ";
        $sql .= "fk_brewsession, batch_number, location, is_active, notes";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_container WHERE fk_brewsession = ?";

        $res = $this->db->query($sql, [(int)$brewSessionId]);
        if (!$res) {
            return [];
        }

        $containers = [];
        while ($obj = $this->db->fetch_object($res)) {
            $containers[] = new Container(
                (int)$obj->rowid,
                $obj->ref,
                $obj->label,
                (float)$obj->capacity_l,
                ContainerType::from($obj->container_type),
                ContainerStatus::from($obj->status),
                $obj->fk_brewsession ? (int)$obj->fk_brewsession : null,
                $obj->batch_number,
                $obj->location,
                (bool)$obj->is_active,
                $obj->notes
            );
        }

        return $containers;
    }

    public function findAll(): array
    {
        $sql = "SELECT rowid, ref, label, capacity_l, container_type, status, ";
        $sql .= "fk_brewsession, batch_number, location, is_active, notes";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_container ORDER BY ref";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $containers = [];
        while ($obj = $this->db->fetch_object($res)) {
            $containers[] = new Container(
                (int)$obj->rowid,
                $obj->ref,
                $obj->label,
                (float)$obj->capacity_l,
                ContainerType::from($obj->container_type),
                ContainerStatus::from($obj->status),
                $obj->fk_brewsession ? (int)$obj->fk_brewsession : null,
                $obj->batch_number,
                $obj->location,
                (bool)$obj->is_active,
                $obj->notes
            );
        }

        return $containers;
    }

    public function save(Container $container): Container
    {
        if ($container->getId() === null) {
            // INSERT
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_container ";
            $sql .= "(entity, ref, label, capacity_l, container_type, status, fk_brewsession, batch_number, location, is_active, notes)";
            $sql .= " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                1, // entity
                $container->getRef(),
                $container->getLabel(),
                $container->getCapacityLiters(),
                $container->getType()->value,
                $container->getStatus()->value,
                $container->getCurrentBrewSessionId(),
                $container->getCurrentBatchNumber(),
                $container->getLocation(),
                $container->isActive() ? 1 : 0,
                $container->getNotes()
            ];

            $res = $this->db->query($sql, $params);
            if (!$res) {
                throw new RuntimeException("Database error creating Container: " . $this->db->lasterror());
            }

            $insertedId = $this->db->last_insert_id(MAIN_DB_PREFIX . "brew_container");
            
            return new Container(
                $insertedId,
                $container->getRef(),
                $container->getLabel(),
                $container->getCapacityLiters(),
                $container->getType(),
                $container->getStatus(),
                $container->getCurrentBrewSessionId(),
                $container->getCurrentBatchNumber(),
                $container->getLocation(),
                $container->isActive(),
                $container->getNotes()
            );
        } else {
            // UPDATE
            $sql = "UPDATE " . MAIN_DB_PREFIX . "brew_container SET";
            $sql .= " ref = ?, label = ?, capacity_l = ?, container_type = ?, status = ?, ";
            $sql .= "fk_brewsession = ?, batch_number = ?, location = ?, is_active = ?, notes = ?";
            $sql .= " WHERE rowid = ?";

            $params = [
                $container->getRef(),
                $container->getLabel(),
                $container->getCapacityLiters(),
                $container->getType()->value,
                $container->getStatus()->value,
                $container->getCurrentBrewSessionId(),
                $container->getCurrentBatchNumber(),
                $container->getLocation(),
                $container->isActive() ? 1 : 0,
                $container->getNotes(),
                $container->getId()
            ];

            $res = $this->db->query($sql, $params);
            if (!$res) {
                throw new RuntimeException("Database error updating Container: " . $this->db->lasterror());
            }

            return $container;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_container WHERE rowid = ?";
        return (bool)$this->db->query($sql, [(int)$id]);
    }
}
