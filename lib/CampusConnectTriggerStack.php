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
        $statement = DBManager::get()->prepare("
            INSERT INTO `campus_connect_trigger_stack`
            SET `object_type` = :object_type,
                `object_id` = :object_id,
                `chdate` = UNIX_TIMESTAMP(),
                `mkdate` = UNIX_TIMESTAMP()
            ON DUPLICATE KEY UPDATE `chdate` = UNIX_TIMESTAMP()
        ");
        return $statement->execute([
            'object_type' => $type,
            'object_id' => $object_id
        ]);
    }

    static public function clear()
    {
        $statement = DBManager::get()->prepare(
            "DELETE FROM `campus_connect_trigger_stack` " .
        "");
        return $statement->execute();
    }

    public function getName() : String
    {
        switch ($this->object_type) {
            case 'course':
                $course = Course::find($this->object_id);
                if ($course) {
                    return $course->name;
                } else {
                    return _("Gelöschte Veranstaltung");
                }
            default:
                return $this->object_id." (".$this->object_type.")";
        }
    }
}
