<?php
class CampusConnectRessourcesMigration extends Migration
{
	function up() {
	    DBManager::get()->exec(
            "
            CREATE TABLE `campus_connect_ressources` (
                `ressource_id` VARCHAR( 32 ) NOT NULL ,
                `json` TEXT NOT NULL ,
                `mkdate` BIGINT NOT NULL ,
                PRIMARY KEY ( `ressource_id` )
            ) ENGINE = MYISAM
        ");
	}
}