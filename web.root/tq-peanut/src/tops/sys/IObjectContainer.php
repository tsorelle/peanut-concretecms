<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 7/3/2017
 * Time: 5:49 AM
 */

namespace Tops\sys;


interface IObjectContainer
{
    public function get($key);
    public function hasDefinition($key);

}