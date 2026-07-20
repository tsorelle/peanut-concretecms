<?php

namespace Peanut\content\services;

use Peanut\content\db\ContentManager;
use Peanut\content\db\model\repository\ContentVersionsRepository;
use Tops\services\TServiceCommand;

class GetVersionsCommand extends TServiceCommand
{

    protected function run()
    {
        $contentId = $this->getRequest();
        if (empty($contentId)) {
            $this->addErrorMessage('No content id received');
            return;
        }
        $manager = new ContentManager();
        $result = $manager->getVersionList($contentId);
        $this->setReturnValue($result);
    }
}