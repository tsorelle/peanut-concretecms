<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/16/2018
 * Time: 5:08 AM
 */

namespace Tops\sys;


class TCsvFormatter
{
    public static function ToCsv(array $objects, array $types= [], $includeHeader=true) {
        $result = [];
        if (sizeof($objects) == 0) {
            return $result;
        }
        if ($includeHeader) {
            $header = '';
            foreach ($objects[0] as $fieldName => $value) {
                if (!empty($header)) {
                    $header .= ',';
                }
                $header .= '"'.$fieldName.'"';
            }
            $result[] = $header;
        }

        foreach ($objects as $record) {
            $line = '';
            if (!empty($line)) {
                $line .= ',';
            }
            foreach ($record as $fieldName => $value) {
                if (!empty($line)) {
                    $line .= ',';
                }
                $type = array_key_exists($fieldName,$types) ? $types[$fieldName] : 'string';
                $line .= self::FieldValue($value,$type);
            }
            $result[] = $line;
        }
        return $result;
    }

    public static function FieldValue($value,$type = 'string') {
        $result = $value === null ? '' : $value;
        if ($type == 'string') {
            $result = '"'.str_replace('""','"',$result).'"';
        }
        return $result;
    }

}