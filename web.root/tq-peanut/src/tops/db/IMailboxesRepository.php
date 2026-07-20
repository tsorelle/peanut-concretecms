<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/12/2017
 * Time: 6:07 AM
 */

namespace Tops\db;


interface IMailboxesRepository extends IEntityRepository
{
    public function getMailboxList($showAll=false);
}