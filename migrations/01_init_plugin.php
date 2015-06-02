<?php
class InitPlugin extends Migration
{
	function up(){
	    DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_config` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `type` varchar(20) NOT NULL,
                `active` tinyint(4) NOT NULL DEFAULT '0',
                `data` text NOT NULL,
            PRIMARY KEY (`id`)) ENGINE=MyISAM");
	}
}