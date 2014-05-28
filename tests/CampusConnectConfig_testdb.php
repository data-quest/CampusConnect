<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CampusConnectConfig.php";

class CampusConnectTestCase extends UnitTestCase {


    function setUp() {
        $db = DBManager::get();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS campus_connect_config (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
            PRIMARY KEY (`id`)) ENGINE=MyISAM" .
        "");
    }


    function tearDown() {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS campus_connect_config ");
    }


    function test_create() {
        $db = DBManager::get();
        $all = $db->query("SELECT COUNT(*) FROM campus_connect_config ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($all, 0);

        $server = new CampusConnectConfig();
        $server['type'] = "server";
        $server['active'] = 0;
        $server['data'] = array('yeah' => 1);
        $server->store();
        
        //Integer-ID austesten
        $this->assertEqual($server->getId(), 1);
        $config = $db->query("SELECT * FROM campus_connect_config ")->fetch(PDO::FETCH_ASSOC);
        $this->assertEqual($config['id'], 1);

        //testen, ob data korrekt gespeichert wurde
        $data = unserialize($config['data']);
        $this->assertEqual($data['yeah'], 1);
    }

}


