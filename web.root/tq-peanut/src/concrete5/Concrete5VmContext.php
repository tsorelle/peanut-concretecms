<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/3/2019
 * Time: 8:07 AM
 */

namespace Tops\concrete5;


use Peanut\sys\TVmContext;
use Tops\sys\TPermissionsManager;
use Tops\sys\TStrings;
use Tops\sys\TUser;

class Concrete5VmContext extends TVmContext
{
    /**
     * @var Concrete5Repository
     */
    private $respostory;
    private function getRepository() {
        if (!isset($this->respostory)) {
            $this->respostory = new Concrete5Repository();
        }
        return $this->respostory;
    }

    private function isInRole($role) {
        $user = TUser::getCurrent();
        if ($role == TPermissionsManager::guestRole) {
            return !$user->isAuthenticated();
        }
        return $user->isMemberOf($role);
    }

    private function getRoleSelection($value)
    {
        if (empty($value)) {
            return '';
        }
        $parts = explode('?', $value);
        if (sizeof($parts) > 1) {
            $role = $parts[0];
            list($yes, $no) = TStrings::Split($parts[1],':',2,'');
            return  $this->isInRole($role) ? $yes : $no;
        }
        return $parts[0];
    }
    protected function get($contextId)
    {
        // updated for repo 1/31/2025
        $result = self::getNullContext();
        if (!empty($contextId)) {
            $value = null;

            // seperate block id & default
            $parts = explode('&',$contextId);
            $blockId = array_shift($parts);

            // join remainder in case default value contains literal ampersand
            $shared = implode('&',$parts);
            $value = null;
            $blockData = $this->getRepository()->getKnockoutViewData($blockId);
            if (!empty($blockData)) {
                $result->viewmodel = $blockData->viewmodel;
                $value = $blockData->inputvalue;
            }

            // check for role based data
            //  e.g.  admin?specialvalue
            $result->value = $this->getRoleSelection($value);
            $result->shared = $this->getRoleSelection($shared);
            if (empty($result->value)) {
                $result->value = $result->shared;
            }
        }
        return $result;
    }
}