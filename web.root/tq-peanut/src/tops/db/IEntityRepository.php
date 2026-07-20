<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 8/31/2017
 * Time: 6:51 AM
 */

namespace Tops\db;

/**
 * Interface IEntityRepository
 * @package Tops\db
 *
 * See the tops-db project for implementations.
 */
interface IEntityRepository
{
    public function startTransaction();

    public function commitTransaction();

    public function rollbackTransaction();

    /**
     * @param $id
     * @return mixed
     */
    public function get($id);

    public function getAll($includeInactive=false);

    public function updateValues($id, array $fields, $userName = 'admin');

    public function update($dto, $userName = 'admin');

    public function insert($dto, $userName = 'admin');

    public function delete($id);

    public function remove($id);

    public function restore($id);

    public function getEntity($value, $includeInactive = false, $fieldName = null);

}