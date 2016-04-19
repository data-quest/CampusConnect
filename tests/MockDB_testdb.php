<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */



class MockDBTestCase extends UnitTestCase {


    function setUp() {
    }


    function tearDown() {
        DBManager::get()->exec("TRUNCATE TABLE auth_user_md5 ");
    }


    function test_do_we_have_access_to_the_database() {
        $db = DBManager::get();
        $columns = $db->query("SELECT * FROM `auth_user_md5`")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertEqual(count($columns), 0);
        $db->exec("INSERT INTO auth_user_md5 SET user_id = MD5('test'), username = 'test'");
        $columns = $db->query("SELECT * FROM `auth_user_md5`")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertEqual(count($columns), 1);

    }

}


