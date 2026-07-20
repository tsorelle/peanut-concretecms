<?php

namespace Tops\db\model;

interface IEntity
{
    public function getEntityName();

    public function setId($value);

    public function getId();

    public function setActive($value);

    public function getActive();

    public function getAssociations();
}