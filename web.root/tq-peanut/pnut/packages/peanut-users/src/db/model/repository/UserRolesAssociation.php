<?php

namespace Peanut\users\db\model\repository;

class UserRolesAssociation extends \Tops\db\TAssociationRepository
{
    public function __construct()
    {
        parent::__construct(
            'pnut_userroles',
            'pnut_users',
            'pnut_roles',
            'userId',
            'roleId',
            'Peanut\users\db\model\entity\User',
            'Peanut\users\db\model\entity\Role');
    }
}