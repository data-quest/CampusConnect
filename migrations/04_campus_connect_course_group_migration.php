<?php
class CampusConnectCourseGroupMigration extends Migration
{
	function up() {
	    DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_course_group` (
                `cg_id` VARCHAR( 32 ) NOT NULL ,
                `Seminar_id` VARCHAR( 32 ) NOT NULL ,
                `parallelgroup_id` VARCHAR( 64 ) NULL,
                PRIMARY KEY (`cg_id`,`Seminar_id`)
            ) ENGINE = MYISAM
        ");
	}
}