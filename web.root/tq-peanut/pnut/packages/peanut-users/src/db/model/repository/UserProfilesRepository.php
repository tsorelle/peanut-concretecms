<?php
/**
 * Created by /tools/create-model.php
 * Time:  2022-05-11 14:51:59
 */
namespace Peanut\users\db\model\repository;

use \PDO;
use PDOStatement;
use Peanut\contacts\db\model\entity\Contact;
use Peanut\users\db\model\entity\UserProfile;
use Tops\db\IProfilesRepository;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;

class UserProfilesRepository extends \Tops\db\TEntityRepository implements IProfilesRepository
{
    protected function getTableName() {
        return 'pnut_userprofiles';
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
        return 'Peanut\users\db\model\entity\UserProfile';
    }

    protected function getFieldDefinitionList()
    {
        return array(
            'id'=>PDO::PARAM_INT,
            'fullname'=>PDO::PARAM_STR,
            'email'=>PDO::PARAM_STR,
            'phone'=>PDO::PARAM_STR,
            'listingtypeId'=>PDO::PARAM_INT,
            'sortkey'=>PDO::PARAM_STR,
            'notes'=>PDO::PARAM_STR,
            'uid'=>PDO::PARAM_STR,
            'accountId'=>PDO::PARAM_INT,
            'createdby'=>PDO::PARAM_STR,
            'createdon'=>PDO::PARAM_STR,
            'changedby'=>PDO::PARAM_STR,
            'changedon'=>PDO::PARAM_STR,
            'active'=>PDO::PARAM_STR);
    }

    private static $profileMap;
    private function getProfileMap()
    {
        if (!isset(self::$profileMap)) {
            self::$profileMap = [
                'fullname'     => 'fullname',
                'shortname'    => 'fullname',
                'displayname'  => 'fullname',
                'email'        => 'email',
                'language'     => '=en',
                'timezone'     => '=UTC',
            ];
        }
    }


    /*    public function getProfile($id)
        {
            // TODO: Implement getProfile() method.
        }*/

    public function getProfileArray($accountId)
    {
        $profile = [];
        /**
         * @var UserProfile
         */
        $contact = $this->getByAccountId($accountId);
        if ($contact) {
            $profile['full-name'   ] =$contact->fullname;
            $profile['short-name'  ] =$contact->fullname;
            $profile['display-name'] =$contact->fullname;
            $profile['email'      ]  =$contact->email;
            $profile['language'   ]  = 'en';
            $profile['timezone'   ]  = 'UTC';
        }
        return $profile;
    }

    public function checkAvailableProfile($email,$fullname) {
        if (!$email || !$fullname) {
            return 'Email and full name values are required.';
        }
        $contact = $this->getSingleEntity('fullname = ?',[$fullname]);
        if ($contact) {
            return "Contact already exists for '$fullname'";
        }
        $contact = $this->getSingleEntity('email = ?',[$email]);
        if ($contact) {
            return "Email address '$email' is already in use.";
        }
        return true;
    }

    public function registerBasicProfile($email,$fullname,$accountId) {
        if (!$email || !$fullname) {
            return 'Email and full name values are required.';
        }
        $contact = $this->getSingleEntity('fullname = ?',[$fullname]);
        if ($contact) {
            return "Contact already exists for '$fullname'";
        }
        $contact = $this->getSingleEntity('email = ?',[$email]);
        if ($contact) {
            return "Email address '$email' is already in use.";
        }
        $contact = new Contact();
        $contact->fullname = $fullname;
        $contact->email = $email;
        $contact->listingtypeId = 0;
        $contact->active = 1;
        $contact->notes = 'Created for site account';
        $contact->accountId = $accountId;
        $id = $this->insert($contact);
        return $id ? false : 'Cannot insert profile';
    }

    public function insertProfileValues(array $profile,$accountId)
    {
        return $this->registerBasicProfile(
            $profile['email'] ?? null,
            $profile['full-name'] ?? null,
            $accountId);
    }

    public function updateProfileValues(array $profile,$accountId)
    {
        $email = $profile['email'] ?? null;
        $fullname =  $profile['full-name'] ?? null;
        if (!($email || $fullname)) {
            return false;
        }
        $contact = $this->getByAccountId($accountId);
        /**
         * @var $contact Contact;
         */
        if (!$contact) {
            return false;
        }

        if ($email) {
            $contact->email = $email;
        }
        if ($fullname) {
            $contact->fullname = $fullname;
        }
        return $this->update($contact);
    }

    public function getProfileTableName()
    {
        return $this->getTableName();
    }

    public function clearAccountId($accountId) {
        $contact = $this->getByAccountId($accountId);
        if ($contact) {
            $contact->accountId = 0;
            $this->update($contact);
        }
    }

    public function getEmail($accountId)
    {
        $contact = $this->getByAccountId($accountId);
        if ($contact) {
            return $contact->email;
        }
        return false;
    }

    /**
     * @param $accountId
     * @return UserProfile
     */
    public function getByAccountId($accountId)
    {
        return $this->getSingleEntity('accountId=?',[$accountId]);
    }


    /**
     * @param $email
     * @return Contact
     */
    public function getByEmail($email) {
        return $this->getSingleEntity('email=?',[$email]);
    }

    public function getAccountIdByEmail($email)
    {
        $contact = $this->getByEmail($email);
        if ($contact) {
            return $contact->accountId;
        }
        return false;
    }

    public function removeProfile($accountId)
    {
        $contact = $this->getByAccountId($accountId);
        $this->remove($contact->id);
    }

    public function getUserProfiles()
    {
        $sql = 'SELECT  p.id AS profileId, accountId, u.username, fullname, email, u.`active`'.
            ' FROM pnut_userprofiles p JOIN `pnut_users` u ON u.id = p.`accountId`WHERE p.active = 1';
        $stmt = $this->executeStatement($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getListingTypes($activeOnly = true) {
        // not supported for simple user profiles
        return [];
    }

    public function getContactList($filter=null,$activeOnly = true) {
        if ($filter) {
            $where = "fullname LIKE ?";
            $params = ["%$filter%"];
        }
        else {
            $where = '';
            $params = [];
        }
        return $this->getEntityCollection($where,$params,!$activeOnly,'ORDER BY IFNULL(sortkey,fullname)');
    }

    public function getEmailSubscribersList($listId) {
        // not supported for simple user profiles
        return [];
    }
    public function removeEmailSubscriptions($listId, array $contactList) {

        // not supported for simple user profiles
        return true;
    }

}