<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class CampusConnectTriggerStack extends SimpleORMap
{
    static protected function configure($config = array())
    {
        $config['db_table'] = 'campus_connect_trigger_stack';
        parent::configure($config);
    }

    static public function findAll($where = "")
    {
        return self::findBySQL(
            ($where ? $where . " " : "1=1 ") .
            "GROUP BY object_type, object_id"
        );
    }

    static public function add($type, $object_id)
    {
        $resource = new CampusConnectTriggerStack();
        $resource['object_type'] = $type;
        $resource['object_id'] = $object_id;
        return (bool) $resource->store();
    }

    static public function clear()
    {
        $statement = DBManager::get()->prepare(
            "DELETE FROM `campus_connect_trigger_stack` " .
        "");
        return $statement->execute();
    }
}
