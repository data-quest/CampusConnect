<?php
class CreateTriggerStack extends Migration
{
	function up(){
	    DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_trigger_stack` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `object_id` varchar(32) NOT NULL,
                `object_type` VARCHAR(32) NOT NULL,
                `mkdate` BIGINT NOT NULL,
            PRIMARY KEY (`id`))"
        );
	}

    function down() {
        DBManager::get()->exec(
            "DROP TABLE IF EXISTS `campus_connect_trigger_stack`"
        );
    }
}
