<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/15/2018
 * Time: 9:56 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;

class DeleteDocumentCommand extends TServiceCommand
{

    public function __construct() {
        $this->addAuthorization(DocumentManager::manageLibraryPermission);
    }
    protected function run()
    {
        $id = $this->getRequest();
        if (empty($id)) {
            $this->addErrorMessage('error-no-id');
            return;
        }
        $documentManager = new DocumentManager();

        $document = $documentManager->getDocument($id);
        if (empty($document)) {
            return;
        }
        $filePath = $documentManager->getDocumentPath($document);
        $documentManager->deleteDocument($id);

        if (file_exists($filePath)) {
            $deleted = @unlink($filePath);
            if (!$deleted) {
                $this->addWarningMessage('Removed library entry but failed to delete the file.');
            }
        }

    }
}