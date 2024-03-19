<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CampusConnectHelper
{

    static public function rec_array_merge($arr1, $arr2) {
        foreach ($arr2 as $index => $value) {
            if (is_int($index)) {
                $arr1[$index] = $value;
            } else {
                if (is_object($arr1[$index]) && get_class($arr1[$index]) === "stdClass") {
                    $arr1[$index] = (array) $arr1[$index];
                }
                if (is_object($value) && get_class($value) === "stdClass") {
                    $value = (array) $value;
                }
                if (is_array($arr1[$index]) && is_array($value)) {
                    $arr1[$index] = self::rec_array_merge($arr1[$index], $value);
                } else {
                    $arr1[$index] = $value;
                }
            }
        }
        return $arr1;
    }
}
