<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/24/2019
 * Time: 9:57 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\DirectoryManager;
use Tops\services\TServiceCommand;

/**
 * Class UpdatePersonSubscriptionsCommand
 * @package Peanut\Mailings\services
 *
 *  Service contract
 *      Request:
 *          interface IUpdateSubscriptionsRequest {
 *              emailSubscriptions : any[];
 *              postalSubscriptions : any[];
 *              personId : any;
 *              addressId :any;
 *      }
 */
class UpdatePersonSubscriptionsCommand extends TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('service-invalid-request');
            return;
        }
        
        if (isset($request->emailSubscriptions)) {
            $personId = isset($request->personId) ? $request->personId : null;
            if ($personId === null) {
                $this->addErrorMessage('service-error-no-personid');
                return;
            }
        }
        if (isset($request->postalSubscriptions)) {
            $addressId = isset($request->addressId) ? $request->addressId : null;
            if ($addressId === null) {
                $this->addErrorMessage('service-error-no-addressid');
                return;
            }
        }
        $manager = new DirectoryManager();
        $manager->updateEmailSubscriptions($personId,$request->emailSubscriptions);
        $manager->updatePostalSubscriptions($addressId,$request->postalSubscriptions);
        if (isset($request->notifications)) {
            if ($request->notifications == 0) {
                $manager->disableNotifications($personId);
            }
            else {
                $manager->enableNotifications($personId);
            }
        }
        $this->addInfoMessage('service-message-subscriptions-updated');
    }
}