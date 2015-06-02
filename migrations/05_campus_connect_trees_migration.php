<?php
class CampusConnectTreesMigration extends Migration
{
	function up() {
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
	}
}