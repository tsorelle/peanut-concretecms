<?php

namespace Tops\db;

interface IProfilesRepository
{
    // public function getProfile($id);
    public function getProfileArray($accountId);
    public function checkAvailableProfile($email,$fullname);
    public function registerBasicProfile($email,$fullname,$accountId);
    public function insertProfileValues(array $profile, $accountId);
    public function updateProfileValues(array $profile, $accountId);
    public function getProfileTableName();
    public function clearAccountId($accountId);
    public function getEmail($accountId);
    public function getAccountIdByEmail($email);
    public function removeProfile($accountId);
    public function getUserProfiles();
}