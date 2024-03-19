<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once "lib/classes/SemType.class.php";

class SemTypeTestCase extends UnitTestCase {


    function setUp() {
        $db = DBManager::get();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS `sem_types` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(64) NOT NULL,
                `class` int(11) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                `chdate` bigint(20) NOT NULL,
                PRIMARY KEY (`id`)
            ) " .
        "");
        $db->exec("TRUNCATE TABLE seminare ");
    }

    function tearDown() {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS sem_types ");
        $db->exec("TRUNCATE TABLE seminare ");
    }


    function test_init() {
        $db = DBManager::get();
        $db->exec(
            "INSERT INTO sem_types (id, name, class, mkdate, chdate) " .
            "VALUES " .
                "(1, 'test1', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(2, 'test2', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(3, 'test3', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()) " .
        "");
        $sem_type = new SemType(2);
        $this->assertEqual($sem_type['name'], "test2");
    }

    function test_count_seminars_store() {
        $db = DBManager::get();
        $db->exec(
            "INSERT INTO sem_types (id, name, class, mkdate, chdate) " .
            "VALUES " .
                "(1, 'test1', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(2, 'test2', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(3, 'test3', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()) " .
        "");
        $sem_type = new SemType(2);
        $this->assertEqual($sem_type->countSeminars(), 0);
        $db->exec(
            "INSERT INTO seminare (Seminar_id, Name, status, mkdate, chdate) " .
            "VALUES " .
                "(1, 'test1', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(2, 'test2', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(3, 'test3', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()) " .
        "");
        $this->assertEqual($sem_type->countSeminars(), 2);

        $sem_type->set('name', "hey");
        $sem_type['name'] = "hey2"; //forbidden access

        $success = $sem_type->store();
        $this->assertEqual($success, 1);
        $new_name = $db->query("SELECT name FROM sem_types WHERE id = 2")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($new_name, "hey");
        $this->assertEqual($sem_type['name'], "hey");
    }

    function test_delete_gettypes_refreshtypes() {
        $db = DBManager::get();
        $db->exec(
            "INSERT INTO sem_types (id, name, class, mkdate, chdate) " .
            "VALUES " .
                "(1, 'test1', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(2, 'test2', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(3, 'test3', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()) " .
        "");
        $db->exec(
            "INSERT INTO seminare (Seminar_id, Name, status, mkdate, chdate) " .
            "VALUES " .
                "(1, 'test1', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(2, 'test2', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()), " .
                "(3, 'test3', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()) " .
        "");
        SemType::refreshTypes();

        $sem_type = new SemType(3);
        $this->assertEqual(count(SemType::getTypes()), 3);
        $sem_type->delete();
        $this->assertEqual(count(SemType::getTypes()), 3);
        $this->assertEqual(count(SemType::refreshTypes()), 2);
        $this->assertEqual(count(SemType::getTypes()), 2);

        $sem_type = new SemType(2);
        $sem_type->delete();
        $this->assertEqual(count(SemType::refreshTypes()), 2); //nichts passiert, da noch Seminare zugeordnet sind.
    }

}


