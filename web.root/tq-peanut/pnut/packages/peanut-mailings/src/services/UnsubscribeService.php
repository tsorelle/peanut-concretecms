<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 6/21/2019
 * Time: 5:14 AM
 */

namespace Peanut\PeanutMailings\services;


use Peanut\PeanutMailings\db\DirectoryManager;
use Tops\mail\TPostOffice;
use Tops\sys\TConfiguration;
use Tops\sys\TWebSite;

class UnsubscribeService
{

    public function unsubscribe($uid, $listId)
    {
/*        mail('terry.sorelle@outlook.com','unsubscribe test',
            "--------------------------------------\n".
            "Action: User:$uid\nList: $listId\n"
        );*/

        $manager = new DirectoryManager();
        $person = $manager->getPersonByUid($uid);
        if (!$person) {
            return;
        }
        $result = $manager->unsubscribeEmail($uid,$listId);
        $subscriptionPhrase = $result === false ? '' : " to '$result->listName'";
        $siteUrl = TWebSite::GetSiteUrl();
        $subscriptionsLink = TConfiguration::getValue('subscriptionsUrl','pages','/subscriptions');

        // todo: create template
        $message = "<p>At your request, we have cancelled your email subscription$subscriptionPhrase.</p>";

        if ($person->email) {
            $message .= sprintf(
              "<p>To manage your other subscriptions and notifications go to our <a href='%s/%s?uid=%s'>Subscriptions page</a> </p>",
                $siteUrl,$subscriptionsLink,$uid);
            TPostOffice::SendMessageFromUs($person->email,'Subscription cancelled',$message);
        }

    }
}