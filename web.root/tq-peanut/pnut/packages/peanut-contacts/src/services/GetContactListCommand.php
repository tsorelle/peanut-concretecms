<?php

namespace Peanut\contacts\services;

use Peanut\contacts\db\ContactsManager;

class GetContactListCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        $response = new \stdClass();
        $manager = new ContactsManager();
        $response->contacts = $manager->getContacts($request->searchvalue ?? '');
        $this->setReturnValue($response);
    }
}