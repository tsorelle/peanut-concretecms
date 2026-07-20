<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/7/2015
 * Time: 7:14 AM
 */

namespace Tops\cache;


interface ITopsCache {
    public function Get($key);
    public function Remove($key);
    public function Set($key,$value,$duration=null);
    public function Flush($category=null);
}