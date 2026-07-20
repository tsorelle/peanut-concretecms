<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/20/2017
 * Time: 7:44 AM
 */

namespace Peanut\Mailboxes\services;

use Tops\mail\TMailbox;
use Tops\mail\TPostOffice;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

/**
 * Class UpdateMailboxCommand
 * @package Peanut\Mailboxes\services
 *
 * Request:
 * export interface IMailBox {
     * id:string;
     * mailboxcode:string ;
     * address:string;
     * displaytext:string;
     * description:string;
     * public: any;
     * published: any
     * active: any;
 * }
 */
class UpdateMailboxCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::mailAdminPermissionName);
    }

    protected function run()
    {
        $manager = TPostOffice::GetMailboxManager();
        $mailBox = $this->getRequest();
        $public = empty($mailBox->public) ? 0 : 1;
        $published = empty($mailBox->published) ? 0 : 1;


        /**
         * @var $current TMailbox
         */
        $current = $manager->findByCode($mailBox->mailboxcode);
        $new = empty($current);
        if ($new) {
            $manager->addMailbox(
                $mailBox->mailboxcode,
                $mailBox->displaytext,
                $mailBox->address,
                $mailBox->description,
                $public,
                $published,
            );
        }
        else {
            $current->setDescription($mailBox->description);
            $current->setMailboxCode($mailBox->mailboxcode);
            $current->setName($mailBox->displaytext);
            $current->setEmail($mailBox->address);
            $current->setPublic($public);
            $current->setPublished($mailBox->published);
            $current->setUpdateTime($this->getUser()->getUserName());
            $manager->updateMailbox($current);
        }

        $result = $manager->getMailboxes(true);
        $this->setReturnValue($result);

    }
}