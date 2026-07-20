<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/27/2017
 * Time: 6:27 AM
 */

namespace Peanut\PeanutMailings\db\model\repository;


use Tops\db\TAssociationRepository;

abstract class SubscriptionAssociation extends TAssociationRepository
{
    public function getListValues($entityId)
    {
        return $this->getRightValues($entityId);
    }

    public function getLists($entityId)
    {
        return $this->getRightObjects($entityId);
    }

    public function getSubscribers($listId)
    {
        return $this->getLeftObjects($listId);
    }

    public function getSubscriberValues($listId)
    {
        return $this->getLeftValues($listId);
    }

    public function subscribe($entityId, $listId)
    {
        $this->addAssociationRight($entityId, $listId);
    }

    public function unsubscribe($entityId, $listId)
    {
        $this->removeAssociationRight($entityId, $listId);
    }

    public function updateSubscriptions($entityId,array $values)
    {
        $this->updateRightValues($entityId, $values);
    }

    public function updateSubscribers($listId, array $values)
    {
        $this->updateLeftValues($listId, $values);
    }

    public function removeSubscriber($entityId) {
        $this->removeAllLeft($entityId);
    }

    public function removeList($listId) {
        $this->removeAllRight($listId);
    }

}