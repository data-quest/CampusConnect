<?php
class CampusConnectCourseUrlsAlterMigration extends Migration
{
	function up() {
	    DBManager::get()->exec(
            "ALTER TABLE `campus_connect_course_url`
            ADD `linktitle` VARCHAR( 265 ) NULL AFTER `course_url`
        ");
	}
}