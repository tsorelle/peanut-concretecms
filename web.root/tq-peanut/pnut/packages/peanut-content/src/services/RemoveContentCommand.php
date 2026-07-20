<?php

namespace Peanut\content\services;

use Peanut\content\db\ContentManager;
use Peanut\content\db\model\repository\ContentVersionsRepository;
use Tops\sys\TUser;

class RemoveContentCommand extends \Tops\services\TServiceCommand
{


    protected function run(): void
    {
        $user = TUser::GetCurrent();
        if (!$user->isAuthenticated()) {
            $this->addErrorMessage('You must be signed in to do this');
            return;
        }
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('No request received');
        }
        $contentId = $request->contentId ?? null;
        if (empty($contentId)) {
            $this->addErrorMessage('No content id received');
            return;
        }
        $sharedOnly = !empty($request->sharedOnly);
        $permanent = $request->permanent ?? false;

        $manager = new ContentManager();

        $authorId = $manager->getAuthorId($request);
        if (!$authorId) {
            $this->addErrorMessage('No author found');
            return;
        }

        $item = $manager->removeContent($contentId,$permanent);
        if ($item) {
            $this->addInfoMessage('Content removed: '.$item->title);
        }
        else {
            $this->addErrorMessage('Unable to remove content');
            return;
        }

        $context = $item->context;
        $response = $manager->getLists($context, $authorId);
        $this->setReturnValue($response);
    }
}