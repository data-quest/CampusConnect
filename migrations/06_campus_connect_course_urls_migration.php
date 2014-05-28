<?php
class CampusConnectCourseUrlsMigration extends DBMigration
{
	function up() {
	    DBManager::get()->exec(
            "CREATE TABLE IF NOT EXISTS `campus_connect_course_url` (
                `seminar_id` varchar(32) NOT NULL,
                `participant_id` int(11) NOT NULL,
                `course_url` varchar(256) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`seminar_id`,`course_url`)
            ) ENGINE=MyISAM
        ");
	}
}