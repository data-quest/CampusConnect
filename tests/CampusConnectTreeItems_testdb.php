<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../lib/CampusConnectTreeItems.php";

class CampusConnectTreeItemsTestCase extends UnitTestCase {

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
            ) ENGINE=MyISAM");
        DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_config` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM");
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
            ) ENGINE=MyISAM
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
            ) ENGINE=MyISAM
        ");
        DBManager::get()->exec("TRUNCATE TABLE `sem_tree`");
        DBManager::get()->exec("TRUNCATE TABLE `seminar_sem_tree`");
    }


    function tearDown()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_entities`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_config`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_trees`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `campus_connect_tree_items`");
        DBManager::get()->exec("TRUNCATE TABLE `sem_tree`");
        DBManager::get()->exec("TRUNCATE TABLE `seminar_sem_tree`");
    }

    function test_map() {
        $db = DBManager::get();
        
        $db->exec("
            INSERT INTO sem_tree 
            SET sem_tree_id = MD5('hallo'),
                name = 'hallo',
                parent_id = 'root'
        ");
        $anzahl = $db->query("SELECT COUNT(*) FROM sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
        
        $tree_item = new CampusConnectTreeItems();
        $tree_item['item_id'] = 1;
        $tree_item['participant_id'] = 1;
        $tree_item['root_id'] = 1;
        $tree_item['title'] = "Yeah";
        $tree_item->store();
        
        $tree_item->map(md5("hallo"));
        
        $anzahl = $db->query("SELECT COUNT(*) FROM sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 2);
        
        $tree_item->map(null);
        
        $anzahl = $db->query("SELECT COUNT(*) FROM sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
        
        $tree_item2 = new CampusConnectTreeItems();
        $tree_item2['item_id'] = 2;
        $tree_item2['participant_id'] = 1;
        $tree_item2['root_id'] = 1;
        $tree_item2['parent_id'] = 1;
        $tree_item2['title'] = "Yeah 2";
        $tree_item2->store();
        
        $tree_item->map(md5("hallo"));
        
        $sem_tree = $db->query("SELECT * FROM sem_tree")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertEqual(count($sem_tree), 3);
        foreach ($sem_tree as $s) {
            $this->assertEqual(in_array($s['name'], array("hallo","Yeah","Yeah 2")), true);
        }
        
        $tree_item->map(null);
        
        $anzahl = $db->query("SELECT COUNT(*) FROM sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
        
        //Nun auch mit Kursen:
        
        $course1 = new CampusConnectEntity();
        $course1['item_id'] = md5("kurs1");
        $course1['participant_id'] = 1;
        $course1['type'] = "course";
        $course1['foreign_id'] = 1;
        $data = array();
        $data['degreeProgrammes'] = array(array('id' => 1));
        $course1['data'] = $data;
        $course1->store();
        
        $anzahl = $db->query("SELECT COUNT(*) FROM seminar_sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);
        
        $tree_item->map(md5("hallo"));
        
        $anzahl = $db->query("SELECT COUNT(*) FROM seminar_sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 1);
        
        $tree_item->map(null);
        
        $anzahl = $db->query("SELECT COUNT(*) FROM seminar_sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 0);

        $course2 = new CampusConnectEntity();
        $course2['item_id'] = md5("kurs2");
        $course2['participant_id'] = 1;
        $course2['type'] = "course";
        $course2['foreign_id'] = 2;
        $data = array();
        $data['degreeProgrammes'] = array(array('id' => 2));
        $course2['data'] = $data;
        $course2->store();

        $tree_item->map(md5("hallo"));

        $anzahl = $db->query("SELECT COUNT(*) FROM seminar_sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 2);

        $data['degreeProgrammes'] = array(array('id' => 2));
        $course1['data'] = $data;
        $course1->store();
        
        $tree_item->map(md5("hallo"));
        
        $anzahl = $db->query("SELECT COUNT(*) FROM seminar_sem_tree")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($anzahl, 2);

    }

}


