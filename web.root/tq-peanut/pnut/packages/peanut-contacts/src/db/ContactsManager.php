<?php
namespace Peanut\contacts\db;

use Peanut\contacts\db\model\entity\Contact;
use Peanut\contacts\db\model\repository\ContactsRepository;
use Peanut\PeanutMailings\db\model\repository\EmailListsRepository;
use Peanut\contacts\db\model\repository\ContactSubscriptionAssociation;
use Peanut\PeanutMailings\sys\ISubscriptionManager;
use Tops\db\model\repository\LookupTableRepository;

class ContactsManager
{
    private $subscriptionsAssociation;

    private $contactsRepository;
    private function getContactsRepository ()
    {
        if (!isset($this->contactsRepository)) {
            $this->contactsRepository = new ContactsRepository();
        }
        return $this->contactsRepository;
    }

    private $emailListsRepository;
    private function getEmailListsRepository() {
        if (!isset($this->emailRepository)) {
            $this->emailListsRepository = new EmailListsRepository();
        }
        return $this->emailListsRepository;
    }


    public function getContactsAndLookups()
    {
        $result = new \stdClass();
        $contactsRepo = $this->getContactsRepository();
        $result->contacts = $contactsRepo->getContactList();
        $result->emailLists = $this->getEmailListsRepository()->getAll();
        $result->listingTypes = $contactsRepo->getListingTypes();
        return $result;
    }

    public function getContacts($filter=null,$activeOnly = true) {
        return $this->getContactsRepository()->getContactList($filter);
    }

    public function getContactSubscriptions($id)
    {
        $association = $this->getEmailSubscriptionsAssociation();
        return $association->getListValues($id);
    }

    private function getEmailSubscriptionsAssociation()
    {
        if (!isset($this->subscriptionsAssociation)) {
            $this->subscriptionsAssociation = new ContactSubscriptionAssociation();
        }
        return $this->subscriptionsAssociation;
    }

    public function updateContact($contactDTO, array $subscriptions = null)
    {
        $repo = $this->getContactsRepository();
        $isNew = empty($contactDTO->id);
        $contact = $isNew ? new Contact() : $repo->get($contactDTO->id);
        if (!$contact) {
            return false;
        }
        if (empty($contactDTO->sortkey)) {
            $name = $contactDTO->fullname;
            $parts = explode(' ',$contactDTO->fullname);
            $last = array_pop($parts);
            if (count($parts)) {
                $name = $last.', '.implode(' ',$parts);
            }
            $contactDTO->sortkey = $name;
        }
        $contact->assignFromObject($contactDTO);
        if (empty($contact->uid)) {
            $contact->uid = uniqid();
        }
        if ($isNew) {
            $id = $repo->insert($contact);
        }
        else {
            $repo->update($contact);
            $id = $contact->id;
        }
        if ($id === false) {
            return false;
        }
        $subscriptionsRepo = $this->getEmailSubscriptionsAssociation();
        if (is_array($subscriptions)) {
            $subscriptionsRepo->updateSubscriptions($id,$subscriptions);
        }
        return true;
    }

    public function setContactSiteAccount($contactId, $accountId)
    {
        $repo = $this->getContactsRepository();
        $contact = $repo->get($contactId);
        if (!$contact) {
            return false;
        }
        $contact->accountId = $accountId;
        $repo->update($contact);
        return true;
    }

    public function unsubscribeEmail($uid,$listId) {
        $repository = $this->getEmailListsRepository();
        $result = $this->unsubscribeByUid($uid,$listId);
        if ($result === false) {
            $result = new \stdClass();
            $result->removed = false;
            $person = $this->getContactsRepository()->getEntityByUid($uid);
            $result->personName = $person ? $person->fullname : null;
            $list = $repository->get($listId);
            $result->listName = $list ? $list->name : null;
        }
        else {
            $result->removed = true;
        }
        return $result;
    }

    public function getSubscriptionValues($userId)
    {
        $response = new \stdClass();
        $response->emailSubscriptions = [];
        $response->postalSubscriptions = [];
        $response->personId = 0;
        $response->addressId = 0;
        $response->personName = '';
        $response->accountId = 0;

        $personsRepository = $this->getContactsRepository();
        if (is_numeric($userId)) {
            $person = $personsRepository->getByAccountId($userId);
        }
        else {
            $person = $this->getPersonByUid($userId);
        }

        if (!$person) {
            return false;
        }

        if ($person) {
            $response->personName = $person->fullname;
            $response->personId = $person->id;
            $response->accountId = $person->accountId;
            $response->emailSubscriptions = $this->getEmailSubscriptionsAssociation()->getListValues($response->personId);
            // $response->notifications = $personsRepository->recievesNotifications($person->uid);
        }
        return $response;

    }

    private function getPersonByUid($userId)
    {
        return $this->getContactsRepository()->getEntityByUid($userId);
    }
    public function getEmailListLookup($includeAdminOnly = false, $translate = false)
    {
        $filter = 'cansubscribe=1';
        if ($includeAdminOnly) {
            $filter .= ' OR adminonly=1';
        }
        $repository = new LookupTableRepository('qnut_email_lists');
        return $repository->getLookupList($translate, $filter);
    }

    public function updateEmailSubscriptions($personId, $emailSubscriptions)
    {
        $this->getEmailSubscriptionsAssociation()->updateSubscriptions($personId,$emailSubscriptions);
    }

}