<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/20/2017
 * Time: 5:32 PM
 */

namespace Peanut\Mailboxes\services;


use Tops\mail\IMailboxManager;
use Tops\mail\TPostOffice;
use Tops\services\TServiceCommand;
use Tops\sys\TObjectContainer;
use Tops\sys\TPermissionsManager;

class DeleteMailboxCommand extends TServiceCommand
{

    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::mailAdminPermissionName);
    }

    protected function run()
    {
        $code = $this->getRequest();
        if ( in_array($code,TPostOffice::SystemMailboxes)) {
            $this->addErrorMessage("Deletion of this mailbox is not allowed.");
            return;
        }
        $manager = TPostOffice::GetMailboxManager();
        $mailbox = $manager->findByCode($code);
        if (!empty($mailbox)) {
            $manager->drop($mailbox->getMailboxId());
        }
        $list = $manager->getMailboxes(true);
        $this->setReturnValue($list);
    }
}