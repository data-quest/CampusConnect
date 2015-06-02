<?php
class EntityDataTable extends Migration
{
	function up() {
	    DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_entities` (
                `item_id` VARCHAR(128) NOT NULL,
                `type` varchar(20) NOT NULL,
                `foreign_id` VARCHAR(64) NULL,
                `participant_id` INT NOT NULL,
                `data` text NOT NULL,
            PRIMARY KEY (`item_id`,`type`)) ENGINE=MyISAM");
	}
}