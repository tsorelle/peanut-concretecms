<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/23/2015
 * Time: 3:00 PM
 */

namespace Tops\mail;

use Tops\db\EntityRepositoryFactory;
use Tops\db\IEntityRepository;
use Tops\db\IMailboxesRepository;
use Tops\sys\TUser;

class TDbMailboxManager implements IMailboxManager {

    /**
     * @var IMailboxesRepository
     */
    private $repository;

    public function __construct()
    {
        $this->repository = EntityRepositoryFactory::Get('mailboxes','Tops\\db\\model\\repository');
    }

    /**
     *
     * @param $id
     * @return IMailbox
     */
    public function find($id)
    {
        return $this->repository->get($id);
    }

    /**
     * @param $id
     */
    public function drop($id)
    {
        $this->repository->delete($id);
    }

    /**
     * @param $mailboxCode
     * @return IMailbox | bool
     */
    public function findByCode($mailboxCode)
    {
        $result = $this->repository->getEntity($mailboxCode);
        if (empty($result)) {
            return false;
        }
        return $result;
    }

    /**
     * @param null $filter
     * @return \stdClass[]
     */
    public function getMailboxes($showAll = false)
    {
        return $this->repository->getMailboxList($showAll);
    }

    /**
     * @param $code
     * @param $name
     * @param $address
     * @param $description
     * @return IMailbox
     */
    public function addMailbox($code, $name, $address, $description='',$public=1, $published=0) : int
    {
        $user = TUser::getCurrent();
        $mailbox = $this->createMailbox($code, $name, $address, $description);
        $mailbox->public = empty($public) ? 0 : 1;
        $mailbox->published = empty($published) ? 0 : 1;
        return $this->repository->insert($mailbox,$user->getUserName());
    }

    /**
     * @param IMailbox $mailbox
     * @return bool
     */
    public function updateMailbox(IMailbox $mailbox): bool
    {
        $existing = $this->findByCode($mailbox->getMailboxCode());
        if (empty($existing)) {
            return false;
        }
        $user = TUser::getCurrent();
        return $this->repository->update($mailbox,$user->getUserName());
    }

    /**
     * @param $code
     * @param $name
     * @param $address
     * @param $description
     * @return IMailbox
     */
    public function createMailbox($code, $name, $address, $description)
    {
        $user = TUser::getCurrent();
        $result = TMailbox::Create($code,$name,$address,$description);
        $result->setCreateTime($user->getUserName());
        return $result;
    }

    public function saveChanges()
    {
        // not used
    }

    /**
     * @param $mailboxCode string
     */
    public function remove($mailboxCode) {
        /**
         * @var $box TMailbox
         */
        $box = $this->repository->getEntity($mailboxCode);
        if (!empty($box)) {
            $this->repository->remove($box->getMailboxId());
        }
    }

    /**
     * @param $mailboxCode string
     */
    public function restore($mailboxCode)
    {
        /**
         * @var $box TMailbox
         */
        $box = $this->repository->getEntity($mailboxCode);
        if (!empty($box)) {
            $this->repository->restore($box->getMailboxId());
        }
    }
}