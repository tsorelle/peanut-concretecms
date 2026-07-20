<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/7/2018
 * Time: 6:18 AM
 */

namespace Peanut\QnutDocuments\services;


use Peanut\QnutDocuments\DocumentManager;
use Tops\services\TServiceCommand;

class IndexDocumentsCommand extends TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        $timelimit = @$request->timelimit;
        $manager = new DocumentManager();
        $manager->indexDocuments($this->getMessages(),$timelimit);
    }
}