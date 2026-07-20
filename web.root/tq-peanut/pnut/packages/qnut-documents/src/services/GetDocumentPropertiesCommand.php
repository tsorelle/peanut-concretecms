<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/15/2018
 * Time: 11:19 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;

class GetDocumentPropertiesCommand extends TServiceCommand
{

    protected function run()
    {
        $id = $this->getRequest();
        if (empty($id)) {
            $this->addErrorMessage('error-no-id');
            return;
        }
        $response = new \stdClass();
        $documentManager = new DocumentManager();

        $properties = $documentManager->getDocumentPropertyValues($id);
        if (empty($properties)) {
            $properties = array();
        }
        $response->properties = $properties;
        $response->committees = $documentManager->getAssociatedCommittees($id);
        $response->groups = $documentManager->getAssociatedGroups($id);

        $this->setReturnValue($response);

    }
}