<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/15/2017
 * Time: 5:27 AM
 */

namespace Tops\sys;

/**
 * Class TLookupItem
 * @package Tops\sys
 *
 * Matching class for TypeScript interface ILookupItem
 *
 *    export interface ILookupItem {
 *        id : any;
 *        code: string;
 *        name: string;
 *        description : string;
 *
 *    }
 */
class TLookupItem
{
    public $id = 0;
    public $code = '';
    public $name = '';
    public $description = '';

    public static function Create(
        $id = 0,
        $code = '',
        $name = '',
        $description = ''
    )
    {
        $result = new TLookupItem();
        $result->id = $id;
        $result->code 		 = $code;
        $result->name 		 = $name;
        $result->description = $description;

        return $result;
    }

}