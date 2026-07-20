<?php

namespace Tops\db;

/**
 * Use this for ad hoc query operations in scripts and other temporary code.
 */
class GenericQueryManager extends TPdoQueryManager
{

    protected function getDatabaseId()
    {
        return null;
    }
    public function getStatement($sql,$params=[]) {
        return $this->executeStatement($sql,$params);
    }
}