<?php

namespace Tops\db;

interface IContactsRepository
{
    public function get($id) ;
    public function getByAccountId($accountId);
    public function getByEmail($email);
    public function getAllByEmail($email) : array;
    public function getByUid($uid);

}