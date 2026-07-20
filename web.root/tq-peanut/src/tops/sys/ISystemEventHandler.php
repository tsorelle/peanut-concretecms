<?php

namespace Tops\sys;

interface ISystemEventHandler
{
    public function handleEvent($event,$data=null) : bool;
}