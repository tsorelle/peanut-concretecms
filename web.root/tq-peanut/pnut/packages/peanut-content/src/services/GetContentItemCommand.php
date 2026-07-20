<?php

namespace Peanut\content\services;

use Peanut\content\db\ContentManager;
use Tops\services\TServiceCommand;

class GetContentItemCommand extends TServiceCommand
{

    protected function run()
    {
        $contentId = $this->getRequest();
        if (empty($contentId)) {
            $this->addErrorMessage('No content id received');
            return;
        }
        $manager = new ContentManager();
        $response = new \stdClass();
        $item = $manager->getTitleByContentId($contentId);
        $response->id = $item->id;
        $response->title = $item->title;
        $response->description = $item->description;
        $response->shared = $item->shared;
        $response->content = $manager->getLatestVersionContent($contentId);
        $response->versions = $manager->getVersionList($contentId);
        $this->setReturnValue($response);
    }
}