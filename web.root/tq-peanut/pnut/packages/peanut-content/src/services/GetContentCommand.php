<?php

namespace Peanut\content\services;

use Peanut\content\db\ContentManager;
use Peanut\content\db\model\repository\ContentVersionsRepository;
use Tops\sys\TUser;

class GetContentCommand extends \Tops\services\TServiceCommand
{


    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('No request received');
        }

        $versionId = $request->versionId ?? null;

        $manager = new ContentManager();
        if (empty($versionId)) {
            $contentId = $request->contentId ?? null;
            if (empty($contentId)) {
                $this->addErrorMessage('No content or version id');
                return;
            }
            $response = $manager->getLatestVersionContent($contentId);
        }
        else {
            $response = $manager->getVersionContent($versionId);
        }


        if (empty($response) || empty($response->content)) {
            $this->addErrorMessage('No content found');
            return;
        }

        $this->setReturnValue($response->content);
    }
}