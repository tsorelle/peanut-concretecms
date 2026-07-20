<?php

namespace Peanut\content\services;

use Peanut\content\db\ContentManager;
use Tops\services\TServiceCommand;
use Tops\sys\TUser;

// delete all versions except the most recent
class ClearVersionsCommand extends TServiceCommand
{

    protected function run()
    {
        $user = TUser::GetCurrent();
        if (!$user->isAuthenticated()) {
            $this->addErrorMessage('You must be signed in to save content');
            return;
        }
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('No request received');
            return;
        }
        $contentId = $request->contentId ?? null;
        if (empty($contentId)) {
            $this->addErrorMessage('No content id received');
            return;
        }
        $contentManager = new ContentManager();
        $versions = $contentManager->getContentVersions($contentId);
        $content = $versions[0]->content;
        $contentManager->removeVersions($contentId);
        $contentManager->saveContent($contentId,$content,true);
        $versions = $contentManager->getVersionList($contentId);
        $this->setReturnValue($versions);
    }
}