<?php
namespace Peanut\content\services;

use Peanut\content\db\ContentManager;
use Tops\sys\TUser;

class GetTitlesCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $user = TUser::GetCurrent();
        if (!$user->isAuthenticated()) {
            $this->addErrorMessage('You must be signed in to do this');
            return;
        }
        $request = $this->getRequest();
        $context = $request->context ?? null;
        if (empty($context)) {
            $this->addErrorMessage('No context received');
            return;
        }
        $sharedOnly = !empty($request->sharedOnly);
        $manager = new ContentManager();
        $authorId = $manager->getAuthorId($request);
        if (!$authorId) {
            $this->addErrorMessage('No author found');
            return;
        }
        $response = $manager->getLists($context,$authorId,$sharedOnly);
        $this->setReturnValue($response);
    }
}