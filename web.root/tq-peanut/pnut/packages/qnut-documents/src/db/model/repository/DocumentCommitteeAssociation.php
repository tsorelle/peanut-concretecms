<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/2/2018
 * Time: 11:19 AM
 */

namespace Peanut\QnutDocuments\db\model\repository;;

use Tops\db\TAssociationRepository;

class DocumentCommitteeAssociation extends TAssociationRepository
{
    public function __construct()
    {
        parent::__construct(
            'qnut_document_committees',
            'qnut_documents',
            'qnut_committees',
            'documentId',
            'committeeId',
            'Peanut\QnutDocuments\db\model\entity\Document',
            '\Tops\db\NamedEntity');
    }
}