<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 4/14/2019
 * Time: 7:20 AM
 */

namespace Peanut\QnutDocuments\db\model\repository;


use Tops\db\TAssociationRepository;

class DocumentUsergroupsAssociation extends TAssociationRepository
{
    public function __construct()
    {
        parent::__construct(
            'qnut_usergroup_documents',
            'qnut_documents',
            'qnut_usergroups',
            'documentid',
            'groupid',
            'Peanut\QnutDocuments\db\model\entity\Document',
            'Peanut\QnutUsergroups\db\model\entity\Usergroup'
        );
    }

}