<?php

namespace Peanut\content\services;

use Peanut\content\db\ContentManager;
use Tops\services\TServiceCommand;
use Tops\sys\TUser;

class SaveContentCommand extends TServiceCommand
{

    protected function run()
    {
        $user = TUser::GetCurrent();
        if (!$user->isAuthenticated()) {
            $this->addErrorMessage('You must be signed in to do this');
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
        $content = $request->content ?? null;
        if (empty($content)) {
            $this->addErrorMessage('No content received');
        }

        $final = !empty($request->final);
        $version = (new ContentManager())->saveContent($contentId,$content,$final);
        $this->addInfoMessage('Content saved');
        $this->setReturnValue($version);
    }
}