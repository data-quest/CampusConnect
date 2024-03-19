<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CCTerms.php";

class CCTermsTestCase extends UnitTestCase {

    function setUp()
    {
        CampusConnectLog::get()->setLogLevel(10000);
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `campus_connect_entities` (
                `item_id` varchar(256) NOT NULL,
                `type` varchar(20) NOT NULL,
                `foreign_id` varchar(64) DEFAULT NULL,
                `participant_id` int(11) NOT NULL,
                `data` text NOT NULL,
                PRIMARY KEY (`item_id`,`type`)
            )");
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `campus_connect_config` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
            PRIMARY KEY (`id`))");
        DBManager::get()->exec("TRUNCATE TABLE `semester_data`");
    }


    function tearDown()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_entities`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_config`");
        DBManager::get()->exec("TRUNCATE TABLE `semester_data`");
    }

    function test_createFromTermsMessage() {
        $db = DBManager::get();
        $anzahl = $db->query("SELECT COUNT(*) FROM semester_data ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $message = array(
            'id' => "2",
            'title' => "WS",
            'start' => "2012-10-01T00:00:00+01:00",
            'end' => "2013-3-31T23:59:59+01:00"
        );
        $participant = new CCParticipant();
        $participant->store();
        CCTerms::createFromTermsMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM semester_data")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $participant['data'] = array('import_settings' => array('course_entity_type' => "cms"));
        $participant->store();
        CCTerms::createFromTermsMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM semester_data")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);

        $message['title'] = "WS 12/13";
        CCTerms::createFromTermsMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM semester_data")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);

        $message['id'] = "3";
        $message['start'] = "2012-11-01T00:00:00+01:00";
        $message['title'] = "WS 2012/2013";
        $this->expectException(new Exception("Imported semester overlaps with existent semester."));
        CCTerms::createFromTermsMessage($message, $participant->getId());
    }

}


