<?php

namespace Peanut\contacts\services;

use Peanut\contacts\db\ContactsManager;

class UpdateContactCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request->contact)) {
            $this->addErrorMessage('No contact received');
            return;
        }
        if (empty($request->contact->fullname)) {
            $this->addErrorMessage('Fullname is required');
            return;
        }
        if (empty($request->contact->email)) {
            $this->addErrorMessage('Fullname is required');
            return;
        }
        if (!is_array($request->subscriptions)) {
            $this->addErrorMessage('No subscriptions received');
            return;
        }


        $manager = new ContactsManager();

        if ($manager->updateContact($request->contact,$request->subscriptions) === false) {
            $this->addErrorMessage('Not able to update contact');
            return;
        }

        $response = new \stdClass();
        $response = $manager->getContacts();
        $this->setReturnValue($response);

    }
}