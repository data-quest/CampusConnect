<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CCInstitutes.php";

class CCInstitutesTestCase extends UnitTestCase {

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
            ) ENGINE=MyISAM");
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `campus_connect_config` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
            PRIMARY KEY (`id`)) ENGINE=MyISAM");
        DBManager::get()->exec("TRUNCATE TABLE `Institute`");
    }


    function tearDown()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_entities`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_config`");
        DBManager::get()->exec("TRUNCATE TABLE `Institute`");
    }

    function test_createFromOrganisationalUnitsMessage() {
        $db = DBManager::get();
        $anzahl = $db->query("SELECT COUNT(*) FROM Institute ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $message = array(
            'id' => "23",
            'title' => "Mathematisches Institut"
        );
        $participant = new CCParticipant();
        $participant->store();
        CCInstitutes::createFromOrganisationalUnitsMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM Institute")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $participant['data'] = array('import_settings' => array('course_entity_type' => "cms"));
        $participant->store();
        CCInstitutes::createFromOrganisationalUnitsMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM Institute")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);

        $message['title'] = "Biologische Fakultät";
        CCInstitutes::createFromOrganisationalUnitsMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM Institute")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);

    }

}


