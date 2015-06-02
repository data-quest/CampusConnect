<?php
class CampusConnectSentItemsMigration extends Migration
{
	function up() {
	    DBManager::get()->exec(
            "
            CREATE TABLE IF NOT EXISTS `campus_connect_sent_items` (
                `item_id` varchar(32) NOT NULL,
                `object_type` varchar(64) NOT NULL,
                `resource_id` VARCHAR(128) NOT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`item_id`,`object_type`),
                KEY `resource_id` (`resource_id`)
            ) ENGINE=MyISAM
        ");
	}
}