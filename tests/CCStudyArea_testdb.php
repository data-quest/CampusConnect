<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CCStudyArea.php";

class CCStudyAreaCase extends UnitTestCase {

    function setUp()
    {
        CampusConnectLog::get()->setLogLevel(10000);
        DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_entities` (
                `item_id` varchar(256) NOT NULL,
                `type` varchar(20) NOT NULL,
                `foreign_id` varchar(64) DEFAULT NULL,
                `participant_id` int(11) NOT NULL,
                `data` text NOT NULL,
                PRIMARY KEY (`item_id`,`type`)
            )");
        DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_config` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
                PRIMARY KEY (`id`)
            )");
        DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_trees` (
                `tree_id` varchar(32) NOT NULL,
                `root_id` varchar(64) NOT NULL,
                `participant_id` int(11) NOT NULL,
                `title` varchar(128) NOT NULL,
                `mapping` enum('pending','all','manual') NOT NULL DEFAULT 'pending',
                `sem_tree_id` varchar(32) DEFAULT NULL,
                `data` text NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`tree_id`)
            )
        ");

        DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_tree_items` (
                `item_id` varchar(64) NOT NULL,
                `participant_id` int(11) NOT NULL,
                `title` varchar(128) NOT NULL,
                `parent_id` varchar(64) NULL,
                `root_id` varchar(64) NOT NULL,
                `sem_tree_id` varchar(32) DEFAULT NULL,
                `mapped_sem_tree_id` varchar(32) DEFAULT NULL,
                `data` text NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`item_id`,`participant_id`)
            )
        ");
    }


    function tearDown()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_entities`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_config`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_trees`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_tree_items`");
    }

    function test_createFromStudyAreaMessage() {
        $db = DBManager::get();
        $anzahl = $db->query("SELECT COUNT(*) FROM campus_connect_trees ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);
        $anzahl = $db->query("SELECT COUNT(*) FROM campus_connect_tree_items ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $message = array(
            'rootID' => "hgfghfhg",
            'directoryTreeTitle' => "WS",
            'nodes' => array(
                array(
                    'id' => "2",
                    'title' => "Kunst Bachelor",
                    'order' => "1",
                    'parent' => array('id' => "2")
                )
            )
        );
        $participant = new CCParticipant();
        $participant->store();
        CCStudyArea::createFromStudyAreaMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM campus_connect_tree_items")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $participant['data'] = array('import_settings' => array('course_entity_type' => "cms"));
        $participant->store();
        CCStudyArea::createFromStudyAreaMessage($message, $participant->getId());
        $anzahl = $db->query("SELECT COUNT(*) FROM campus_connect_tree_items")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
        $anzahl = $db->query("SELECT COUNT(*) FROM campus_connect_trees")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);

    }

}


