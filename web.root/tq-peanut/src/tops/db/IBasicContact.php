<?php

namespace Tops\db;

interface IBasicContact
{
    public function getEmail() : string;
    public function getFullName() : string;
    public function getAccountId() : string;
    public function getId() : string;
    public function getUid() : string;
}