<?php

namespace Peanut\contacts\services;

use Peanut\contacts\db\ContactsManager;

class GetContactDetailsCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $id = $this->getRequest();
        if (!is_numeric($id)) {
            $this->addErrorMessage('Invalid contact id');
            return;
        }
        $manager = new ContactsManager();
        $response = new \stdClass();
        $response->subscriptions = $manager->getContactSubscriptions($id);
        $this->setReturnValue($response);
    }
}