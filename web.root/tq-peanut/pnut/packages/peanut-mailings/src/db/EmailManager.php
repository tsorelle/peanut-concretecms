<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/17/2019
 * Time: 5:15 AM
 */

namespace Peanut\PeanutMailings\db;

use Peanut\PeanutMailings\db\model\entity\EmailCorrection;
use Peanut\PeanutMailings\db\model\repository\EmailCorrectionsRepository;
use Peanut\PeanutMailings\db\model\repository\EmailFailuresRepository;
use Peanut\PeanutMailings\db\model\repository\EmailListsRepository;
use Peanut\PeanutMailings\db\model\repository\PersonsRepository;
use Tops\mail\TEmailAddress;
use Tops\mail\TPostOffice;
use Tops\sys\TCmsEvents;
use Tops\sys\TConfiguration;
use Tops\sys\TUser;

class EmailManager
{

    const defaultPersonFormUrl = '/community/directory/members?pid=';


    private $personsRepository;

    private function getPersonsRepository()
    {
        if (!isset($this->personsRepository)) {
            $this->personsRepository = new PersonsRepository();
        }
        return $this->personsRepository;
    }

    /**
     * @var EmailCorrectionsRepository
     */
    private $emailCorrectionsRepository;

    private function getEmailCorrectionsRepository()
    {
        if (!isset($this->emailCorrectionsRepository)) {
            $this->emailCorrectionsRepository = new EmailCorrectionsRepository();
        }
        return $this->emailCorrectionsRepository;
    }

    private $emailFailureLog;
    private function getEmailFailureLog() {
        if (!isset($this->emailFailureLog)) {
            $this->emailFailureLog = new EmailFailuresRepository();
        }
        return $this->emailFailureLog;
    }

    /**
     * @return EmailListsRepository
     */
    private $emailListsRepository;
    private function getEmailListsRepository() {
        if (!isset($this->emailListsRepository)) {
            $this->emailListsRepository = new EmailListsRepository();
        }
        return $this->emailListsRepository;
    }

    private $personFormUrl;

    private function getPersonFormUrl()
    {
        if (!isset($this->personFormUrl)) {
            $this->personFormUrl = TConfiguration::getValue('personForm', 'pages', self::defaultPersonFormUrl);
        }
        return $this->personFormUrl;
    }

    /**
     * @return \stdClass[]
     *
     */
    public function getUnresolvedEmailProblems()
    {
        $personForm = $this->getPersonFormUrl();
        $repository = $this->getEmailCorrectionsRepository();
        $repository->refreshCorrections();
        return $repository->getPendingCorrections($this->getPersonFormUrl());
    }

    public function logEmailErrorReport($hookRequest) {
        $log = $this->getEmailFailureLog();
        $log->addEntry($hookRequest);
    }

    public function bounced($messageId) {
        $log = $this->getEmailFailureLog();
        return $log->checkForBounce($messageId);

    }

    /**
     * @param $emailAddress
     * @param $errorLevel
     * @param $errorMessage
     * @param string $username
     * @throws \Exception
     */
    public function logEmailProblem($emailAddress, $errorLevel, $errorMessage, $username = 'system')
    {
        $correctionsRepository = $this->getEmailCorrectionsRepository();
        $personsRepository = $this->getPersonsRepository();
        TCmsEvents::Enable(false);
        $user = TUser::getByEmail($emailAddress);
        try {
            $entry = $correctionsRepository->getCorrection($emailAddress);
            if ($entry == false) {
                // if no entry, create new one
                $accountId = $user ? $user->getId() : 0;
                $entry = new EmailCorrection();
                $person = $personsRepository->getByAccountId($accountId);
                if (!$person) {
                    $persons = $personsRepository->getPersonsByEmail($emailAddress);
                    if (!empty($persons)) {
                        $person = array_shift($persons);
                    }
                }
                if ($person) {
                    $entry->personId = $person->getId();
                }

                if ($user) {
                    $entry->name = $user->getFullName();
                } else if ($person) {
                    $entry->name = $person->firstname . ' ' . $person->lastname;
                } else {
                    $entry->name = '(unknown)';
                }

                $entry->address = $emailAddress;
                $entry->errorLevel = $errorLevel;
                $entry->errorMessage = $errorMessage;
                $entry->accountId = $accountId;
                $entry->createdby = $username;
                $entry->reportedDate = date('Y-m-d h:m:s');
                $correctionsRepository->insert($entry);
            }

            // blank email address in persons table and cms account
            $people = $personsRepository->getPersonsByEmail($emailAddress);
            if (!empty($people)) {
                foreach ($people as $person) {
                    if ($person->email) {
                        $person->email = null;
                        $personsRepository->updatePerson($person);
                    }
                }
            }

            if ($user) {
                $placeholder = 'disabled.' . uniqid() . '@2quakers.net';
                $user->setProfileValue(TUser::profileKeyEmail, $placeholder);
            }
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            TCmsEvents::Enable();
        }
    }

    /**
     * @param $emailAddress
     * @param $correctionId
     * @return bool|string
     */
    public function correctEmail($emailAddress, $correctionId)
    {
        TCmsEvents::Enable(false);
        $correctionsRepository = $this->getEmailCorrectionsRepository();
        $correction = $correctionsRepository->get($correctionId);
        if (!$correction) {
            return 'Correction not in log';
        }
        $accountId = $correction->accountId;
        $personId = $correction->personId;
        $personsRepository = $this->getPersonsRepository();

        try {
            if ($accountId) {
                $user = TUser::getByEmail($emailAddress);
                if ($user) {
                    if ($user->getId() !== $accountId) {
                        return "Email '$emailAddress' already used by someone else.";
                    }
                    // otherwise, already correct, fall though and reconcile
                } else {
                    $user = TUser::getById($accountId);
                    if ($user) {
                        $user->setProfileValue(TUser::profileKeyEmail, $emailAddress);
                    }
                }
            }

            if ($personId) {
                $person = $personsRepository->get($personId);
                if ($person && ($person->email != $emailAddress)) {
                    $person->email = $emailAddress;
                    $personsRepository->update($person);
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        } finally {
            TCmsEvents::Enable();
        }
        $correctionsRepository->resolve($emailAddress);
        return true;
    }

    public function updateCorrection(EmailCorrection $correction, $username = 'admin')
    {
        $this->getEmailCorrectionsRepository()->update($correction, $username);
    }

    /**
     * @param $email
     * @param $personId
     * @param string $username
     * @return bool | string
     */
    public function updatePersonEmail($email, $personId, $username = 'admin')
    {
        $person = $this->getPersonsRepository()->get($personId);
        if (!$person) {
            return false;
        }
        $person->email = $email;
        $this->getPersonsRepository()->update($person, $username);
        if (empty($person->accountId)) {
            return false;
        }
        return $this->updateAccountEmail($email, $person->accountId);
    }

    /**
     * @param $email
     * @param $accountId
     * @return bool|string
     */
    public function updateAccountEmail($email, $accountId)
    {
        TCmsEvents::Enable(false);
        try {
            $account = TUser::getById($accountId);
            if ($account) {
                $account->setProfileValue(TUser::profileKeyEmail, $email);
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        } finally {
            TCmsEvents::Enable();
        }
        return true;
    }

    /**
     * @param $id
     * @return EmailCorrection | false
     */
    public function getEmailCorrection($id)
    {
        return $this->getEmailCorrectionsRepository()->get($id);
    }

    /**
     * @param $recipient
     * @throws \Exception
     */
    public function unsubscribe($recipient)
    {
        $emailAddress = TEmailAddress::FromString($recipient)->getAddress();
        $correctionsRepository = $this->getEmailCorrectionsRepository();
        $personsRepository = $this->getPersonsRepository();
        $user = TUser::getByEmail($emailAddress);
        TCmsEvents::Enable(false);
        try {
            $people = $personsRepository->getPersonsByEmail($emailAddress);
            if (!empty($people)) {
                foreach ($people as $person) {
                    if ($person->email) {
                        $correctionsRepository->unsubscribeAll($person->id);
                        $person->email = null;
                        $personsRepository->updatePerson($person);
                    }
                }
            }
            if ($user) {
                $placeholder = 'disabled.' . uniqid() . '@gmail.com';
                $user->setProfileValue(TUser::profileKeyEmail, $placeholder);
            }
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            TCmsEvents::Enable();
        }
    }

} // end class