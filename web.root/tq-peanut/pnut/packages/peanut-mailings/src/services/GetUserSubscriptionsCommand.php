<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/23/2019
 * Time: 3:31 PM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\DirectoryManager;
use Tops\services\TServiceCommand;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;
use Tops\sys\TUser;

/**
 * Class GetUserSubscriptionsCommand
 * @package Peanut\Mailings\services
 *
 * Service contract
 *   Response
 *    interface IGetSubscriptionsResponse {
 *		personId: any;
 *      personName: string;
 *		addressId: any;
 *		emailLists : Peanut.ILookupItem[];
 *		postalLists : Peanut.ILookupItem[];
 *		emailSubscriptions : ISubscriptionListItem[];
 *		postalSubscriptions : ISubscriptionListItem[];
 *		translations : string[];
 *	  }
 */
class GetUserSubscriptionsCommand extends TServiceCommand
{

    /**
     * @throws \Exception
     */
    protected function run()
    {
        $manager = new DirectoryManager();
        $user = $this->getUser();
        $request = $this->getRequest();
        $response = false;

        if ($request && !empty($request->userId)) {
            $response = $manager->getSubscriptionValues($request->userId);
        }
        else if ($user->isAuthenticated()) {
            $response = $manager->getSubscriptionValues($user->getId());
        }
        if ($response === false) {
            $response = new \stdClass();
            if (!$user->isAuthenticated()) {
                $response->redirect = TConfiguration::getValue('login-page','pages','/login');
            }
            else {
                $this->addErrorMessage('Cannot find user account.');
            }
            $this->setReturnValue($response);
            return;
        }
        if ($user->isAuthenticated() && ($user->getId() == $response->accountId)) {
            $response->pageHeading = TLanguage::text('subscription-header-default');
        }
        else {
            $response->pageHeading = TLanguage::formatText('subscription-header-format',$response->personName);
        }
        $response->emailLists = $manager->getEmailListLookup($user->isAdmin());
        $response->postalLists = $manager->getPostalListLookup();
        $response->translations =
            TLanguage::getTranslations([
            'label-email-lists',
            'label-postal-lists',
            'label-save-changes',
                'label-notifications',
                'notifications-description'
        ]);

        $this->setReturnValue($response);
    }
}