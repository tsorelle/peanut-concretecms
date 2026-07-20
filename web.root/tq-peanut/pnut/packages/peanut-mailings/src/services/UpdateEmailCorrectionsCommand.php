<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/18/2019
 * Time: 5:30 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\EmailManager;
use Tops\mail\TEmailValidator;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

class UpdateEmailCorrectionsCommand extends TServiceCommand
{

    public function __construct() {
        $this->addAuthorization(TPermissionsManager::updateDirectoryPermissionName);
    }

    /**
     * @throws \Exception
     */
    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        $user = $this->getUser();
        $username = $user->getUserName();
        $manager = new EmailManager();
        $newAddress = @$request->newAddress;
        if ($newAddress) {
            if (TEmailValidator::Invalid($newAddress)) {
                $this->addErrorMessage('validation-invalid-email2');
                return;
            }
            $manager->logEmailProblem(
                $newAddress,1,
                "Reported by ".$user->getFullName(),
                $username);
        }
        $response = new \stdClass();
        if (empty($request->changes)) {
            $response->corrections = $manager->getUnresolvedEmailProblems();
            $response->errorCount = 0;
        }
        else {
            $invalid = [];

            foreach ($request->changes as $update) {
                $correction = $manager->getEmailCorrection($update->id);
                if ($correction) {
                    if (!empty($update->email)) {
                        $validationResult = TEmailValidator::Validate($update->email);
                        if (!$validationResult->isValid) {
                            $invalid[$update->id] = $update->email;
                            continue;
                        }
                        $accountUpdateResult = false;
                        $personId = empty($correction->personId) ? false : $correction->personId;
                        if ($personId) {
                            $accountUpdateResult = $manager->updatePersonEmail(
                                $update->email,
                                $personId);
                        }
                        if ($accountUpdateResult === false) {
                            if (!empty($correction->accountId)) {
                                $accountUpdateResult = $manager->updateAccountEmail(
                                    $update->email,
                                    $correction->accountId);
                            }
                        }
                    }
                    $correction->active = 0;
                    $manager->updateCorrection($correction, $username);
                }
            }
            $corrections = $manager->getUnresolvedEmailProblems();
            $errorCount = count($invalid);
            if ($errorCount) {
                $count = count($corrections);
                for ($i = 0; $i < $count; $i++) {
                    $item = $corrections[$i];
                    if (array_key_exists($item->id, $invalid)) {
                        $item->invalid = 1;
                        $item->correction = $invalid[$item->id];
                        $corrections[$i] = $item;
                    }
                }
            }

            $response->corrections = $corrections;
            $response->errorCount = $errorCount;
        }
        $this->setReturnValue($response);
    }
}