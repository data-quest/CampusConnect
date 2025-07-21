<?php
class AddKeyToTriggerStack extends Migration
{
	function up(){
        DBManager::get()->exec("
            TRUNCATE TABLE `campus_connect_trigger_stack`
        ");
        DBManager::get()->exec("
            ALTER TABLE `campus_connect_trigger_stack`
            ADD COLUMN `chdate` INT NOT NULL AFTER `object_type`,
            ADD UNIQUE INDEX `uniq` (`object_id`,`object_type`)
        ");
	}

    function down() {
        DBManager::get()->exec("
            ALTER TABLE `campus_connect_trigger_stack`
            DROP COLUMN `chdate`,
            DROP INDEX `uniq`
        ");
    }
}
